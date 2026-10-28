<?php

namespace App\Models;

use CodeIgniter\Model;

class RoleModel extends Model
{
    protected $table            = 'role';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    //Retornar array para facilitar el JSON
    protected $returnType       = 'array'; 
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    //Campos de la tabla 
    protected $allowedFields    = ['name','description','is_active'];

    protected bool $allowEmptyInserts = false;

    //Timestamps automatica
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

     /**
     * Retorna todos los roles activos e inactivos.
     */
    public function getAll()
    {
        return $this->orderBy('id', 'ASC')->findAll();
    }

    /**
     * Retorna solo roles activos (útil para selects en el frontend).
     */
    public function getActive()
    {
        return $this->where('is_active', 1)->orderBy('name', 'ASC')->findAll();
    }
}
