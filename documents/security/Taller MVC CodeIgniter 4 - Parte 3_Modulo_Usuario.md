# Taller: Backend de Seguridad - CodeIgniter 4 Usuarios

## Objetivo

Construir los módulos de seguridad (Roles,Permisos, Módulos, `Roles` y Asignación de Permisos) del proyecto PQRS, usando CodeIgniter 4 con autenticación JWT, filtros de seguridad, y validación progresiva con Postman.

---

## Decisiones de Arquitectura

| Decisión             | Elección                                                   | Justificación                                                              |
| --------------------- | ----------------------------------------------------------- | --------------------------------------------------------------------------- |
| Validación del token | **Filtro de CI4** (`AuthFilter`)                    | Más elegante, centralizado y desacoplado de los controladores.             |
| Borrado lógico       | `DELETE /api/{recurso}/{id}`                              | El backend cambia el estado internamente. El frontend solo hace `DELETE`. |
| Orden de desarrollo   | Roles → Permisos → Módulos → `Usuarios`→ Asignación | De lo más simple a lo más complejo.                                       |

---

## Fase 4: Módulo de Usuario

Paso 4.1: Crear los Modelos Auxiliare

Crea el archivo **`app/Models/ProfileModel.php`** :

```php
<?php

namespace App\Models;

use CodeIgniter\Model;

class ProfileModel extends Model
{
    protected $table            = 'profile';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['user_id','first_name','last_name','address','phone','document_type_id','document_number','photo_url','birth_date'];

    protected bool $allowEmptyInserts = false;

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

     public function getByUserId(int $userId)
    {
        return $this->where('user_id', $userId)->first();
    }
}
```

Paso 4.1: Crear los Modelos Auxiliare

### Edita  **`app/Models/UserRoleModel.php`**:

```PHP
<?php

namespace App\Models;

use CodeIgniter\Model;

class UserRoleModel extends Model
{
    protected $table            = 'user_role';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = ['user_id', 'role_id', 'status_id'];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    public function getRolesByUserId(int $userId)
    {
        return $this->select('role.id, role.name, role.description')
                    ->join('role', 'role.id = user_role.role_id')
                    ->where('user_role.user_id', $userId)
                    ->where('user_role.status_id', 1)
                    ->findAll();
    }
}
```

### Paso 4.2: Ampliar el `UserModel`

Agrega estos métodos a **`app/Models/UserModel.php`** (sin borrar lo existente):

```PHP
    /**
     * Retorna usuarios con perfil y roles (para showAll).
     */
    public function getAllWithProfile()
    {
        $users = $this->select('user.id, user.username, user.email, user.status_id, user.last_login, user.created_at, user.updated_at, user_status.name as status_name')
                      ->join('user_status', 'user_status.id = user.status_id')
                      ->orderBy('user.id', 'ASC')
                      ->findAll();

        // Agregar perfil y roles a cada usuario
        $profileModel = new \App\Models\ProfileModel();
        $userRoleModel = new \App\Models\UserRoleModel();

        foreach ($users as &$user) {
            $user['profile'] = $profileModel->getByUserId($user['id']);
            $user['roles']   = $userRoleModel->getRolesByUserId($user['id']);
        }

        return $users;
    }

    /**
     * Retorna un usuario con perfil y roles (para showId).
     */
    public function getUserWithProfile(int $id)
    {
        $user = $this->select('user.id, user.username, user.email, user.status_id, user.last_login, user.created_at, user.updated_at, user_status.name as status_name')
                     ->join('user_status', 'user_status.id = user.status_id')
                     ->where('user.id', $id)
                     ->first();

        if (!$user) {
            return null;
        }

        $profileModel = new \App\Models\ProfileModel();
        $userRoleModel = new \App\Models\UserRoleModel();

        $user['profile'] = $profileModel->getByUserId($id);
        $user['roles']   = $userRoleModel->getRolesByUserId($id);

        return $user;
    }
```

### Paso 4.3: Crear el Controlador de Usuarios

Crea  **`app/Controllers/Security/UserController.php`** :

```PHP
<?php

namespace App\Controllers\Security;

use App\Controllers\BaseApiController;
use App\Models\UserModel;
use App\Models\ProfileModel;
use App\Models\UserRoleModel;

class UserController extends BaseApiController
{
    protected UserModel $userModel;
    protected ProfileModel $profileModel;
    protected UserRoleModel $userRoleModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->profileModel = new ProfileModel();
        $this->userRoleModel = new UserRoleModel();
    }

    /**
     * GET /api/users
     */
    public function showAll()
    {
        $users = $this->userModel->getAllWithProfile();
        return $this->respondSuccess($users, 'Usuarios obtenidos correctamente.');
    }

    /**
     * GET /api/users/{id}
     */
    public function showId($id = null)
    {
        $user = $this->userModel->getUserWithProfile($id);

        if (!$user) {
            return $this->respondError('Usuario no encontrado.', 404);
        }

        return $this->respondSuccess($user, 'Usuario obtenido correctamente.');
    }

    /**
     * POST /api/users
     * Crea usuario + perfil + roles en una transacción.
     */
    public function create()
    {
        $input = $this->request->getJSON(true);

        // Validaciones básicas
        $required = ['username', 'email', 'password', 'status_id', 'first_name', 'last_name', 'document_type_id', 'document_number'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                return $this->respondError("El campo {$field} es obligatorio.", 400);
            }
        }

        if (empty($input['role_ids']) || !is_array($input['role_ids'])) {
            return $this->respondError('Debe asignar al menos un rol.', 400);
        }

        // Verificar duplicados
        if ($this->userModel->where('username', $input['username'])->first()) {
            return $this->respondError('Ya existe un usuario con ese username.', 409);
        }
        if ($this->userModel->where('email', $input['email'])->first()) {
            return $this->respondError('Ya existe un usuario con ese email.', 409);
        }
        if ($this->profileModel->where('document_number', $input['document_number'])->first()) {
            return $this->respondError('Ya existe un perfil con ese número de documento.', 409);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // 1. Crear usuario
            $userId = $this->userModel->insert([
                'username'      => $input['username'],
                'email'         => $input['email'],
                'password_hash' => password_hash($input['password'], PASSWORD_DEFAULT),
                'status_id'     => (int) $input['status_id'],
            ]);

            // 2. Crear perfil
            $this->profileModel->insert([
                'user_id'          => $userId,
                'first_name'       => $input['first_name'],
                'last_name'        => $input['last_name'],
                'address'          => $input['address'] ?? null,
                'phone'            => $input['phone'] ?? null,
                'document_type_id' => (int) $input['document_type_id'],
                'document_number'  => $input['document_number'],
                'photo_url'        => $input['photo_url'] ?? null,
                'birth_date'       => $input['birth_date'] ?? null,
            ]);

            // 3. Asignar roles
            foreach ($input['role_ids'] as $roleId) {
                $this->userRoleModel->insert([
                    'user_id'   => $userId,
                    'role_id'   => (int) $roleId,
                    'status_id' => 1,
                ]);
            }

            $db->transCommit();

            $user = $this->userModel->getUserWithProfile($userId);
            return $this->respondSuccess($user, 'Usuario creado correctamente.', 201);

        } catch (\Exception $e) {
            $db->transRollback();
            return $this->respondError('Error al crear usuario: ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/users/{id}
     * Actualiza usuario + perfil. Los roles se gestionan aparte.
     */
    public function update($id = null)
    {
        $user = $this->userModel->find($id);
        if (!$user) {
            return $this->respondError('Usuario no encontrado.', 404);
        }

        $input = $this->request->getJSON(true);
        if (empty($input)) {
            return $this->respondError('No se enviaron datos para actualizar.', 400);
        }

        // Verificar duplicados si cambian
        if (isset($input['username']) && $input['username'] !== $user['username']) {
            if ($this->userModel->where('username', $input['username'])->where('id !=', $id)->first()) {
                return $this->respondError('Ya existe otro usuario con ese username.', 409);
            }
        }
        if (isset($input['email']) && $input['email'] !== $user['email']) {
            if ($this->userModel->where('email', $input['email'])->where('id !=', $id)->first()) {
                return $this->respondError('Ya existe otro usuario con ese email.', 409);
            }
        }
        if (isset($input['document_number'])) {
            $profile = $this->profileModel->getByUserId($id);
            if ($profile && $input['document_number'] !== $profile['document_number']) {
                if ($this->profileModel->where('document_number', $input['document_number'])->first()) {
                    return $this->respondError('Ya existe otro perfil con ese número de documento.', 409);
                }
            }
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // 1. Actualizar usuario
            $userData = [];
            if (isset($input['username']))      $userData['username'] = $input['username'];
            if (isset($input['email']))         $userData['email'] = $input['email'];
            if (isset($input['status_id']))     $userData['status_id'] = (int) $input['status_id'];
            if (!empty($input['password']))     $userData['password_hash'] = password_hash($input['password'], PASSWORD_DEFAULT);

            if (!empty($userData)) {
                $this->userModel->update($id, $userData);
            }

            // 2. Actualizar perfil
            $profileData = [];
            if (isset($input['first_name']))       $profileData['first_name'] = $input['first_name'];
            if (isset($input['last_name']))        $profileData['last_name'] = $input['last_name'];
            if (isset($input['address']))          $profileData['address'] = $input['address'];
            if (isset($input['phone']))            $profileData['phone'] = $input['phone'];
            if (isset($input['document_type_id'])) $profileData['document_type_id'] = (int) $input['document_type_id'];
            if (isset($input['document_number']))  $profileData['document_number'] = $input['document_number'];
            if (isset($input['photo_url']))        $profileData['photo_url'] = $input['photo_url'];
            if (isset($input['birth_date']))       $profileData['birth_date'] = $input['birth_date'];

            if (!empty($profileData)) {
                $this->profileModel->where('user_id', $id)->set($profileData)->update();
            }

            $db->transCommit();

            $updated = $this->userModel->getUserWithProfile($id);
            return $this->respondSuccess($updated, 'Usuario actualizado correctamente.');

        } catch (\Exception $e) {
            $db->transRollback();
            return $this->respondError('Error al actualizar usuario: ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/users/{id}
     * Soft delete: cambia status_id a 2 (inactivo).
     */
    public function delete($id = null)
    {
        $user = $this->userModel->find($id);
        if (!$user) {
            return $this->respondError('Usuario no encontrado.', 404);
        }

        $this->userModel->update($id, ['status_id' => 2]);

        return $this->respondSuccess(null, 'Usuario desactivado correctamente.');
    }
}
```

### Paso 4.4: Registrar las Rutas

Agrega en  **`app/Config/Routes.php`** :

```PHP
// Rutas de Usuarios (protegidas)
$routes->group('api/users', ['namespace' => 'App\Controllers\Security', 'filter' => 'auth'], function ($routes) {
    $routes->get('/', 'UserController::showAll');
    $routes->get('(:num)', 'UserController::showId/$1');
    $routes->post('/', 'UserController::create');
    $routes->put('(:num)', 'UserController::update/$1');
    $routes->delete('(:num)', 'UserController::delete/$1');
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

**B. Crear usuario (POST)**

| Campo   | Valor                              |
| ------- | ---------------------------------- |
| Método | `POST`                           |
| URL     | `http://localhost:8080/api/user` |
| Headers | `Content-Type: application/json` |

Body:

```JSON
{
    "username": "juan",
    "email": "juan@pqrs.com",
    "password": "juan123",
    "status_id": 1,
    "role_ids": [2],
    "first_name": "Juan",
    "last_name": "Pérez",
    "document_type_id": 1,
    "document_number": "12345678",
    "phone": "3001234567",
    "address": "Calle 123 #45-67"
}
```


## Checklist de Validación

### Usuarios

* `POST /api/users` crea usuario + perfil + roles en una transacción.
* Verificar en BD que se crearon registros en `user`, `profile` y `user_role`.
* La contraseña se guarda hasheada (empieza con `$2y$10$`).
* `POST /api/users` con `username` duplicado → `409`.
* `POST /api/users` con `email` duplicado → `409`.
* `POST /api/users` con `document_number` duplicado → `409`.
* `POST /api/users` sin `role_ids` → `400`.
* Si falla la creación del perfil, el usuario no se crea (rollback).
* `GET /api/users` lista usuarios con perfil y roles anidados.
* `GET /api/users/{id}` devuelve usuario con perfil y roles.
* `PUT /api/users/{id}` actualiza usuario y perfil.
* `DELETE /api/users/{id}` cambia `status_id` a 2.
* Login con usuario desactivado → `403 El usuario no está activo`.
