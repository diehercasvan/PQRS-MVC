<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if (!function_exists('generateJWT')) {
    function generateJWT(array $payload): string
    {
        $key = env('JWT_SECRET');
        $time = time();
        $expire = $time + (int) env('JWT_EXPIRE', 3600);

        $tokenPayload = [
            'iat' => $time,
            'exp' => $expire,
            'data' => $payload
        ];

        return JWT::encode($tokenPayload, $key, 'HS256');
    }
}

if (!function_exists('validateJWT')) {
    function validateJWT(string $token)
    {
        $key = env('JWT_SECRET');
        try {
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            return (array) $decoded->data;
        } catch (\Exception $e) {
            return false;
        }
    }
}

if (!function_exists('getBearerToken')) {
    function getBearerToken(): ?string
    {
        $request = \Config\Services::request();
        $authHeader = $request->getHeaderLine('Authorization');
        
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $matches[1];
        }
        return null;
    }
}