# Taller: Backend de Seguridad - CodeIgniter 4 Permisos

## Objetivo

Construir los módulos de seguridad (Roles, *Permisos*, Módulos, Usuarios y Asignación de Permisos) del proyecto PQRS, usando CodeIgniter 4 con autenticación JWT, filtros de seguridad, y validación progresiva con Postman.

---

## Decisiones de Arquitectura

| Decisión             | Elección                                                     | Justificación                                                              |
| --------------------- | ------------------------------------------------------------- | --------------------------------------------------------------------------- |
| Validación del token | **Filtro de CI4** (`AuthFilter`)                      | Más elegante, centralizado y desacoplado de los controladores.             |
| Borrado lógico       | `DELETE /api/{recurso}/{id}`                                | El backend cambia el estado internamente. El frontend solo hace `DELETE`. |
| Orden de desarrollo   | Roles → `Permisos` → Módulos → Usuarios → Asignación | De lo más simple a lo más complejo.                                       |

---

## Fase 2: Módulo de Permiso

### Paso 2.1: Crear el Modelo

Crea (o ajusta si ya existe)  **`app/Models/PermissionModel.php`**:

```php
<?php

namespace App\Models;

use CodeIgniter\Model;

class PermissionModel extends Model
{
    protected $table            = 'permission';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = ['name', 'description'];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    /**
     * Retorna todos los permisos ordenados por ID.
     */
    public function getAll()
    {
        return $this->orderBy('id', 'ASC')->findAll();
    }
}
```

### Paso 2.2: Crear el Controlador

Crea  **`app/Controllers/Security/PermissionController.php`** :

```PHP
<?php

namespace App\Controllers\Security;

use App\Controllers\BaseApiController;
use App\Models\PermissionModel;

class PermissionController extends BaseApiController
{
    protected PermissionModel $permissionModel;

    public function __construct()
    {
        $this->permissionModel = new PermissionModel();
    }

    /**
     * GET /api/permissions
     */
    public function showAll()
    {
        $permissions = $this->permissionModel->getAll();
        return $this->respondSuccess($permissions, 'Permisos obtenidos correctamente.');
    }

    /**
     * GET /api/permissions/{id}
     */
    public function showId($id = null)
    {
        $permission = $this->permissionModel->find($id);

        if (!$permission) {
            return $this->respondError('Permiso no encontrado.', 404);
        }

        return $this->respondSuccess($permission, 'Permiso obtenido correctamente.');
    }

    /**
     * POST /api/permissions
     * Body: { "name": "export", "description": "Exportar datos" }
     */
    public function create()
    {
        $input = $this->request->getJSON(true);

        if (empty($input['name'])) {
            return $this->respondError('El campo name es obligatorio.', 400);
        }

        // Verificar duplicado
        $exists = $this->permissionModel->where('name', $input['name'])->first();
        if ($exists) {
            return $this->respondError('Ya existe un permiso con ese nombre.', 409);
        }

        $data = [
            'name'        => $input['name'],
            'description' => $input['description'] ?? null,
        ];

        $id = $this->permissionModel->insert($data);
        $permission = $this->permissionModel->find($id);

        return $this->respondSuccess($permission, 'Permiso creado correctamente.', 201);
    }

    /**
     * PUT /api/permissions/{id}
     */
    public function update($id = null)
    {
        $permission = $this->permissionModel->find($id);
        if (!$permission) {
            return $this->respondError('Permiso no encontrado.', 404);
        }

        $input = $this->request->getJSON(true);
        if (empty($input)) {
            return $this->respondError('No se enviaron datos para actualizar.', 400);
        }

        if (isset($input['name']) && $input['name'] !== $permission['name']) {
            $exists = $this->permissionModel->where('name', $input['name'])->where('id !=', $id)->first();
            if ($exists) {
                return $this->respondError('Ya existe otro permiso con ese nombre.', 409);
            }
        }

        $data = [];
        if (isset($input['name']))        $data['name'] = $input['name'];
        if (isset($input['description'])) $data['description'] = $input['description'];

        $this->permissionModel->update($id, $data);
        $updated = $this->permissionModel->find($id);

        return $this->respondSuccess($updated, 'Permiso actualizado correctamente.');
    }

    /**
     * DELETE /api/permissions/{id}
     * La tabla permission NO tiene is_active, así que el delete es físico.
     */
    public function delete($id = null)
    {
        $permission = $this->permissionModel->find($id);
        if (!$permission) {
            return $this->respondError('Permiso no encontrado.', 404);
        }

        $this->permissionModel->delete($id);

        return $this->respondSuccess(null, 'Permiso eliminado correctamente.');
    }
}
```

### Paso 2.3: Registrar las Rutas

### Edita **`app/Config/Routes.php`** y agrega el grupo de roles (protegido por el filtro `auth`):

```PHP
// Rutas de Permisos (protegidas)
$routes->group('api/permissions', ['namespace' => 'App\Controllers\Security', 'filter' => 'auth'], function ($routes) {
    $routes->get('/', 'PermissionController::showAll');
    $routes->get('(:num)', 'PermissionController::showId/$1');
    $routes->post('/', 'PermissionController::create');
    $routes->put('(:num)', 'PermissionController::update/$1');
    $routes->delete('(:num)', 'PermissionController::delete/$1');
});
```

### Paso 2.4: Probar en Postman

> ⚠️ **Importante:** Primero debes hacer login para obtener un token válido.

### Paso 2.5: Lista de chequeo Permisos

* `GET /api/permissions` → Lista los 6 permisos existentes (view, create, edit, delete, print, download).
* `GET /api/permissions/1` → Devuelve el permiso "view".
* `POST /api/permissions` con `{"name": "export", "description": "Exportar datos"}` → 201, crea el permiso.
* `POST /api/permissions` con `{"name": "view"}` → 409 (duplicado).
* `PUT /api/permissions/7` con `{"description": "Nueva descripción"}` → 200, actualiza.
* `DELETE /api/permissions/7` → 200, elimina físicamente.
* `GET /api/permissions/999` → 404.
