<?php

namespace App\Libraries;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtService
{

  private string $key;
  private int $expire;

  public function __construct()
  {
    $this->key = env('JWT_SECRET') ?? 'MiClaveSuperSecretaParaPQRS2026_!@#';
    $this->expire = (int) (env('JWT_EXPIRE') ?? 3600);
  }

  public function generate(array $payLoad): string
  {
    $time = time();
    $tokenPayload = [
      'iat' => $time,
      'exp' => $time + $this->expire,
      'data' => $payLoad
    ];

    return JWT::encode($tokenPayload, $this->key, 'HS256');
  }

  public function validate(string $token)
  {
    try {
      $decoded = JWT::decode($token, new Key($this->key, 'HS256'));
      return (array)$decoded->data;
    } catch (\Exception $e) {
      return false;
    }
  }
  
  public function getBearerToken(): ?string
  {
    $request=\Config\Services::request();
    $authHeader=$request->getHeaderLine('Authorization');
    if(preg_match('/Bearer\s(\S+)/',$authHeader,$matches)){
      return $matches[1];
    }
    return null;
  }

}
