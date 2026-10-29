# 🚀 Taller Práctico: JWT, APIs REST y Códigos de Estado

¡Bienvenido a este taller práctico sobre **JSON Web Tokens (JWT)** y **APIs REST**! En esta guía aprenderás desde cero los conceptos teóricos fundamentales sobre peticiones HTTP, códigos de estado estructurados y la anatomía interna de un JWT. Además, implementarás un backend funcional con un CRUD simulado guardado en un archivo `JSON`, con código disponible tanto para **Node.js (Express)** como para **PHP (Nativo)**, finalizando con la verificación de todas las rutas en **Postman**.

---

## 📋 Tabla de Contenidos

1. [Módulo 1: Fundamentos de APIs REST, Métodos y Estados](#módulo-1-fundamentos-de-apis-rest-métodos-y-estados)
2. [Módulo 2: Anatomía de un JWT (Estructura, Partes y Firma)](#módulo-2-anatomía-de-un-jwt-json-web-token)
3. [Módulo 3A: Taller Práctico en Node.js + Express](#módulo-3a-taller-práctico-en-nodejs--express)
4. [Módulo 3B: Taller Práctico en PHP Nativo](#módulo-3b-taller-práctico-en-php-nativo)
5. [Módulo 4: Guía de Pruebas en Postman](#módulo-4-guía-de-pruebas-en-postman)

---

## Módulo 1: Fundamentos de APIs REST, Métodos y Estados

Una API REST se comunica mediante el protocolo HTTP. Cada interacción cliente-servidor utiliza un **Método HTTP** e informa el resultado mediante un **Código de Estado HTTP** acompañado de una respuesta estructurada en formato **JSON**.

### Métodos HTTP Clave

* **`GET`**: Obtiene y recupera recursos (operación de lectura).
* **`POST`**: Envía datos para crear un nuevo recurso.
* **`PUT` / `PATCH`**: Actualiza un recurso existente en su totalidad o parcialmente.
* **`DELETE`**: Remueve un recurso.

### Códigos de Estado y Estructuras JSON de Respuesta

Cada respuesta del servidor debe acompañarse de una estructura clara que informe lo sucedido:

* **`200 OK` (Operación exitosa / Lectura / Actualización):**

  ```json
  {
    "success": true,
    "status": 200,
    "message": "Operación realizada con éxito",
    "data": { "id": 1, "titulo": "Aprender APIs" }
  }
  ```
* **`201 Created` (Recurso creado con éxito):**

  ```json
  {
    "success": true,
    "status": 201,
    "message": "Recurso creado correctamente",
    "data": { "id": 2, "titulo": "Configurar entorno" }
  }
  ```
* **`400 Bad Request` (Datos incompletos o sintaxis errónea):**

  ```json
  {
    "success": false,
    "status": 400,
    "error": "Bad Request",
    "message": "El campo 'titulo' es obligatorio"
  }
  ```
* **`401 Unauthorized` (Falta de token, token inválido o expirado):**

  ```json
  {
    "success": false,
    "status": 401,
    "error": "Unauthorized",
    "message": "Token de autenticación no proporcionado o inválido"
  }
  ```
* **`403 Forbidden` (Autenticado pero sin permisos):**

  ```json
  {
    "success": false,
    "status": 403,
    "error": "Forbidden",
    "message": "No tienes privilegios para realizar esta acción"
  }
  ```
* **`404 Not Found` (Recurso no encontrado):**

  ```json
  {
    "success": false,
    "status": 404,
    "error": "Not Found",
    "message": "El recurso solicitado no existe"
  }
  ```

---

## Módulo 2: Anatomía de un JWT (JSON Web Token)

Un **JSON Web Token (RFC 7519)** es un estándar abierto para transmitir información de forma segura entre partes como un objeto JSON compactado y firmado digitalmente.

### Las 3 Partes de un JWT

Un token se compone de tres partes separadas por puntos (`.`): `header.payload.signature`

```text
eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VySWQiOjEsInJvbGUiOiJhZG1pbiIsImlhdCI6MTY4MDAwMDAwMCwiZXhwIjoxNjgwMDAzNjAwfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c
```

1. **Header (Cabecera):** Especifica el algoritmo de firma (ej. `HS256`) y el tipo de token (`JWT`).

   ```json
   {
     "alg": "HS256",
     "typ": "JWT"
   }
   ```
2. **Payload (Carga útil):** Contiene los *claims* (declaraciones con la información del usuario y metadatos de control):

   * `iat` (*Issued At*): Fecha/hora de emisión (en timestamp UNIX).
   * `exp` (*Expiration Time*): Fecha/hora de expiración.
   * *Datos personalizados:* ID, usuario, rol, etc.

   ```json
   {
     "userId": 1,
     "username": "admin",
     "role": "administrador",
     "iat": 1711900000,
     "exp": 1711903600
   }
   ```
3. **Signature (Firma):** Se genera combinando el Header codificado en Base64Url, el Payload codificado en Base64Url y una **clave secreta** que permanece únicamente en el servidor mediante el algoritmo indicado:

   $$
   \text{Firma} = \text{HMACSHA256}(\text{base64UrlEncode}(Header) + "." + \text{base64UrlEncode}(Payload), \text{secretKey})
   $$

   *Propósito:* Garantiza que los datos no hayan sido alterados durante el tránsito. Si alguien modifica el payload, la firma deja de coincidir.

---

## Módulo 3A: Taller Práctico en Node.js + Express

### 1. Inicialización

```bash
mkdir api-jwt-node
cd api-jwt-node
npm init -y
npm install express jsonwebtoken cors
```

### 2. Archivo de datos inicial (`data.json`)

Crea el archivo `data.json` en la raíz del proyecto:

```json
[
  { "id": 1, "titulo": "Aprender JWT en Node.js", "completado": false },
  { "id": 2, "titulo": "Configurar Postman", "completado": true }
]
```

### 3. Código del Servidor (`server.js`)

```javascript
const express = require('express');
const jwt = require('jsonwebtoken');
const fs = require('fs');
const path = require('path');
const cors = require('cors');

const app = express();
app.use(express.json());
app.use(cors());

const SECRET_KEY = 'mi_clave_secreta_ultrasegura_node';
const DATA_FILE = path.join(__dirname, 'data.json');

const leerDatos = () => {
    if (!fs.existsSync(DATA_FILE)) return [];
    const data = fs.readFileSync(DATA_FILE, 'utf-8');
    return JSON.parse(data);
};

const escribirDatos = (data) => {
    fs.writeFileSync(DATA_FILE, JSON.stringify(data, null, 2), 'utf-8');
};

// LOGIN -> Genera el Token JWT
app.post('/api/login', (req, res) => {
    const { username, password } = req.body;

    if (username === 'admin' && password === '123456') {
        const payload = { userId: 1, username: 'admin', role: 'administrador' };
        const token = jwt.sign(payload, SECRET_KEY, { expiresIn: '1h' });

        return res.status(200).json({
            success: true,
            status: 200,
            message: 'Autenticación exitosa. Token generado.',
            token: token
        });
    }

    return res.status(401).json({
        success: false,
        status: 401,
        error: 'Unauthorized',
        message: 'Credenciales inválidas. Usa username: admin / password: 123456'
    });
});

// Middleware de verificación de Token
const verificarToken = (req, res, next) => {
    const authHeader = req.headers['authorization'];
    if (!authHeader) {
        return res.status(401).json({
            success: false,
            status: 401,
            error: 'Unauthorized',
            message: 'Cabecera de autorización (Authorization) faltante'
        });
    }

    const token = authHeader.split(' ')[1];
    if (!token) {
        return res.status(401).json({
            success: false,
            status: 401,
            error: 'Unauthorized',
            message: 'Token no encontrado en la cabecera'
        });
    }

    jwt.verify(token, SECRET_KEY, (err, decoded) => {
        if (err) {
            return res.status(401).json({
                success: false,
                status: 401,
                error: 'Unauthorized',
                message: 'Token inválido o expirado',
                detalle: err.message
            });
        }
        req.user = decoded;
        next();
    });
};

// CRUD SOBRE JSON
app.get('/api/tareas', verificarToken, (req, res) => {
    const tareas = leerDatos();
    res.status(200).json({
        success: true,
        status: 200,
        mensaje: `Acceso concedido para usuario: ${req.user.username}`,
        total: tareas.length,
        data: tareas
    });
});

app.post('/api/tareas', verificarToken, (req, res) => {
    const { titulo } = req.body;
    if (!titulo) {
        return res.status(400).json({
            success: false,
            status: 400,
            error: 'Bad Request',
            message: 'El campo "titulo" es obligatorio'
        });
    }

    const tareas = leerDatos();
    const nuevaTarea = {
        id: tareas.length > 0 ? tareas[tareas.length - 1].id + 1 : 1,
        titulo,
        completado: false
    };

    tareas.push(nuevaTarea);
    escribirDatos(tareas);

    res.status(201).json({
        success: true,
        status: 201,
        message: 'Tarea creada con éxito',
        data: nuevaTarea
    });
});

app.put('/api/tareas/:id', verificarToken, (req, res) => {
    const id = parseInt(req.params.id);
    const { titulo, completado } = req.body;

    let tareas = leerDatos();
    const index = tareas.findIndex(t => t.id === id);

    if (index === -1) {
        return res.status(404).json({
            success: false,
            status: 404,
            error: 'Not Found',
            message: `No se encontró la tarea con ID ${id}`
        });
    }

    tareas[index] = {
        ...tareas[index],
        titulo: titulo !== undefined ? titulo : tareas[index].titulo,
        completado: completado !== undefined ? completado : tareas[index].completado
    };

    escribirDatos(tareas);

    res.status(200).json({
        success: true,
        status: 200,
        message: 'Tarea actualizada correctamente',
        data: tareas[index]
    });
});

app.delete('/api/tareas/:id', verificarToken, (req, res) => {
    const id = parseInt(req.params.id);
    let tareas = leerDatos();
    const index = tareas.findIndex(t => t.id === id);

    if (index === -1) {
        return res.status(404).json({
            success: false,
            status: 404,
            error: 'Not Found',
            message: `No se encontró la tarea con ID ${id}`
        });
    }

    const tareaEliminada = tareas.splice(index, 1);
    escribirDatos(tareas);

    res.status(200).json({
        success: true,
        status: 200,
        message: 'Tarea eliminada con éxito',
        data: tareaEliminada[0]
    });
});

const PORT = 3000;
app.listen(PORT, () => console.log(`Servidor Node.js corriendo en http://localhost:${PORT}`));
```

### 4. Ejecución

```bash
node server.js
```

---

## Módulo 3B: Taller Práctico en PHP Nativo

### 1. Inicialización e instalación de dependencias

```bash
mkdir api-jwt-php
cd api-jwt-php
composer require firebase/php-jwt
```

### 2. Archivo de datos inicial (`data.json`)

Crea el archivo `data.json` en la raíz del proyecto:

```json
[
  { "id": 1, "titulo": "Aprender JWT en PHP", "completado": false },
  { "id": 2, "titulo": "Configurar Postman", "completado": true }
]
```

### 3. Código del Servidor (`index.php`)

```php
<?php
require __DIR__ . '/vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

define('SECRET_KEY', 'mi_clave_secreta_ultrasegura_php');
$data_file = __DIR__ . '/data.json';

function leerDatos($file) {
    if (!file_exists($file)) return [];
    $contenido = file_get_contents($file);
    return json_decode($contenido, true) ?? [];
}

function escribirDatos($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function enviarRespuesta($status_code, $data) {
    http_response_code($status_code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// LOGIN -> Genera el JWT
if ($method === 'POST' && (strpos($request_uri, '/login') !== false)) {
    $input = json_decode(file_get_contents('php://input'), true);
    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';

    if ($username === 'admin' && $password === '123456') {
        $issuedAt = time();
        $payload = [
            'iat' => $issuedAt,
            'exp' => $issuedAt + 3600,
            'data' => [
                'userId' => 1,
                'username' => 'admin',
                'role' => 'administrador'
            ]
        ];

        $jwt = JWT::encode($payload, SECRET_KEY, 'HS256');

        enviarRespuesta(200, [
            "success" => true,
            "status" => 200,
            "message" => "Autenticación exitosa. Token generado.",
            "token" => $jwt
        ]);
    }

    enviarRespuesta(401, [
        "success" => false,
        "status" => 401,
        "error" => "Unauthorized",
        "message" => "Credenciales inválidas. Usa username: admin / password: 123456"
    ]);
}

// VERIFICACIÓN DE JWT
$headers = apache_request_headers();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

if (!$authHeader) {
    enviarRespuesta(401, [
        "success" => false,
        "status" => 401,
        "error" => "Unauthorized",
        "message" => "Cabecera de autorización (Authorization) faltante"
    ]);
}

list($jwt) = sscanf($authHeader, 'Bearer %s');

if (!$jwt) {
    enviarRespuesta(401, [
        "success" => false,
        "status" => 401,
        "error" => "Unauthorized",
        "message" => "Formato de token inválido. Use: Bearer <token>"
    ]);
}

try {
    $decoded = JWT::decode($jwt, new Key(SECRET_KEY, 'HS256'));
    $usuarioActual = $decoded->data;
} catch (Exception $e) {
    enviarRespuesta(401, [
        "success" => false,
        "status" => 401,
        "error" => "Unauthorized",
        "message" => "Token inválido o expirado",
        "detalle" => $e->getMessage()
    ]);
}

// ROUTING CRUD /tareas
if (strpos($request_uri, '/tareas') !== false) {
    preg_match('/\/tareas\/([0-9]+)/', $request_uri, $matches);
    $id = isset($matches[1]) ? intval($matches[1]) : null;
    $tareas = leerDatos($data_file);

    switch ($method) {
        case 'GET':
            enviarRespuesta(200, [
                "success" => true,
                "status" => 200,
                "mensaje" => "Acceso concedido para usuario: {$usuarioActual->username}",
                "total" => count($tareas),
                "data" => $tareas
            ]);
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            $titulo = trim($input['titulo'] ?? '');

            if (empty($titulo)) {
                enviarRespuesta(400, [
                    "success" => false,
                    "status" => 400,
                    "error" => "Bad Request",
                    "message" => "El campo 'titulo' es obligatorio"
                ]);
            }

            $nuevoId = count($tareas) > 0 ? max(array_column($tareas, 'id')) + 1 : 1;
            $nuevaTarea = [
                "id" => $nuevoId,
                "titulo" => $titulo,
                "completado" => false
            ];

            $tareas[] = $nuevaTarea;
            escribirDatos($data_file, $tareas);

            enviarRespuesta(201, [
                "success" => true,
                "status" => 201,
                "message" => "Tarea creada con éxito",
                "data" => $nuevaTarea
            ]);
            break;

        case 'PUT':
            if (!$id) {
                enviarRespuesta(400, [
                    "success" => false,
                    "status" => 400,
                    "error" => "Bad Request",
                    "message" => "Se requiere ID de la tarea"
                ]);
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $encontrado = false;

            foreach ($tareas as &$tarea) {
                if ($tarea['id'] === $id) {
                    if (isset($input['titulo'])) $tarea['titulo'] = trim($input['titulo']);
                    if (isset($input['completado'])) $tarea['completado'] = filter_var($input['completado'], FILTER_VALIDATE_BOOLEAN);
                    $encontrado = true;
                    $tareaActualizada = $tarea;
                    break;
                }
            }
            unset($tarea);

            if (!$encontrado) {
                enviarRespuesta(404, [
                    "success" => false,
                    "status" => 404,
                    "error" => "Not Found",
                    "message" => "No se encontró la tarea con ID {$id}"
                ]);
            }

            escribirDatos($data_file, $tareas);
            enviarRespuesta(200, [
                "success" => true,
                "status" => 200,
                "message" => "Tarea actualizada correctamente",
                "data" => $tareaActualizada
            ]);
            break;

        case 'DELETE':
            if (!$id) {
                enviarRespuesta(400, [
                    "success" => false,
                    "status" => 400,
                    "error" => "Bad Request",
                    "message" => "Se requiere ID de la tarea"
                ]);
            }

            $encontradoIndex = -1;
            foreach ($tareas as $index => $tarea) {
                if ($tarea['id'] === $id) {
                    $encontradoIndex = $index;
                    break;
                }
            }

            if ($encontradoIndex === -1) {
                enviarRespuesta(404, [
                    "success" => false,
                    "status" => 404,
                    "error" => "Not Found",
                    "message" => "No se encontró la tarea con ID {$id}"
                ]);
            }

            $tareaEliminada = array_splice($tareas, $encontradoIndex, 1);
            escribirDatos($data_file, $tareas);

            enviarRespuesta(200, [
                "success" => true,
                "status" => 200,
                "message" => "Tarea eliminada con éxito",
                "data" => $tareaEliminada[0]
            ]);
            break;
    }
} else {
    enviarRespuesta(404, [
        "success" => false,
        "status" => 404,
        "error" => "Not Found",
        "message" => "Ruta no encontrada"
    ]);
}
```

### 4. Ejecución del Servidor PHP

```bash
php -S localhost:8000
```

---

## Módulo 4: Guía de Pruebas en Postman

Sustituye la URL Base según la tecnología que estés probando:

* **Node.js:** `http://localhost:3000/api`
* **PHP:** `http://localhost:8000/index.php` (o la ruta correspondiente si usas reescritura de URLs).

---

### 1. Iniciar Sesión (Autenticación)

* **Método:** `POST`
* **URL:** `{{BASE_URL}}/login`
* **Body** (*raw* -> *JSON*):
  ```json
  {
    "username": "admin",
    "password": "123456"
  }
  ```
* **Respuesta Esperada (`200 OK`):**
  Recibirás un objeto JSON con la propiedad `token`. Copia dicho valor.

---

### 2. Obtener la lista de Tareas (GET Protegido)

* **Método:** `GET`
* **URL:** `{{BASE_URL}}/tareas`
* **Headers:**
  * Key: `Authorization`
  * Value: `Bearer <PEGA_TU_TOKEN_AQUI>`
* **Respuesta Esperada (`200 OK`):** Retorna el array de tareas almacenadas en `data.json`.
* **Prueba de Error (`401 Unauthorized`):** Elimina la cabecera `Authorization` o edita el token alterando un carácter para verificar que el middleware rechaza la solicitud.

---

### 3. Crear una nueva Tarea (POST Protegido)

* **Método:** `POST`
* **URL:** `{{BASE_URL}}/tareas`
* **Headers:**
  * Key: `Authorization`
  * Value: `Bearer <PEGA_TU_TOKEN_AQUI>`
* **Body** (*raw* -> *JSON*):
  ```json
  {
    "titulo": "Implementar pruebas de API"
  }
  ```
* **Respuesta Esperada (`201 Created`):** Retorna la tarea con su ID autogenerado.

---

### 4. Actualizar una Tarea (PUT Protegido)

* **Método:** `PUT`
* **URL:** `{{BASE_URL}}/tareas/1`
* **Headers:**
  * Key: `Authorization`
  * Value: `Bearer <PEGA_TU_TOKEN_AQUI>`
* **Body** (*raw* -> *JSON*):
  ```json
  {
    "completado": true
  }
  ```
* **Respuesta Esperada (`200 OK`):** Retorna el objeto actualizado.

---

### 5. Eliminar una Tarea (DELETE Protegido)

* **Método:** `DELETE`
* **URL:** `{{BASE_URL}}/tareas/2`
* **Headers:**
  * Key: `Authorization`
  * Value: `Bearer <PEGA_TU_TOKEN_AQUI>`
* **Respuesta Esperada (`200 OK`):** Elimina el registro del archivo `data.json` y devuelve el ítem removido.
