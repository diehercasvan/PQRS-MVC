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