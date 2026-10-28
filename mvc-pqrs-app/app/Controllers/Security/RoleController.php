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