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