# Tablas de Consultas para Postman - Proyecto PQRS

Este documento contiene todas las rutas de la API, organizadas por módulo, con el método HTTP, headers y body necesarios para probar cada endpoint en Postman.

> **Nota:** Las rutas protegidas requieren el header `Authorization: Bearer <token>`. El token se obtiene del login (`0.1`).

---

## 0. Autenticación (sin token)

| # | Acción | Método | URL | Headers | Body (JSON) |
|---|--------|--------|-----|---------|-------------|
| 0.1 | Login | `POST` | `http://localhost:8080/api/auth/login` | `Content-Type: application/json` | `{"username": "admin", "password": "admin123"}` |
| 0.2 | Obtener mi usuario | `GET` | `http://localhost:8080/api/auth/me` | `Authorization: Bearer <token>` | — |

---

## 1. Roles (protegido)

| # | Acción | Método | URL | Headers | Body (JSON) |
|---|--------|--------|-----|---------|-------------|
| 1.1 | Listar roles | `GET` | `http://localhost:8080/api/roles` | `Authorization: Bearer <token>` | — |
| 1.2 | Ver rol por ID | `GET` | `http://localhost:8080/api/roles/1` | `Authorization: Bearer <token>` | — |
| 1.3 | Crear rol | `POST` | `http://localhost:8080/api/roles` | `Content-Type: application/json`<br>`Authorization: Bearer <token>` | `{"name": "auditor", "description": "Rol de auditoría", "is_active": 1}` |
| 1.4 | Actualizar rol | `PUT` | `http://localhost:8080/api/roles/4` | `Content-Type: application/json`<br>`Authorization: Bearer <token>` | `{"description": "Rol de auditoría actualizado"}` |
| 1.5 | Desactivar rol (soft delete) | `DELETE` | `http://localhost:8080/api/roles/4` | `Authorization: Bearer <token>` | — |

---

## 2. Permisos (protegido)

| # | Acción | Método | URL | Headers | Body (JSON) |
|---|--------|--------|-----|---------|-------------|
| 2.1 | Listar permisos | `GET` | `http://localhost:8080/api/permissions` | `Authorization: Bearer <token>` | — |
| 2.2 | Ver permiso por ID | `GET` | `http://localhost:8080/api/permissions/1` | `Authorization: Bearer <token>` | — |
| 2.3 | Crear permiso | `POST` | `http://localhost:8080/api/permissions` | `Content-Type: application/json`<br>`Authorization: Bearer <token>` | `{"name": "export", "description": "Exportar datos"}` |
| 2.4 | Actualizar permiso | `PUT` | `http://localhost:8080/api/permissions/7` | `Content-Type: application/json`<br>`Authorization: Bearer <token>` | `{"description": "Exportar datos a Excel"}` |
| 2.5 | Eliminar permiso (físico) | `DELETE` | `http://localhost:8080/api/permissions/7` | `Authorization: Bearer <token>` | — |

---

## 3. Módulos (protegido)

| # | Acción | Método | URL | Headers | Body (JSON) |
|---|--------|--------|-----|---------|-------------|
| 3.1 | Listar módulos | `GET` | `http://localhost:8080/api/modules` | `Authorization: Bearer <token>` | — |
| 3.2 | Ver módulo por ID | `GET` | `http://localhost:8080/api/modules/1` | `Authorization: Bearer <token>` | — |
| 3.3 | Crear módulo | `POST` | `http://localhost:8080/api/modules` | `Content-Type: application/json`<br>`Authorization: Bearer <token>` | `{"name": "Reportes", "route": "#reports", "icon": "bi-file-earmark", "description": "Módulo de reportes", "is_active": 1}` |
| 3.4 | Actualizar módulo | `PUT` | `http://localhost:8080/api/modules/6` | `Content-Type: application/json`<br>`Authorization: Bearer <token>` | `{"description": "Módulo de reportes actualizado"}` |
| 3.5 | Desactivar módulo (soft delete) | `DELETE` | `http://localhost:8080/api/modules/6` | `Authorization: Bearer <token>` | — |

---

## 4. Usuarios (protegido) — *Próximamente*

| # | Acción | Método | URL | Headers | Body (JSON) |
|---|--------|--------|-----|---------|-------------|
| 4.1 | Listar usuarios | `GET` | `http://localhost:8080/api/users` | `Authorization: Bearer <token>` | — |
| 4.2 | Ver usuario por ID | `GET` | `http://localhost:8080/api/users/1` | `Authorization: Bearer <token>` | — |
| 4.3 | Crear usuario + perfil | `POST` | `http://localhost:8080/api/users` | `Content-Type: application/json`<br>`Authorization: Bearer <token>` | `{"username": "juan", "email": "juan@pqrs.com", "password": "juan123", "status_id": 1, "role_ids": [2], "first_name": "Juan", "last_name": "Pérez", "document_type_id": 1, "document_number": "12345678"}` |
| 4.4 | Actualizar usuario | `PUT` | `http://localhost:8080/api/users/2` | `Content-Type: application/json`<br>`Authorization: Bearer <token>` | `{"first_name": "Juan Carlos", "phone": "3001234567"}` |
| 4.5 | Desactivar usuario (soft delete) | `DELETE` | `http://localhost:8080/api/users/2` | `Authorization: Bearer <token>` | — |

---

## 5. Asignación de Permisos (protegido) — *Próximamente*

| # | Acción | Método | URL | Headers | Body (JSON) |
|---|--------|--------|-----|---------|-------------|
| 5.1 | Asignar permisos a usuario+rol+módulo | `POST` | `http://localhost:8080/api/permissions/assign` | `Content-Type: application/json`<br>`Authorization: Bearer <token>` | `{"user_id": 1, "role_id": 1, "module_id": 2, "permission_ids": [1, 2, 3]}` |
| 5.2 | Ver permisos de un usuario | `GET` | `http://localhost:8080/api/permissions/user/1` | `Authorization: Bearer <token>` | — |
| 5.3 | Revocar un permiso específico | `DELETE` | `http://localhost:8080/api/permissions/revoke` | `Content-Type: application/json`<br>`Authorization: Bearer <token>` | `{"user_id": 1, "role_id": 1, "module_id": 2, "permission_id": 3}` |

---

## 6. Pruebas de Seguridad (negativas)

| # | Acción | Método | URL | Headers | Resultado esperado |
|---|--------|--------|-----|---------|--------------------|
| 6.1 | Petición sin token | `GET` | `http://localhost:8080/api/roles` | — | `401 Token no proporcionado` |
| 6.2 | Token inválido | `GET` | `http://localhost:8080/api/roles` | `Authorization: Bearer token_falso` | `401 Token inválido` |
| 6.3 | Crear rol duplicado | `POST` | `http://localhost:8080/api/roles` | `Content-Type: application/json`<br>`Authorization: Bearer <token>` | `409 Ya existe un rol con ese nombre` |
| 6.4 | Ver ID inexistente | `GET` | `http://localhost:8080/api/roles/999` | `Authorization: Bearer <token>` | `404 Rol no encontrado` |

---

## Recomendación: Variables de Entorno en Postman

Para no repetir el token en cada petición:

1. **Crear una variable de entorno** en Postman llamada `token`.
2. En el login, ir a la pestaña **Tests** y agregar:
   ```javascript
   const response = pm.response.json();
   pm.environment.set("token", response.token);