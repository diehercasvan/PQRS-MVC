# Taller: Backend de Seguridad - CodeIgniter 4 Módulos

## Objetivo

Construir los módulos de seguridad (Roles, Permisos, *Módulos*, Usuarios y Asignación de Permisos) del proyecto PQRS, usando CodeIgniter 4 con autenticación JWT, filtros de seguridad, y validación progresiva con Postman.

---

## Decisiones de Arquitectura

| Decisión             | Elección                                                             | Justificación                                                              |
| --------------------- | --------------------------------------------------------------------- | --------------------------------------------------------------------------- |
| Validación del token | **Filtro de CI4** (`AuthFilter`)                              | Más elegante, centralizado y desacoplado de los controladores.             |
| Borrado lógico       | `DELETE /api/{recurso}/{id}`                                        | El backend cambia el estado internamente. El frontend solo hace `DELETE`. |
| Orden de desarrollo   | Roles → Permisos ` →` `Módulos`  → Usuarios → Asignación | De lo más simple a lo más complejo.                                       |

---

## Fase 3: Módulo de Módulos

### Paso 3.1: Crear el Modelo

Crea (o ajusta si ya existe)  **`app/Models/ModuleModel.php`** :

```php
<?php

namespace App\Models;

use CodeIgniter\Model;

class ModuleModel extends Model
{
    protected $table            = 'module';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = ['name', 'route', 'icon', 'description', 'is_active'];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    /**
     * Retorna todos los módulos.
     */
    public function getAll()
    {
        return $this->orderBy('id', 'ASC')->findAll();
    }

    /**
     * Retorna solo módulos activos (útil para el menú del frontend).
     */
    public function getActive()
    {
        return $this->where('is_active', 1)->orderBy('name', 'ASC')->findAll();
    }
}
```

### Paso 3.2: Crear el Controlador

Crea  **`app/Controllers/Security/ModuleController.php`** :

```PHP
<?php

namespace App\Controllers\Security;

use App\Controllers\BaseApiController;
use App\Models\ModuleModel;

class ModuleController extends BaseApiController
{
    protected ModuleModel $moduleModel;

    public function __construct()
    {
        $this->moduleModel = new ModuleModel();
    }

    /**
     * GET /api/modules
     */
    public function showAll()
    {
        $modules = $this->moduleModel->getAll();
        return $this->respondSuccess($modules, 'Módulos obtenidos correctamente.');
    }

    /**
     * GET /api/modules/{id}
     */
    public function showId($id = null)
    {
        $module = $this->moduleModel->find($id);

        if (!$module) {
            return $this->respondError('Módulo no encontrado.', 404);
        }

        return $this->respondSuccess($module, 'Módulo obtenido correctamente.');
    }

    /**
     * POST /api/modules
     * Body: { "name": "Reportes", "route": "#reports", "icon": "bi-file-earmark", "description": "...", "is_active": 1 }
     */
    public function create()
    {
        $input = $this->request->getJSON(true);

        // Validación básica
        if (empty($input['name']) || empty($input['route'])) {
            return $this->respondError('Los campos name y route son obligatorios.', 400);
        }

        // Verificar duplicados
        $existsName = $this->moduleModel->where('name', $input['name'])->first();
        if ($existsName) {
            return $this->respondError('Ya existe un módulo con ese nombre.', 409);
        }

        $existsRoute = $this->moduleModel->where('route', $input['route'])->first();
        if ($existsRoute) {
            return $this->respondError('Ya existe un módulo con esa ruta.', 409);
        }

        $data = [
            'name'        => $input['name'],
            'route'       => $input['route'],
            'icon'        => $input['icon'] ?? null,
            'description' => $input['description'] ?? null,
            'is_active'   => isset($input['is_active']) ? (int) $input['is_active'] : 1,
        ];

        $id = $this->moduleModel->insert($data);
        $module = $this->moduleModel->find($id);

        return $this->respondSuccess($module, 'Módulo creado correctamente.', 201);
    }

    /**
     * PUT /api/modules/{id}
     */
    public function update($id = null)
    {
        $module = $this->moduleModel->find($id);
        if (!$module) {
            return $this->respondError('Módulo no encontrado.', 404);
        }

        $input = $this->request->getJSON(true);
        if (empty($input)) {
            return $this->respondError('No se enviaron datos para actualizar.', 400);
        }

        // Verificar duplicados si cambian name o route
        if (isset($input['name']) && $input['name'] !== $module['name']) {
            $exists = $this->moduleModel->where('name', $input['name'])->where('id !=', $id)->first();
            if ($exists) {
                return $this->respondError('Ya existe otro módulo con ese nombre.', 409);
            }
        }

        if (isset($input['route']) && $input['route'] !== $module['route']) {
            $exists = $this->moduleModel->where('route', $input['route'])->where('id !=', $id)->first();
            if ($exists) {
                return $this->respondError('Ya existe otro módulo con esa ruta.', 409);
            }
        }

        $data = [];
        if (isset($input['name']))        $data['name'] = $input['name'];
        if (isset($input['route']))       $data['route'] = $input['route'];
        if (isset($input['icon']))        $data['icon'] = $input['icon'];
        if (isset($input['description'])) $data['description'] = $input['description'];
        if (isset($input['is_active']))   $data['is_active'] = (int) $input['is_active'];

        $this->moduleModel->update($id, $data);
        $updated = $this->moduleModel->find($id);

        return $this->respondSuccess($updated, 'Módulo actualizado correctamente.');
    }

    /**
     * DELETE /api/modules/{id}
     * Soft delete: cambia is_active a 0
     */
    public function delete($id = null)
    {
        $module = $this->moduleModel->find($id);
        if (!$module) {
            return $this->respondError('Módulo no encontrado.', 404);
        }

        $this->moduleModel->update($id, ['is_active' => 0]);

        return $this->respondSuccess(null, 'Módulo desactivado correctamente.');
    }
}
```

### Paso 3.3: Registrar las Rutas

Agrega en  **`app/Config/Routes.php`** :

```PHP
// Rutas de Módulos (protegidas)
$routes->group('api/modules', ['namespace' => 'App\Controllers\Security', 'filter' => 'auth'], function ($routes) {
    $routes->get('/', 'ModuleController::showAll');
    $routes->get('(:num)', 'ModuleController::showId/$1');
    $routes->post('/', 'ModuleController::create');
    $routes->put('(:num)', 'ModuleController::update/$1');
    $routes->delete('(:num)', 'ModuleController::delete/$1');
});
```

### Paso 3.4: Probar en Postman

> ⚠️ **Importante:** Primero debes hacer login para obtener un token válido.

### Paso 3.5: Lista de chequeo Módulos

* `GET /api/modules` → Lista los módulos.
* `GET /api/modules/1` → Devuelve "Dashboard".
* `POST /api/modules` con `{"name": "Reportes", "route": "#reports", "icon": "bi-file-earmark"}` → 201.
* `POST /api/modules` con name duplicado → 409.
* `POST /api/modules` con route duplicado → 409.
* `PUT /api/modules/6` con `{"description": "Módulo de reportes"}` → 200.
* `DELETE /api/modules/6` → 200, `is_active` cambia a 0.
* `GET /api/modules/999` → 404.
