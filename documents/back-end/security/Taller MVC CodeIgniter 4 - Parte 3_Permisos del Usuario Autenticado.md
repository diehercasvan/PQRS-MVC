# Fase 6: Endpoint de Permisos del Usuario Autentica

## Paso 6.1: Ampliar el ModuleRoleUserMode

Ya tenemos el método getPermissionsByUser() en el modelo. Lo vamos a reutilizar. Solo asegúrate de que exista en app/Models/ModuleRoleUserModel.php:

```php
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
```

### Paso 6.2: Ampliar el AuthControlle

Abre **`app/Controllers/Auth/AuthController.php`** y agrega el método `permissions()`. El archivo debe quedar así (te muestro solo el método nuevo, agrégalo dentro de la clase):

```PHP
    /**
     * GET /api/auth/permissions
     * Requiere: Header Authorization: Bearer <token>
     * Devuelve los módulos y permisos del usuario autenticado.
     */
    public function permissions()
    {
        $token = $this->jwt->getBearerToken();

        if (!$token) {
            return $this->response->setStatusCode(401)->setJSON([
                'status' => 'error',
                'message' => 'Token no proporcionado.'
            ]);
        }

        $payload = $this->jwt->validate($token);

        if (!$payload) {
            return $this->response->setStatusCode(401)->setJSON([
                'status' => 'error',
                'message' => 'Token inválido o expirado.'
            ]);
        }

        $mruModel = new \App\Models\ModuleRoleUserModel();
        $permissions = $mruModel->getPermissionsByUser((int) $payload['id']);

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Permisos obtenidos correctamente.',
            'data' => $permissions
        ]);
    }
```

> ⚠️ **Importante:** Como este método está en el `AuthController`, que **no** usa el filtro `auth` (porque el login no requiere token), la validación del token se hace manualmente dentro del método. Esto es intencional: `login` es público, pero `me` y `permissions` requieren token.

### Paso 6.3: Registrar la Rutas

Edita **`app/Config/Routes.php`** y agrega esta ruta dentro del grupo `api/auth`:

```PHP
$routes->group('api/auth', ['namespace' => 'App\Controllers\Auth'], function ($routes) {
    $routes->post('login', 'AuthController::login');
    $routes->get('me', 'AuthController::me');
    $routes->get('permissions', 'AuthController::permissions'); // <-- NUEVA
});
```

### Paso 6.4: Probar en Postma

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

**B. Consultar permisos del usuario autenticad**

| Campo   | Valor                                                              |
| ------- | ------------------------------------------------------------------ |
| Método | `GET`                                                            |
| URL     | `http://localhost:8080/api/auth/permission`                      |
| Headers | `Content-Type: application/json  Authorization: Bearer <token> ` |

Body:

```JSON
{
    "status": "success",
    "message": "Permisos obtenidos correctamente.",
    "data": [
        {
            "module_id": 1,
            "module_name": "Dashboard",
            "route": "#dashboard",
            "icon": "bi-speedometer2",
            "permissions": ["view", "print"],
            "roles": ["admin"]
        },
        {
            "module_id": 2,
            "module_name": "Usuarios",
            "route": "#user",
            "icon": "bi-people",
            "permissions": ["view", "create", "edit", "delete", "download"],
            "roles": ["admin"]
        },
        // ... y así para cada módulo
    ]
}
```
