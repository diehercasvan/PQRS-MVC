<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Libraries\JwtService;


class AuthController extends BaseController
{
    /**
     * POST /api/auth/login
     * Body: { "username": "admin", "password": "admin123" }
     */
    protected JwtService $jwt;

    public function __construct()
    {
        $this->jwt=new JwtService();
    }

    public function login()
    {
        $input = $this->request->getJSON(true);

        // 1. Validar que vengan los datos
        if (empty($input['username']) || empty($input['password'])) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 'error',
                'message' => 'Usuario y contraseña son obligatorios.'
            ]);
        }

        $userModel = new UserModel();

        // 2. Buscar el usuario por username o email
        $user = $userModel->groupStart()
                            ->where('username', $input['username'])
                            ->orWhere('email', $input['username'])
                          ->groupEnd()
                          ->first();

        if (!$user) {
            return $this->response->setStatusCode(401)->setJSON([
                'status' => 'error',
                'message' => 'Credenciales inválidas.'
            ]);
        }

        // 3. Verificar que el usuario esté activo
        if ((int) $user['status_id'] !== 1) {
            return $this->response->setStatusCode(403)->setJSON([
                'status' => 'error',
                'message' => 'El usuario no está activo.'
            ]);
        }

        // 4. Verificar la contraseña
        if (!password_verify($input['password'], $user['password_hash'])) {
            return $this->response->setStatusCode(401)->setJSON([
                'status' => 'error',
                'message' => 'Credenciales inválidas.'
            ]);
        }

        // 5. Actualizar last_login
        $userModel->update($user['id'], ['last_login' => date('Y-m-d H:i:s')]);

        // 6. Generar el JWT
        $token = $this->jwt->generate([
            'id'       => $user['id'],
            'username' => $user['username'],
            'email'    => $user['email']
        ]);

        // 7. Retornar el token y datos básicos
        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Login exitoso.',
            'token' => $token,
            'user' => [
                'id'       => $user['id'],
                'username' => $user['username'],
                'email'    => $user['email']
            ]
        ]);
    }

    /**
     * GET /api/auth/me
     * Requiere: Header Authorization: Bearer <token>
     */
    public function me()
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

        $userModel = new UserModel();
        $user = $userModel->find($payload['id']);

        if (!$user) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => 'error',
                'message' => 'Usuario no encontrado.'
            ]);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'user' => [
                'id'       => $user['id'],
                'username' => $user['username'],
                'email'    => $user['email']
            ]
        ]);
    }
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
}