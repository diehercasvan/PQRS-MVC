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