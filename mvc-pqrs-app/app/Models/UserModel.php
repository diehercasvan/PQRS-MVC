<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'user';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['username','email','password_hash','status_id','last_login'];

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
}
