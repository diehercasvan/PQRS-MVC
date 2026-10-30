
# Taller: Backend de Seguridad - CodeIgniter 4 Asignación de Permisos

## Objetivo

Construir los módulos de seguridad (Roles,Permisos, Módulos, Roles y `Asignación de Permisos`) del proyecto PQRS, usando CodeIgniter 4 con autenticación JWT, filtros de seguridad, y validación progresiva con Postman.

---

## Decisiones de Arquitectura

| Decisión             | Elección                                                              | Justificación                                                              |
| --------------------- | ---------------------------------------------------------------------- | --------------------------------------------------------------------------- |
| Validación del token | **Filtro de CI4** (`AuthFilter`)                               | Más elegante, centralizado y desacoplado de los controladores.             |
| Borrado lógico       | `DELETE /api/{recurso}/{id}`                                         | El backend cambia el estado internamente. El frontend solo hace `DELETE`. |
| Orden de desarrollo   | Roles → Permisos → Módulos → Usuarios→`Asignación de Permisos` | De lo más simple a lo más complejo.                                       |

---

## Fase 5: Módulo de Asignación de Permiso

Paso 5.1: Ampliar el ModuleRoleUserMode

Crea (o ajusta)  **`app/Models/ModuleRoleUserModel.php`** :

```php
<?php

namespace App\Models;

use CodeIgniter\Model;

class ModuleRoleUserModel extends Model
{
    protected $table            = 'module_role_user';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = ['module_id', 'role_id', 'user_id', 'permission_id'];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    /**
     * Retorna todos los permisos de un usuario agrupados por módulo.
     */
    public function getPermissionsByUser(int $userId)
    {
        $rows = $this->select('module.id as module_id, module.name as module_name, module.route, module.icon, permission.id as permission_id, permission.name as permission_name, role.name as role_name')
                     ->join('module', 'module.id = module_role_user.module_id')
                     ->join('permission', 'permission.id = module_role_user.permission_id')
                     ->join('role', 'role.id = module_role_user.role_id')
                     ->where('module_role_user.user_id', $userId)
                     ->orderBy('module.name', 'ASC')
                     ->findAll();

        // Agrupar por módulo
        $grouped = [];
        foreach ($rows as $row) {
            $moduleId = $row['module_id'];
            if (!isset($grouped[$moduleId])) {
                $grouped[$moduleId] = [
                    'module_id'   => $row['module_id'],
                    'module_name' => $row['module_name'],
                    'route'       => $row['route'],
                    'icon'        => $row['icon'],
                    'permissions' => [],
                    'roles'       => [],
                ];
            }
            if (!in_array($row['permission_name'], $grouped[$moduleId]['permissions'])) {
                $grouped[$moduleId]['permissions'][] = $row['permission_name'];
            }
            if (!in_array($row['role_name'], $grouped[$moduleId]['roles'])) {
                $grouped[$moduleId]['roles'][] = $row['role_name'];
            }
        }

        return array_values($grouped);
    }
}
```

### Paso 5.2: Crear el Controlador de Asignación

Crea  **`app/Controllers/Security/PermissionAssignmentController.php`** :

```PHP
<?php

namespace App\Controllers\Security;

use App\Controllers\BaseApiController;
use App\Models\ModuleRoleUserModel;
use App\Models\UserModel;
use App\Models\RoleModel;
use App\Models\ModuleModel;
use App\Models\PermissionModel;

class PermissionAssignmentController extends BaseApiController
{
    protected ModuleRoleUserModel $mruModel;
    protected UserModel $userModel;
    protected RoleModel $roleModel;
    protected ModuleModel $moduleModel;
    protected PermissionModel $permissionModel;

    public function __construct()
    {
        $this->mruModel = new ModuleRoleUserModel();
        $this->userModel = new UserModel();
        $this->roleModel = new RoleModel();
        $this->moduleModel = new ModuleModel();
        $this->permissionModel = new PermissionModel();
    }

    /**
     * POST /api/permissions/assign
     * Body: { "user_id": 1, "role_id": 1, "module_id": 2, "permission_ids": [1, 2, 3] }
     */
    public function assign()
    {
        $input = $this->request->getJSON(true);

        $required = ['user_id', 'role_id', 'module_id', 'permission_ids'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                return $this->respondError("El campo {$field} es obligatorio.", 400);
            }
        }

        if (!is_array($input['permission_ids'])) {
            return $this->respondError('permission_ids debe ser un array.', 400);
        }

        // Validar que existan las entidades
        if (!$this->userModel->find($input['user_id'])) {
            return $this->respondError('Usuario no encontrado.', 404);
        }
        if (!$this->roleModel->find($input['role_id'])) {
            return $this->respondError('Rol no encontrado.', 404);
        }
        if (!$this->moduleModel->find($input['module_id'])) {
            return $this->respondError('Módulo no encontrado.', 404);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $inserted = 0;
            $skipped = 0;

            foreach ($input['permission_ids'] as $permissionId) {
                if (!$this->permissionModel->find($permissionId)) {
                    continue; // Ignorar permisos inexistentes
                }

                // Verificar si ya existe
                $exists = $this->mruModel
                    ->where('user_id', $input['user_id'])
                    ->where('role_id', $input['role_id'])
                    ->where('module_id', $input['module_id'])
                    ->where('permission_id', $permissionId)
                    ->first();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                $this->mruModel->insert([
                    'user_id'       => (int) $input['user_id'],
                    'role_id'       => (int) $input['role_id'],
                    'module_id'     => (int) $input['module_id'],
                    'permission_id' => (int) $permissionId,
                ]);
                $inserted++;
            }

            $db->transCommit();

            return $this->respondSuccess([
                'inserted' => $inserted,
                'skipped'  => $skipped,
            ], "Permisos asignados: {$inserted}, omitidos (ya existían): {$skipped}.", 201);

        } catch (\Exception $e) {
            $db->transRollback();
            return $this->respondError('Error al asignar permisos: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/permissions/user/{userId}
     */
    public function getUserPermissions($userId = null)
    {
        if (!$this->userModel->find($userId)) {
            return $this->respondError('Usuario no encontrado.', 404);
        }

        $permissions = $this->mruModel->getPermissionsByUser((int) $userId);

        return $this->respondSuccess($permissions, 'Permisos del usuario obtenidos correctamente.');
    }

    /**
     * DELETE /api/permissions/revoke
     * Body: { "user_id": 1, "role_id": 1, "module_id": 2, "permission_id": 3 }
     */
    public function revoke()
    {
        $input = $this->request->getJSON(true);

        $required = ['user_id', 'role_id', 'module_id', 'permission_id'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                return $this->respondError("El campo {$field} es obligatorio.", 400);
            }
        }

        $exists = $this->mruModel
            ->where('user_id', $input['user_id'])
            ->where('role_id', $input['role_id'])
            ->where('module_id', $input['module_id'])
            ->where('permission_id', $input['permission_id'])
            ->first();

        if (!$exists) {
            return $this->respondError('El permiso no está asignado al usuario.', 404);
        }

        $this->mruModel->delete($exists['id']);

        return $this->respondSuccess(null, 'Permiso revocado correctamente.');
    }
}
```

### Paso 5.3: Registrar las Rutas

Agrega en  **`app/Config/Routes.php`** :

```PHP
// Rutas de Asignación de Permisos (protegidas)
$routes->group('api/permissions', ['namespace' => 'App\Controllers\Security', 'filter' => 'auth'], function ($routes) {
    $routes->post('assign', 'PermissionAssignmentController::assign');
    $routes->get('user/(:num)', 'PermissionAssignmentController::getUserPermissions/$1');
    $routes->delete('revoke', 'PermissionAssignmentController::revoke');
});
```

⚠️ **Importante:** Estas rutas deben ir **antes** del grupo genérico `api/permissions` que apunta a `PermissionController`, porque `assign`, `user` y `revoke` podrían coincidir con el patrón `(:num)` si no se ordenan bien. En CI4, el orden de las rutas importa.

Para evitar conflictos, reordena el archivo `Routes.php` así:

```PHP
// 1. Rutas específicas de asignación (van primero)
$routes->group('api/permissions', ['namespace' => 'App\Controllers\Security', 'filter' => 'auth'], function ($routes) {
    $routes->post('assign', 'PermissionAssignmentController::assign');
    $routes->get('user/(:num)', 'PermissionAssignmentController::getUserPermissions/$1');
    $routes->delete('revoke', 'PermissionAssignmentController::revoke');
});

// 2. Rutas genéricas de permisos (van después)
$routes->group('api/permissions', ['namespace' => 'App\Controllers\Security', 'filter' => 'auth'], function ($routes) {
    $routes->get('/', 'PermissionController::showAll');
    $routes->get('(:num)', 'PermissionController::showId/$1');
    $routes->post('/', 'PermissionController::create');
    $routes->put('(:num)', 'PermissionController::update/$1');
    $routes->delete('(:num)', 'PermissionController::delete/$1');
});
```

### Paso 5.4: Probar en Postman

 ⚠️ **Importante:** Primero debes hacer login para obtener un token válido.

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

**B. Asignar permisos (POST**

| Campo   | Valor                                           |
| ------- | ----------------------------------------------- |
| Método | `POST`                                        |
| URL     | `http://localhost:8080/api/permissions/assig` |
| Headers | `Content-Type: application/json`              |

Body:

```JSON
{
    "user_id": 2,
    "role_id": 2,
    "module_id": 2,
    "permission_ids": [1, 2, 3]
}
```

**Resultado esperado (201):**

```JSON
{
    "status": "success",
    "message": "Permisos asignados: 3, omitidos (ya existían): 0.",
    "data": { "inserted": 3, "skipped": 0 }
}
```

## Checklist de Validación

### Asignación de Permisos

* `POST /api/permissions/assign` inserta permisos correctamente.
* Si un permiso ya existe, se omite (skipped) sin error.
* `POST /api/permissions/assign` con `user_id` inexistente → `404`.
* `POST /api/permissions/assign` con `permission_ids` vacío → `400`.
* `GET /api/permissions/user/{id}` agrupa permisos por módulo.
* `DELETE /api/permissions/revoke` elimina el permiso específico.
* `DELETE /api/permissions/revoke` con permiso no asignado → `404`.
