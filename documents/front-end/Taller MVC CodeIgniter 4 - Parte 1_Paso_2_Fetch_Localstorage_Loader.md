<div id="global-loader" class="global-loader active">

## Paso 2: Infraestructura de Comunicació

## Antes de continuar: pequeño ajuste visual

Como vamos a trabajar con el login primero, quiero que el loader aparezca por defecto al cargar la página, para que se vea el efecto. Cambiemos una línea en `index.html`:

**Antes:**

```HTML
<div id="global-loader" class="global-loader">
```

**Después:**

```HTML
<div id="global-loader" class="global-loader active">
```

Con esto, verás el spinner al recargar. En el  **Paso 2** , cuando creemos `Loader.hide()`, el spinner desaparecerá automáticamente al iniciar la app.

**En este paso crearemos tres archivos que son la base de toda la aplicación:**

1. **`core/LocalStorage.js`** — Wrapper de `localStorage` con manejo de JSON.
2. **`core/Loader.js`** — Control del overlay de carga con contador para peticiones simultáneas.
3. **`core/ApiService.js`** — Peticiones `fetch` centralizadas con token automático y manejo de errores.

## Paso 2.1: Crear `core/LocalStorage.js`

Crea  **`public/assets/js/core/LocalStorage.js`** :

```JavaScript
// ============================================================
// LocalStorage - Wrapper para manejar localStorage con JSON
// ============================================================

export class LocalStorage {
    /**
     * Guarda un valor en localStorage (serializado como JSON).
     * @param {string} key
     * @param {*} value
     */
    static set(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
        } catch (error) {
            console.error(`Error guardando en localStorage [${key}]:`, error);
        }
    }

    /**
     * Obtiene un valor de localStorage (deserializado).
     * @param {string} key
     * @returns {*} El valor o null si no existe.
     */
    static get(key) {
        try {
            const value = localStorage.getItem(key);
            return value ? JSON.parse(value) : null;
        } catch (error) {
            console.error(`Error leyendo de localStorage [${key}]:`, error);
            return null;
        }
    }

    /**
     * Elimina una clave de localStorage.
     * @param {string} key
     */
    static remove(key) {
        localStorage.removeItem(key);
    }

    /**
     * Limpia todo el localStorage.
     */
    static clear() {
        localStorage.clear();
    }

    /**
     * Verifica si existe una clave en localStorage.
     * @param {string} key
     * @returns {boolean}
     */
    static has(key) {
        return localStorage.getItem(key) !== null;
    }
}
```

## Paso 2.2: Crear `core/Loader.js`

Crea  **`public/assets/js/core/Loader.js`** :

```JavaScript
// ============================================================
// Loader - Control del overlay de carga global
// Usa un contador para manejar peticiones simultáneas.
// ============================================================

export class Loader {
    static counter = 0;
    static overlayId = 'global-loader';

    /**
     * Muestra el loader. Incrementa el contador.
     * @param {string} message - Mensaje opcional a mostrar.
     */
    static show(message = 'Cargando...') {
        this.counter++;
        const overlay = document.getElementById(this.overlayId);
        if (!overlay) return;

        // Actualizar el mensaje si se proporciona
        const messageEl = overlay.querySelector('.loader-content p');
        if (messageEl) {
            messageEl.textContent = message;
        }

        overlay.classList.add('active');
    }

    /**
     * Oculta el loader. Decrementa el contador.
     * Solo oculta cuando el contador llega a 0.
     */
    static hide() {
        this.counter = Math.max(0, this.counter - 1);
        if (this.counter > 0) return;

        const overlay = document.getElementById(this.overlayId);
        if (overlay) {
            overlay.classList.remove('active');
        }
    }

    /**
     * Fuerza el ocultamiento del loader (resetea el contador).
     * Útil en casos de error inesperado.
     */
    static forceHide() {
        this.counter = 0;
        const overlay = document.getElementById(this.overlayId);
        if (overlay) {
            overlay.classList.remove('active');
        }
    }
}
```

**¿Por qué un contador?** Si haces 3 peticiones simultáneas, el contador sube a 3. Cada `hide()` lo baja a 2, 1, 0. Solo cuando llega a 0 se oculta el overlay. Así evitamos que el loader parpadee o desaparezca antes de tiempo.

## Paso 2.3: Crear `core/ApiService.js`

Crea  **`public/assets/js/core/ApiService.js`** :

```JavaScript
// ============================================================
// ApiService - Peticiones HTTP centralizadas
// - Agrega token automáticamente en el header Authorization.
// - Maneja errores 401 (token expirado → logout).
// - Integra el Loader durante cada petición.
// ============================================================

import { LocalStorage } from './LocalStorage.js';
import { Loader } from './Loader.js';

export class ApiService {
    // URL base de la API (mismo origen, sin CORS)
    static baseUrl = '/api';

    /**
     * Realiza una petición HTTP.
     * @param {string} endpoint - Ruta relativa, ej: '/roles' o '/auth/login'
     * @param {string} method - GET, POST, PUT, DELETE
     * @param {object|null} body - Cuerpo de la petición (opcional)
     * @param {object} options - Opciones adicionales { showLoader: boolean }
     * @returns {Promise<{ok: boolean, status: number, data: object}>}
     */
    static async request(endpoint, method = 'GET', body = null, options = {}) {
        const { showLoader = true } = options;

        // Mostrar loader si corresponde
        if (showLoader) Loader.show();

        // Headers base
        const headers = { 'Content-Type': 'application/json' };

        // Agregar token si existe
        const token = LocalStorage.get('token');
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }

        // Opciones del fetch
        const fetchOptions = { method, headers };
        if (body) {
            fetchOptions.body = JSON.stringify(body);
        }

        try {
            const response = await fetch(`${this.baseUrl}${endpoint}`, fetchOptions);

            // Token expirado o inválido
            if (response.status === 401) {
                LocalStorage.clear();
                // Redirigir al login (el Router se encargará del render)
                window.location.hash = '#/login';
                return {
                    ok: false,
                    status: 401,
                    data: { message: 'Sesión expirada. Por favor, inicia sesión de nuevo.' }
                };
            }

            // Parsear respuesta JSON (si la hay)
            let data = {};
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                data = await response.json();
            } else {
                data = { message: await response.text() };
            }

            return { ok: response.ok, status: response.status, data };

        } catch (error) {
            console.error('ApiService error:', error);
            return {
                ok: false,
                status: 0,
                data: { message: 'Error de conexión. Verifica tu red o el servidor.' }
            };
        } finally {
            // Ocultar loader siempre, incluso si hay error
            if (showLoader) Loader.hide();
        }
    }

    // -------------------- Métodos de conveniencia --------------------

    static get(endpoint, options = {}) {
        return this.request(endpoint, 'GET', null, options);
    }

    static post(endpoint, body, options = {}) {
        return this.request(endpoint, 'POST', body, options);
    }

    static put(endpoint, body, options = {}) {
        return this.request(endpoint, 'PUT', body, options);
    }

    static delete(endpoint, body = null, options = {}) {
        return this.request(endpoint, 'DELETE', body, options);
    }
}
```

**Nota sobre `baseUrl = '/api'`:** Como el frontend vive en `public/` y la API en `/api/...` dentro del mismo servidor, las rutas relativas funcionan directamente. Si el frontend estuviera en otro dominio, usaríamos `http://localhost:8080/api`.

## Paso 2.4: Actualizar `main.js` para probar la infraestructura

Para verificar que todo funciona, actualiza **`public/assets/js/main.js`** con un test temporal:

```JavaScript
// ============================================================
// PUNTO DE ENTRADA (temporal - se completará en el Paso 7)
// ============================================================

import { LocalStorage } from './core/LocalStorage.js';
import { Loader } from './core/Loader.js';
import { ApiService } from './core/ApiService.js';

console.log('PQRS System - Frontend inicializado correctamente.');

// ------------------------------------------------------------
// TEST TEMPORAL: Probar la infraestructura
// ------------------------------------------------------------
async function testInfrastructure() {
    // 1. Ocultar loader al inicio (porque pusimos la clase active en index.html)
    Loader.forceHide();

    // 2. Probar LocalStorage
    LocalStorage.set('test_key', { mensaje: 'Hola', numero: 42 });
    const testValue = LocalStorage.get('test_key');
    console.log('✅ LocalStorage funciona:', testValue);
    LocalStorage.remove('test_key');

    // 3. Probar Loader
    Loader.show('Probando loader...');
    console.log('✅ Loader.show() ejecutado. Contador:', Loader.counter);
    setTimeout(() => {
        Loader.hide();
        console.log('✅ Loader.hide() ejecutado. Contador:', Loader.counter);
    }, 1500);

    // 4. Probar ApiService (petición pública al login sin credenciales)
    setTimeout(async () => {
        const response = await ApiService.post('/auth/login', {
            username: 'admin',
            password: 'admin123'
        });
        console.log('✅ ApiService POST /auth/login:', response.status, response.data);
  
        // Si el login es exitoso, guardar el token para las siguientes pruebas
        if (response.ok && response.data.token) {
            LocalStorage.set('token', response.data.token);
            console.log('✅ Token guardado en LocalStorage.');

            // Probar un endpoint protegido
            const protectedResponse = await ApiService.get('/roles');
            console.log('✅ ApiService GET /roles (protegido):', protectedResponse.status);
        }
    }, 2500);
}

testInfrastructure();
```

## Verificación del Paso 2

1. Recarga `http://localhost:8080/`.
2. Abre la consola (F12).
3. Deberías ver en orden:

```
PQRS System - Frontend inicializado correctamente.
✅ LocalStorage funciona: {mensaje: 'Hola', numero: 42}
✅ Loader.show() ejecutado. Contador: 1
✅ Loader.hide() ejecutado. Contador: 0
✅ ApiService POST /auth/login: 200 {status: 'success', message: 'Login exitoso.', ...}
✅ Token guardado en LocalStorage.
✅ ApiService GET /roles (protegido): 200
```

**Visualmente:**

* Al recargar, verás el spinner brevemente (por el `forceHide()`).
* Luego, a los 1.5 segundos, el spinner vuelve a aparecer (por el test del Loader) y desaparece.
* A los 2.5 segundos, el spinner aparece nuevamente (por la petición a `/auth/login`) y desaparece al recibir la respuesta.
* Verás el spinner otra vez por la petición a `/roles`.

**Nota:** El spinner aparecerá 3 veces durante el test. Esto es normal y confirma que el Loader funciona.

## Checklist del Paso 2

* `core/LocalStorage.js` creado con métodos `set`, `get`, `remove`, `clear`, `has`.
* `core/Loader.js` creado con `show`, `hide`, `forceHide` y contador.
* `core/ApiService.js` creado con `request`, `get`, `post`, `put`, `delete`.
* `ApiService` agrega token automáticamente si existe.
* `ApiService` redirige a `#/login` si recibe 401.
* `ApiService` muestra/oculta el Loader.
* `main.js` actualizado con el test de infraestructura.
* En consola aparecen los 6 mensajes de "✅".
* El spinner aparece y desaparece correctamente.
