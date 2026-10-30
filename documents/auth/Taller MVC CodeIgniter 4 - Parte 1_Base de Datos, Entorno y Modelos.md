```
// Rutas de Autenticación
$routes->group('api/auth', ['namespace' => 'App\Controllers\Auth'], function ($routes) {
    $routes->post('login', 'AuthController::login');
    $routes->get('me', 'AuthController::me');
});
```

# **Taller Práctico: Creación de un Proyecto MVC desde Cero con CodeIgniter 4**

# **Parte 1: Instalación, Configuración de Entorno, Base de Datos y Creación de Modelos**

## **Introducción**

Este taller detalla el procedimiento paso a paso para inicializar un proyecto web bajo el patrón **MVC (Modelo-Vista-Controlador)** utilizando CodeIgniter 4, configurando el entorno de desarrollo, estableciendo la conexión con la base de datos `pqrs_system` y generando los modelos iniciales para la gestión de usuarios, roles y el sistema de PQRS.

## **Paso 1: Crear el Proyecto CodeIgniter 4**

La forma recomendada por la documentación oficial es utilizando **Composer**.

1. Abre tu terminal o línea de comandos.
2. Ubícate en la carpeta donde quieras alojar el proyecto (por ejemplo, `htdocs` en XAMPP o `www` en WAMP).
3. Ejecuta el siguiente comando: **composer create-project codeigniter4/appstarter pqrs-app, esto creará una carpeta llamada `pqrs-app` con toda la estructura del framework y sus dependencias instaladas.
4. Una vez finalizada la instalación, entra a la carpeta del proyecto: **cd pqrs-app**

**Nota importante sobre el servidor web:** CodeIgniter 4 ha movido el archivo `index.php` a la carpeta `public`. Por seguridad, debes configurar tu servidor (Apache/Nginx) para que apunte directamente a `pqrs-app/public` como raíz del sitio, y no a la carpeta raíz del proyecto.

## **Paso 2: Configurar el Entorno y la Base de Datos**

CodeIgniter utiliza un archivo `.env` para las configuraciones sensibles. Vamos a crearlo y a configurar la conexión a tu base de datos `pqrs_system`.

1. En la raíz del proyecto (`pqrs-app`), busca el archivo llamado `env` (sin punto al inicio).
2. Cópialo o renómbralo a `.env`.
3. Abre el archivo `.env` y asegúrate de descomentar (quitar el `#`) y editar las siguientes líneas para que coincidan con tu entorno local:
   * `CI_ENVIRONMENT = development`: Esto activa la barra de depuración (Debug Toolbar) y muestra errores detallados, muy útil mientras desarrollamos.
   * `database.default.database = pqrs_system`: Asegúrate de que coincida con el nombre exacto de tu script o base de datos.
   * `database.default.username = root`
   * `database.default.password =` : Si tu usuario `root` de MySQL tiene contraseña, escríbela aquí. Si no, déjalo vacío.

## **Paso 3: Verificar la Conexión**

Para comprobar que todo está bien antes de empezar a programar, podemos hacer una prueba rápida desde la terminal usando la herramienta `spark` que trae CodeIgniter 4\.

Ejecuta el siguiente comando dentro de la carpeta del proyecto:

```Shell
php spark db:table
```

Si la conexión es exitosa, verás un listado de todas las tablas de tu base de datos `pqrs_system` (como `user`, `role`, `module`, `profile`, `pqrs`, etc.). Esto confirma que el framework ya puede comunicarse con tu base de datos.

## **Paso 4: Crear los Modelos del Sistema (Models)**

Vamos a crear los modelos para las tablas directamente relacionadas con la seguridad, el control de acceso y el núcleo del sistema PQRS.

Puedes crear los archivos manualmente en `app/Models/` o usar el comando `spark`. Se recomienda usar `spark` para que se generen con la estructura correcta:

* **Modelos de Seguridad y Accesos:**

  ```Shell
  php spark make:model UserModel
  php spark make:model RoleModel
  php spark make:model UserStatusModel
  php spark make:model UserRoleModel
  ```
* **Modelos de Módulos y Permisos:**

  ```Shell
  php spark make:model ModuleModel
  php spark make:model ModuleRoleModel
  ```
* **Modelos de Perfiles y Tipos:**

  ```Shell
  php spark make:model ProfileModel
  php spark make:model DocumentTypeModel
  ```

  **Modelos del Módulo PQRS:**

  ```Shell
  php spark make:model PqrsModel
  php spark make:model PqrsStatusModel
  php spark make:model PqrsTypeModel
  php spark make:model PqrsResponseModel
  ```

## **Resumen de Modelos Generados**

| Categoría | Nombre del Modelo       | Archivo Generado                     | Tabla Asociada       |
| :--------- | :---------------------- | :----------------------------------- | :------------------- |
| Seguridad  | `RoleModel`           | `app/Models/RoleModel.php`         | `role`             |
| Seguridad  | `UserModel`           | `app/Models/UserModel.php`         | `user`             |
| Seguridad  | `UserStatusModel`     | `app/Models/UserStatusModel.php`   | `user_status`      |
| Seguridad  | `UserRoleModel`       | `app/Models/UserRoleModel.php`     | `user_role`        |
| Permisos   | `ModuleModel`         | `app/Models/ModuleModel.php`       | `module`           |
| Permisos   | `ModuleRoleUserModel` | `app/Models/ModuleRoleModel.php`   | `module_role_user` |
| Perfiles   | `ProfileModel`        | `app/Models/ProfileModel.php`      | `profile`          |
| Perfiles   | `DocumentTypeModel`   | `app/Models/DocumentTypeModel.php` | `document_type`    |
| PQRS       | `PqrsModel`           | `app/Models/PqrsModel.php`         | `pqrs`             |
| PQRS       | `PqrsStatusModel`     | `app/Models/PqrsStatusModel.php`   | `pqrs_status`      |
| PQRS       | `PqrsTypeModel`       | `app/Models/PqrsTypeModel.php`     | `pqrs_type`        |
| PQRS       | `PqrsResponseModel`   | `app/Models/PqrsResponseModel.php` | `pqrs_response`    |

## **Paso 5: Ajustar el esquema de Datos (Mysql)**

Puedes ejecutar este script directamente en `phpMyAdmin` o en la consola de MySQL. Como es una base de datos en desarrollo y aún no tiene datos críticos, he incluido las sentencias para recrear esas tablas con la estructura correcta.

```MySQL
USE `pqrs_system`;
-- --------------------------------------------------------
-- 1. Ajuste tabla `pqrs`: renombrar `radicado` a `tracking_number`
-- --------------------------------------------------------
ALTER TABLE `pqrs` 
CHANGE COLUMN `radicado` `tracking_number` VARCHAR(30) NOT NULL COMMENT 'Número de radicado único';

-- Nota: El índice UNIQUE se mantiene automáticamente con el cambio de nombre en MySQL/MariaDB.

-- --------------------------------------------------------
-- 2. Ajuste tabla `pqrs_type`: agregar timestamps
-- --------------------------------------------------------
ALTER TABLE `pqrs_type`
ADD COLUMN `created_at` timestamp NOT NULL DEFAULT current_timestamp() AFTER `description`,
ADD COLUMN `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() AFTER `created_at`;

-- --------------------------------------------------------
-- 3. Ajuste tabla `pqrs_status`: agregar timestamps
-- --------------------------------------------------------
ALTER TABLE `pqrs_status`
ADD COLUMN `created_at` timestamp NOT NULL DEFAULT current_timestamp() AFTER `color`,
ADD COLUMN `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() AFTER `created_at`;
```

## **Paso 6: Actualizar los Modelos en CI4**

Ahora que la base de datos está ajustada, debemos reflejar estos cambios en los modelos de CodeIgniter 4.

**Archivo: `app/Models/PqrsModel.php`**
Debemos cambiar el nombre del campo en `$allowedFields` y, si tenías alguna lógica, actualizarla. Si aún no has creado este modelo, créalo con `php spark make:model PqrsModel` y pega lo siguiente:

```PHP
<?php

namespace App\Models;
use CodeIgniter\Model;

class PqrsModel extends Model
{
    protected $table            = 'pqrs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
  
    // Cambiamos 'radicado' por 'tracking_number'
    protected $allowedFields    = ['tracking_number', 'user_id', 'pqrs_type_id', 'pqrs_status_id', 'assigned_to', 'subject', 'description'];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
```

## **Parte 2**

## **Módulo de Seguridad - Backend con CodeIgniter 4 y JWT**

Construir el sistema de autenticación y autorización del proyecto PQRS, permitiendo que los usuarios inicien sesión, reciban un token JWT y accedan a rutas protegidas. Cada paso está diseñado para ser probado con Postman antes de avanzar al frontend.

---

## Requisitos Previos

- Proyecto CodeIgniter 4 instalado y configurado.
- Base de datos `pqrs_system` creada con el script SQL ajustado.
- Postman instalado.
- Servidor de CI4 corriendo (`php spark serve`).

## **Paso 1: Instalar la librería JWT**

Abre tu terminal en la raíz del proyecto y ejecuta:

```Shell
composer require firebase/php-jwt
```

## **Paso 2: Configurar Variables de Entorno**

```Properties
JWT_SECRET = "MiClaveSuperSecretaParaPQRS2026_!@#"
JWT_EXPIRE = 3600
```

| Variable       | Descripción                                                                     |
| -------------- | -------------------------------------------------------------------------------- |
| `JWT_SECRET` | Clave secreta para firmar los tokens. En producción debe ser larga y aleatoria. |
| `JWT_EXPIRE` | Tiempo de vida del token en segundos (3600 = 1 hora).                            |

## **Paso 3: Crear el Helper de JWT**

Crea el archivo  **`app/Helpers/JwtHelper.php`** :

```PHP
<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if (!function_exists('generateJWT')) {
    /**
     * Genera un token JWT con los datos del usuario.
     */
    function generateJWT(array $payload): string
    {
        $key = env('JWT_SECRET');
        $time = time();
        $expire = $time + (int) env('JWT_EXPIRE', 3600);

        $tokenPayload = [
            'iat' => $time,          // Emitido en
            'exp' => $expire,        // Expira en
            'data' => $payload       // Datos del usuario (id, username, roles, etc.)
        ];

        return JWT::encode($tokenPayload, $key, 'HS256');
    }
}

if (!function_exists('validateJWT')) {
    /**
     * Valida el token y retorna el payload, o retorna false si es inválido.
     */
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
    /**
     * Extrae el token del header Authorization: Bearer <token>
     */
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
```

## **Paso 4: Registrar el Helper en BaseController**

Edita **`app/Controllers/BaseController.php`** y agrega `'jwt'` al array `$helpers`:

```PHP
protected $helpers = ['jwt'];
```

## **Paso 5: Crear el Controlador de Autenticación**

Crea la carpeta `app/Controllers/Auth/` y dentro el archivo  **`AuthController.php`** :

```PHP
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
}
```

## **Paso 6: Crear las Rutas**

Edita **`app/Config/Routes.php`** y agrega:

```PHP
// Rutas de Autenticación
$routes->group('api/auth', ['namespace' => 'App\Controllers\Auth'], function ($routes) {
    $routes->post('login', 'AuthController::login');
    $routes->get('me', 'AuthController::me');
});
```

## **Paso 7: Actualizar el Password del Admin**

El hash que viene en el script SQL es de ejemplo. Ejecuta en phpMyAdmin o consola MySQL:

```MySQL
USE pqrs_system;
UPDATE `user` 
SET `password_hash` = '$2y$10$9Kku5f1.7ud2NVDRmA0DuOZeRwDRodyxv436g.KrPYSXaUwqGmxli' 
WHERE `id` = 1;
```

Este hash corresponde a la contraseña `Admin123`.
Si prefieres generar uno nuevo, ejecuta en terminal: `php -r "echo password_hash('Admin123', PASSWORD_DEFAULT);"` y pega el resultado.

## **Paso 8: Probar en Postman**

Subir el servicios de la aplicación, recordar estar en la carpeta raiz del proyecto `mvc-pqrs-app`:

```PHP
php spark serve
```
