# Documentación del Proyecto: Sistema de Gestión PQRS

## 1. Objetivo General
Desarrollar una aplicación web robusta y escalable para la gestión de Peticiones, Quejas, Reclamos y Sugerencias (PQRS), implementando un sistema de control de acceso basado en roles (RBAC) dinámico que permita administrar usuarios, perfiles y permisos granulares sobre cada módulo del sistema.

## 2. Objetivos Específicos
*   **Implementar un sistema de autenticación seguro** mediante tokens (JWT) almacenados en el cliente, garantizando la protección de las rutas y la identidad del usuario.
*   **Diseñar una arquitectura de permisos dinámica**, permitiendo que un usuario con múltiples roles tenga accesos específicos (ver, crear, editar, eliminar, imprimir, descargar) sobre módulos determinados.
*   **Construir el módulo core de PQRS**, permitiendo a los usuarios radicar solicitudes con un número único de radicado, y a los administradores gestionar su ciclo de vida (estados) y trazabilidad (respuestas).
*   **Desarrollar una interfaz de usuario interactiva y responsiva**, utilizando Bootstrap 5 y comunicación asíncrona (Fetch API) para una experiencia de usuario fluida sin recargas de página.

---

## 3. Requerimientos Funcionales (RF)

### 3.1 Módulo de Seguridad y Accesos
*   **RF01 - Autenticación:** El sistema debe permitir el inicio de sesión mediante `username` o `email` y contraseña. Debe generar un token JWT y almacenarlo en el `localStorage`.
*   **RF02 - Gestión de Usuarios:** CRUD de usuarios, asignación de estados (activo, inactivo, suspendido) y gestión de su perfil (datos personales y documento).
*   **RF03 - Gestión de Roles:** CRUD de roles. Un usuario puede tener asignado más de un rol en el sistema.
*   **RF04 - Gestión de Módulos y Permisos:** Administración de los módulos del sistema y asignación dinámica de permisos por usuario, rol y módulo.
*   **RF05 - Control de Acceso (Middleware):** El backend debe validar el token en cada petición y verificar que el usuario tenga el permiso requerido para la acción solicitada (ej. validar permiso `create` antes de registrar un usuario).

### 3.2 Módulo de PQRS
*   **RF06 - Radicación:** Los usuarios autenticados pueden crear una PQRS (Petición, Queja, Reclamo, Sugerencia). El sistema genera automáticamente un número de radicado único.
*   **RF07 - Trazabilidad:** Cada PQRS permite registrar respuestas (comentarios) por parte del administrador y del usuario, guardando fecha y autor.
*   **RF08 - Ciclo de Vida:** Las PQRS transitan por estados predefinidos (Radicada, En Proceso, Resuelta, Cerrada, Rechazada).
*   **RF09 - Bandeja de Entrada (Admin):** Los usuarios con permisos administrativos pueden visualizar todas las PQRS, filtrarlas y cambiar su estado.
*   **RF10 - Historial (Usuario):** Los usuarios regulares solo pueden visualizar el estado e historial de sus propias PQRS.

---

## 4. Requerimientos No Funcionales (RNF)

*   **RNF01 - Seguridad:** Las contraseñas deben almacenarse encriptadas (Bcrypt). La comunicación debe ser mediante tokens JWT. El backend no debe confiar en validaciones hechas solo en el frontend.
*   **RNF02 - Usabilidad:** La interfaz debe ser responsiva (adaptable a móviles y escritorio) utilizando Bootstrap 5. Los mensajes de error y éxito deben ser claros para el usuario.
*   **RNF03 - Rendimiento:** Las consultas a la base de datos deben estar optimizadas mediante el uso de índices (ya definidos en el script SQL) y el uso de Fetch API para actualizaciones parciales (SPA-like).
*   **RNF04 - Escalabilidad:** El sistema de permisos debe permitir agregar nuevos permisos (ej. "exportar_pdf") sin necesidad de modificar la estructura de las tablas de la base de datos.
*   **RNF05 - Mantenibilidad:** El código backend debe seguir el patrón MVC estricto de CodeIgniter 4, con controladores organizados en carpetas y modelos dedicados por entidad.

---

## 5. Condiciones del Stack Tecnológico

Para garantizar el correcto funcionamiento y despliegue del proyecto, se establecen las siguientes condiciones técnicas:

### Backend
*   **Framework:** CodeIgniter 4 (última versión estable).
*   **Lenguaje:** PHP 8.1 o superior.
*   **Arquitectura:** Patrón MVC (Modelo-Vista-Controlador).
*   **Gestor de Dependencias:** Composer.
*   **Autenticación:** Librería JWT (JSON Web Tokens) para la generación y validación de tokens.
*   **Servidor Web:** Apache (XAMPP/WAMP) o Nginx. El DocumentRoot debe apuntar a la carpeta `public` del proyecto.

### Base de Datos
*   **Motor:** MySQL / MariaDB.
*   **Codificación:** `utf8mb4` para soporte completo de caracteres especiales.
*   **Integridad:** Uso estricto de Llaves Foráneas (Foreign Keys) y restricciones `ON DELETE CASCADE` / `SET NULL` según corresponda.
*   **Índices:** Creación de índices en columnas de búsqueda frecuente (username, email, radicado) y tablas pivote para optimizar consultas.

### Frontend
*   **Lenguaje:** HTML5, CSS3, JavaScript (ES6+).
*   **Librería CSS:** Bootstrap 5 (para grillas, componentes y utilidades).
*   **Iconografía:** Bootstrap Icons.
*   **Comunicación:** Fetch API (nativo de JS) para el consumo de los endpoints del backend.
*   **Gestión de Sesión:** `localStorage` para el almacenamiento del token JWT y datos del usuario/perfil.
*   **Estructura:** Vistas modulares (componentes) que se cargan dinámicamente en un contenedor principal.