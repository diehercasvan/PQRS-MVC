<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class BaseApiController extends Controller
{
    /**
     * Respuesta exitosa estandarizada.
     */
    protected function respondSuccess($data = null, string $message = 'OK', int $code = 200)
    {
        $response = ['status' => 'success', 'message' => $message];
        if ($data !== null) {
            $response['data'] = $data;
        }
        return $this->response->setStatusCode($code)->setJSON($response);
    }

    /**
     * Respuesta de error estandarizada.
     */
    protected function respondError(string $message, int $code = 400, $errors = null)
    {
        $response = ['status' => 'error', 'message' => $message];
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        return $this->response->setStatusCode($code)->setJSON($response);
    }
}