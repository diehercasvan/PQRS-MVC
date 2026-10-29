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