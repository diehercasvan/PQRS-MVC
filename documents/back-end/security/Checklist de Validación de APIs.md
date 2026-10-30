

# Cierre del Backend de Seguridad

Con el endpoint `/api/auth/permissions` ya tenemos  **todo el backend de seguridad completo** . A continuación, un resumen de lo que hemos construido y lo que falta:

### Lo que ya está listo

| Módulo              | Endpoints                                               | Estado |
| -------------------- | ------------------------------------------------------- | ------ |
| Autenticación       | `login`,`me`,`permissions`                        | ✅     |
| Roles                | `showAll`,`showId`,`create`,`update`,`delete` | ✅     |
| Permisos             | `showAll`,`showId`,`create`,`update`,`delete` | ✅     |
| Módulos             | `showAll`,`showId`,`create`,`update`,`delete` | ✅     |
| Usuarios             | `showAll`,`showId`,`create`,`update`,`delete` | ✅     |
| Asignación          | `assign`,`getUserPermissions`,`revoke`            | ✅     |
| Filtro`AuthFilter` | Protección global con JWT                              | ✅     |

Flujo completo que ya funciona

1. **Login** → devuelve token JWT.
2. **Frontend** guarda token en `localStorage`.
3. **Frontend** llama a `/api/auth/permissions` con el token → recibe módulos y permisos.
4. **Frontend** construye el menú dinámico solo con los módulos que el usuario puede ver.
5. **Frontend** habilita/deshabilita botones según los permisos (`create`, `edit`, `delete`, `print`, `download`).
6. Cada petición a los CRUDs pasa por el `AuthFilter` que valida el token.

# Checklist de Validación de APIs - Proyecto PQRS

Este documento contiene la lista de chequeo para validar cada endpoint de la API en Postman. Marca cada casilla a medida que confirmes que el endpoint funciona correctamente.

---

## Convenciones

- **✅** = Validado y funcionando.
- **⬜** = Pendiente por validar.
- **❌** = Falló (requiere corrección).

---

## 0. Autenticación (`/api/auth`)

### 0.1 Login

- [ ] `POST /api/auth/login` con credenciales válidas → `200` + token.
- [ ] El token es un string JWT válido (3 partes separadas por puntos).
- [ ] `POST /api/auth/login` con password incorrecto → `401 Credenciales inválidas`.
- [ ] `POST /api/auth/login` con usuario inexistente → `401 Credenciales inválidas`.
- [ ] `POST /api/auth/login` sin body → `400 Usuario y contraseña son obligatorios`.
- [ ] `POST /api/auth/login` con usuario inactivo (status_id != 1) → `403 El usuario no está activo`.

### 0.2 Me

- [ ] `GET /api/auth/me` con token válido → `200` + datos del usuario.
- [ ] `GET /api/auth/me` sin token → `401 Token no proporcionado`.
- [ ] `GET /api/auth/me` con token inválido → `401 Token inválido o expirado`.

### 0.3 Permissions

- [ ] `GET /api/auth/permissions` con token válido → `200` + array de módulos con permisos.
- [ ] `GET /api/auth/permissions` sin token → `401`.
- [ ] Con usuario regular, solo devuelve los módulos/permisos asignados.

---

## 1. Roles (`/api/roles`)

### 1.1 Listar roles

- [ ] `GET /api/roles` con token válido → `200` + array de roles.
- [ ] `GET /api/roles` sin token → `401 Token no proporcionado`.
- [ ] `GET /api/roles` con token inválido → `401 Token inválido o expirado`.

### 1.2 Ver rol por ID

- [ ] `GET /api/roles/1` con token válido → `200` + datos del rol.
- [ ] `GET /api/roles/999` (inexistente) → `404 Rol no encontrado`.

### 1.3 Crear rol

- [ ] `POST /api/roles` con body válido → `201` + rol creado.
- [ ] `POST /api/roles` sin `name` → `400 El campo name es obligatorio`.
- [ ] `POST /api/roles` con `name` duplicado → `409 Ya existe un rol con ese nombre`.
- [ ] `POST /api/roles` sin token → `401`.

### 1.4 Actualizar rol

- [ ] `PUT /api/roles/{id}` con body válido → `200` + rol actualizado.
- [ ] `PUT /api/roles/{id}` con `name` duplicado → `409 Ya existe otro rol con ese nombre`.
- [ ] `PUT /api/roles/999` (inexistente) → `404 Rol no encontrado`.
- [ ] `PUT /api/roles/{id}` sin body → `400 No se enviaron datos para actualizar`.

### 1.5 Desactivar rol (soft delete)

- [ ] `DELETE /api/roles/{id}` → `200` + `is_active` cambia a `0`.
- [ ] Verificar en BD que `is_active = 0` después del delete.
- [ ] `DELETE /api/roles/999` → `404 Rol no encontrado`.

---

## 2. Permisos (`/api/permissions`)

### 2.1 Listar permisos

- [ ] `GET /api/permissions` con token válido → `200` + array de permisos.
- [ ] `GET /api/permissions` sin token → `401`.

### 2.2 Ver permiso por ID

- [ ] `GET /api/permissions/1` → `200` + permiso "view".
- [ ] `GET /api/permissions/999` → `404 Permiso no encontrado`.

### 2.3 Crear permiso

- [ ] `POST /api/permissions` con body válido → `201` + permiso creado.
- [ ] `POST /api/permissions` sin `name` → `400 El campo name es obligatorio`.
- [ ] `POST /api/permissions` con `name` duplicado → `409 Ya existe un permiso con ese nombre`.

### 2.4 Actualizar permiso

- [ ] `PUT /api/permissions/{id}` con body válido → `200` + permiso actualizado.
- [ ] `PUT /api/permissions/{id}` con `name` duplicado → `409 Ya existe otro permiso con ese nombre`.
- [ ] `PUT /api/permissions/999` → `404 Permiso no encontrado`.
- [ ] `PUT /api/permissions/{id}` sin body → `400 No se enviaron datos para actualizar`.

### 2.5 Eliminar permiso (físico)

- [ ] `DELETE /api/permissions/{id}` → `200` + permiso eliminado.
- [ ] Verificar en BD que el registro ya no existe.
- [ ] `DELETE /api/permissions/999` → `404 Permiso no encontrado`.

---

## 3. Módulos (`/api/modules`)

### 3.1 Listar módulos

- [ ] `GET /api/modules` con token válido → `200` + array de módulos.
- [ ] `GET /api/modules` sin token → `401`.

### 3.2 Ver módulo por ID

- [ ] `GET /api/modules/1` → `200` + módulo "Dashboard".
- [ ] `GET /api/modules/999` → `404 Módulo no encontrado`.

### 3.3 Crear módulo

- [ ] `POST /api/modules` con body válido → `201` + módulo creado.
- [ ] `POST /api/modules` sin `name` o `route` → `400 Los campos name y route son obligatorios`.
- [ ] `POST /api/modules` con `name` duplicado → `409 Ya existe un módulo con ese nombre`.
- [ ] `POST /api/modules` con `route` duplicada → `409 Ya existe un módulo con esa ruta`.

### 3.4 Actualizar módulo

- [ ] `PUT /api/modules/{id}` con body válido → `200` + módulo actualizado.
- [ ] `PUT /api/modules/{id}` con `name` duplicado → `409 Ya existe otro módulo con ese nombre`.
- [ ] `PUT /api/modules/{id}` con `route` duplicada → `409 Ya existe otro módulo con esa ruta`.
- [ ] `PUT /api/modules/999` → `404 Módulo no encontrado`.
- [ ] `PUT /api/modules/{id}` sin body → `400 No se enviaron datos para actualizar`.

### 3.5 Desactivar módulo (soft delete)

- [ ] `DELETE /api/modules/{id}` → `200` + `is_active` cambia a `0`.
- [ ] Verificar en BD que `is_active = 0`.
- [ ] `DELETE /api/modules/999` → `404 Módulo no encontrado`.

---

## 4. Usuarios (`/api/users`) — *Próximamente*

### 4.1 Listar usuarios

- [ ] `GET /api/users` con token válido → `200` + array de usuarios con perfil y roles.
- [ ] `GET /api/users` sin token → `401`.

### 4.2 Ver usuario por ID

- [ ] `GET /api/users/1` → `200` + usuario con perfil y roles.
- [ ] `GET /api/users/999` → `404 Usuario no encontrado`.

### 4.3 Crear usuario + perfil

- [ ] `POST /api/users` con body válido → `201` + usuario creado con perfil y rol.
- [ ] Verificar en BD que se crearon registros en `user`, `profile` y `user_role`.
- [ ] `POST /api/users` sin `username` → `400`.
- [ ] `POST /api/users` con `username` duplicado → `409`.
- [ ] `POST /api/users` con `email` duplicado → `409`.
- [ ] `POST /api/users` con `document_number` duplicado → `409`.
- [ ] Verificar que la contraseña se guarda hasheada en BD (no en texto plano).

### 4.4 Actualizar usuario

- [ ] `PUT /api/users/{id}` con body válido → `200` + usuario actualizado.
- [ ] Verificar que se actualizan tanto `user` como `profile`.
- [ ] `PUT /api/users/{id}` con `email` duplicado → `409`.
- [ ] `PUT /api/users/999` → `404 Usuario no encontrado`.

### 4.5 Desactivar usuario (soft delete)

- [ ] `DELETE /api/users/{id}` → `200` + `status_id` cambia a `2` (inactivo).
- [ ] Verificar en BD que `status_id = 2`.
- [ ] Intentar hacer login con el usuario desactivado → `403 El usuario no está activo`.
- [ ] `DELETE /api/users/999` → `404 Usuario no encontrado`.

---

## 5. Asignación de Permisos (`/api/permissions`) — *Próximamente*

### 5.1 Asignar permisos

- [ ] `POST /api/permissions/assign` con body válido → `201` + permisos asignados.
- [ ] Verificar en BD que se crearon registros en `module_role_user`.
- [ ] `POST /api/permissions/assign` con `user_id` inexistente → `404`.
- [ ] `POST /api/permissions/assign` con `role_id` inexistente → `404`.
- [ ] `POST /api/permissions/assign` con `module_id` inexistente → `404`.
- [ ] `POST /api/permissions/assign` con `permission_ids` vacío → `400`.

### 5.2 Ver permisos de un usuario

- [ ] `GET /api/permissions/user/{userId}` → `200` + array de permisos agrupados por módulo.
- [ ] `GET /api/permissions/user/999` → `404 Usuario no encontrado`.

### 5.3 Revocar permiso

- [ ] `DELETE /api/permissions/revoke` con body válido → `200` + permiso revocado.
- [ ] Verificar en BD que el registro se eliminó de `module_role_user`.
- [ ] `DELETE /api/permissions/revoke` con permiso inexistente → `404`.

---

## 6. Pruebas de Seguridad (Transversales)

- [ ] Cualquier endpoint protegido sin token → `401 Token no proporcionado`.
- [ ] Cualquier endpoint protegido con token inválido → `401 Token inválido o expirado`.
- [ ] Cualquier endpoint protegido con token expirado → `401 Token inválido o expirado`.
- [ ] Los endpoints de login no requieren token.
- [ ] Los errores 500 no exponen información sensible (en producción, `CI_ENVIRONMENT = production`).
- [ ] Los IDs inexistentes devuelven `404` y no `500`.
- [ ] Los duplicados devuelven `409` y no `500`.

---

## 7. Validaciones de Base de Datos

Después de cada operación, verificar en phpMyAdmin o consola MySQL:

- [ ] Los `created_at` y `updated_at` se generan automáticamente.
- [ ] Los `DELETE` (soft delete) cambian el estado pero no borran el registro.
- [ ] Los `DELETE` físicos (permisos) eliminan el registro.
- [ ] Las contraseñas se guardan hasheadas (empiezan con `$2y$10$`).
- [ ] Las foreign keys se respetan (no se puede crear un usuario con `status_id` inexistente).

---

## 8. Resumen de Progreso

| Módulo         | Total endpoints | Validados   | Pendientes   |
| --------------- | --------------- | ----------- | ------------ |
| Autenticación  | 2               | ⬜          | ⬜           |
| Roles           | 5               | ⬜          | ⬜           |
| Permisos        | 5               | ⬜          | ⬜           |
| Módulos        | 5               | ⬜          | ⬜           |
| Usuarios        | 5               | ⬜          | ⬜           |
| Asignación     | 3               | ⬜          | ⬜           |
| **Total** | **25**    | **0** | **25** |

---

## Notas

- Marca cada casilla solo cuando el endpoint devuelva el código HTTP y el JSON esperado.
- Si un endpoint falla, anota el error y corrígelo antes de continuar.
- Usa la colección de Postman con variables de entorno para no repetir el token.
- Guarda los ejemplos de respuestas exitosas para usarlos como referencia en el frontend.
