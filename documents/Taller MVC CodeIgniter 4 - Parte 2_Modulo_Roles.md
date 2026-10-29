# Taller: Backend de Seguridad - CodeIgniter 4 Roles

## Objetivo

Construir los módulos de seguridad (`Roles`, Permisos, Módulos, Usuarios y Asignación de Permisos) del proyecto PQRS, usando CodeIgniter 4 con autenticación JWT, filtros de seguridad, y validación progresiva con Postman.

---

## Decisiones de Arquitectura

| Decisión             | Elección                                                | Justificación                                                              |
| --------------------- | -------------------------------------------------------- | --------------------------------------------------------------------------- |
| Validación del token | **Filtro de CI4** (`AuthFilter`)                 | Más elegante, centralizado y desacoplado de los controladores.             |
| Borrado lógico       | `DELETE /api/{recurso}/{id}`                           | El backend cambia el estado internamente. El frontend solo hace `DELETE`. |
| Orden de desarrollo   | Roles → Permisos → Módulos → Usuarios → Asignación | De lo más simple a lo más complejo.                                       |

---

## Fase 0: Preparación

### Paso 0.1: Crear el Filtro de Autenticación

Crea el archivo **`app/Filters/AuthFilter.php`**:

```php
<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\JwtService;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $jwt = new JwtService();
        $token = $jwt->getBearerToken();

        if (!$token) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON(['status' => 'error', 'message' => 'Token no proporcionado.']);
        }

        $payload = $jwt->validate($token);
        if (!$payload) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON(['status' => 'error', 'message' => 'Token inválido o expirado.']);
        }

        // Guardar el usuario actual en el request para usarlo en los controladores
        $request->user = $payload;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No se necesita hacer nada después de la respuesta
    }
}
```

### Paso 0.2: Registrar el Filtro

Edita  **`app/Config/Filters.php`** . Busca el array `$aliases` y agrega:

```PHP
public array $aliases = [
    // ... otros filtros existentes
    'auth' => \App\Filters\AuthFilter::class,
];
```

### Paso 0.3: Crear el Controlador Base de API

Crea  **`app/Controllers/BaseApiController.php`** :

```PHP
<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class BaseApiController extends Controller
{
    /**
     * Respuesta exitosa estandarizada.
     */
    protected function respondSuccess($data = null, string $message = 'OK', int $code = 200)
    {
        $response = ['status' => 'success', 'message' => $message];
        if ($data !== null) {
            $response['data'] = $data;
        }
        return $this->response->setStatusCode($code)->setJSON($response);
    }

    /**
     * Respuesta de error estandarizada.
     */
    protected function respondError(string $message, int $code = 400, $errors = null)
    {
        $response = ['status' => 'error', 'message' => $message];
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        return $this->response->setStatusCode($code)->setJSON($response);
    }
}
```

## Fase 1: Módulo de Roles

### Paso 1.1: Crear el Modelo

Crea **`app/Models/RoleModel.php`** (o ajústalo si ya existe):

```PHP
<?php

namespace App\Models;

use CodeIgniter\Model;

class RoleModel extends Model
{
    protected $table            = 'role';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = ['name', 'description', 'is_active'];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    /**
     * Retorna todos los roles activos e inactivos.
     */
    public function getAll()
    {
        return $this->orderBy('id', 'ASC')->findAll();
    }

    /**
     * Retorna solo roles activos (útil para selects en el frontend).
     */
    public function getActive()
    {
        return $this->where('is_active', 1)->orderBy('name', 'ASC')->findAll();
    }
}
```

### Paso 1.2: Crear el Controlador

Crea la carpeta `app/Controllers/Security/` y dentro el archivo  **`RoleController.php`** :

```PHP
<?php

namespace App\Controllers\Security;

use App\Controllers\BaseApiController;
use App\Models\RoleModel;

class RoleController extends BaseApiController
{
    protected RoleModel $roleModel;

    public function __construct()
    {
        $this->roleModel = new RoleModel();
    }

    /**
     * GET /api/roles
     */
    public function showAll()
    {
        $roles = $this->roleModel->getAll();
        return $this->respondSuccess($roles, 'Roles obtenidos correctamente.');
    }

    /**
     * GET /api/roles/{id}
     */
    public function showId($id = null)
    {
        $role = $this->roleModel->find($id);

        if (!$role) {
            return $this->respondError('Rol no encontrado.', 404);
        }

        return $this->respondSuccess($role, 'Rol obtenido correctamente.');
    }

    /**
     * POST /api/roles
     * Body: { "name": "admin", "description": "...", "is_active": 1 }
     */
    public function create()
    {
        $input = $this->request->getJSON(true);

        // Validación básica
        if (empty($input['name'])) {
            return $this->respondError('El campo name es obligatorio.', 400);
        }

        // Verificar duplicado
        $exists = $this->roleModel->where('name', $input['name'])->first();
        if ($exists) {
            return $this->respondError('Ya existe un rol con ese nombre.', 409);
        }

        $data = [
            'name'        => $input['name'],
            'description' => $input['description'] ?? null,
            'is_active'   => isset($input['is_active']) ? (int) $input['is_active'] : 1,
        ];

        $id = $this->roleModel->insert($data);
        $role = $this->roleModel->find($id);

        return $this->respondSuccess($role, 'Rol creado correctamente.', 201);
    }

    /**
     * PUT /api/roles/{id}
     */
    public function update($id = null)
    {
        $role = $this->roleModel->find($id);
        if (!$role) {
            return $this->respondError('Rol no encontrado.', 404);
        }

        $input = $this->request->getJSON(true);
        if (empty($input)) {
            return $this->respondError('No se enviaron datos para actualizar.', 400);
        }

        // Si cambia el nombre, verificar que no exista otro con ese nombre
        if (isset($input['name']) && $input['name'] !== $role['name']) {
            $exists = $this->roleModel->where('name', $input['name'])->where('id !=', $id)->first();
            if ($exists) {
                return $this->respondError('Ya existe otro rol con ese nombre.', 409);
            }
        }

        $data = [];
        if (isset($input['name']))        $data['name'] = $input['name'];
        if (isset($input['description'])) $data['description'] = $input['description'];
        if (isset($input['is_active']))   $data['is_active'] = (int) $input['is_active'];

        $this->roleModel->update($id, $data);
        $updated = $this->roleModel->find($id);

        return $this->respondSuccess($updated, 'Rol actualizado correctamente.');
    }

    /**
     * DELETE /api/roles/{id}
     * Soft delete: cambia is_active a 0
     */
    public function delete($id = null)
    {
        $role = $this->roleModel->find($id);
        if (!$role) {
            return $this->respondError('Rol no encontrado.', 404);
        }

        $this->roleModel->update($id, ['is_active' => 0]);

        return $this->respondSuccess(null, 'Rol desactivado correctamente.');
    }
}
```

### Paso 1.3: Registrar las Rutas

Edita **`app/Config/Routes.php`** y agrega el grupo de roles (protegido por el filtro `auth`):

```PHP
// Rutas de Autenticación (sin filtro)
$routes->group('api/auth', ['namespace' => 'App\Controllers\Auth'], function ($routes) {
    $routes->post('login', 'AuthController::login');
    $routes->get('me', 'AuthController::me');
});

// Rutas de Roles (protegidas con JWT)
$routes->group('api/roles', ['namespace' => 'App\Controllers\Security', 'filter' => 'auth'], function ($routes) {
    $routes->get('/', 'RoleController::showAll');
    $routes->get('(:num)', 'RoleController::showId/$1');
    $routes->post('/', 'RoleController::create');
    $routes->put('(:num)', 'RoleController::update/$1');
    $routes->delete('(:num)', 'RoleController::delete/$1');
});
```

### Paso 1.4: Probar en Postman

> ⚠️ **Importante:** Primero debes hacer login para obtener un token válido.

**A. Login**

| Campo   | Valor                                    |
| ------- | ---------------------------------------- |
| Método | `POST`                                 |
| URL     | `http://localhost:8080/api/auth/login` |
| Headers | `Content-Type: application/json`       |

Body:

```JSON
{ "username": "admin", "password": "admin123" }
```

Copia el token de la respuesta.

**B. Crear Rol (POST)**

| Campo   | Valor                                                                     |
| ------- | ------------------------------------------------------------------------- |
| Método | `POST`                                                                  |
| URL     | `http://localhost:8080/api/roles`                                       |
| Headers | `Content-Type: application/json`<br />`Authorization: Bearer <token>` |

Body:

```JSON
{
    "name": "auditor",
    "description": "Rol de auditoría",
    "is_active": 1
}
```

Resultado esperado (201):

```JSON
{
    "status": "success",
    "message": "Rol creado correctamente.",
    "data": {
        "id": 4,
        "name": "auditor",
        "description": "Rol de auditoría",
        "is_active": 1,
        "created_at": "...",
        "updated_at": "..."
    }
}
```

**B. Listar Roles (GET**

| Campo   | Valor                                                                     |
| ------- | ------------------------------------------------------------------------- |
| Método | `GET`                                                                   |
| URL     | `http://localhost:8080/api/role`                                        |
| Headers | `Content-Type: application/json`<br />`Authorization: Bearer <token>` |

**D. Ver Rol por ID (GET)**

| Campo   | Valor                                                                     |
| ------- | ------------------------------------------------------------------------- |
| Método | `GET`                                                                   |
| URL     | `http://localhost:8080/api/roles/`                                      |
| Headers | `Content-Type: application/json`<br />`Authorization: Bearer <token>` |

**E. Actualizar Rol (PUT)**

| Campo   | Valor                                                                     |
| ------- | ------------------------------------------------------------------------- |
| Método | `PUT`                                                                   |
| URL     | `http://localhost:8080/api/roles/`                                      |
| Headers | `Content-Type: application/json`<br />`Authorization: Bearer <token>` |

Body:

```JSON
{
    "description": "Rol de auditoría actualizado"
}
```

**F. Desactivar Rol (DELETE)**

| Campo   | Valor                                                                     |
| ------- | ------------------------------------------------------------------------- |
| Método | `DELET`                                                                 |
| URL     | `http://localhost:8080/api/roles/`                                      |
| Headers | `Content-Type: application/json`<br />`Authorization: Bearer <token>` |

**G. Probar sin toke**

Haz cualquier petición a /api/roles sin el header Authorization. Debe devolver 401.

**Paso 1.5: Checklist de Validación de Roles**□ Login devuelve token.
□ POST /api/roles crea un rol (201).
□ GET /api/roles lista todos los roles.
□ GET /api/roles/{id} devuelve un rol específico.
□ PUT /api/roles/{id} actualiza el rol.
□ DELETE /api/roles/{id} cambia is_active a 0.
□ Petición sin token devuelve 401.
□ Petición con token inválido devuelve 401.
□ POST /api/roles con nombre duplicado devuelve 409.
□ GET /api/roles/999 (inexistente) devuelve 404.
