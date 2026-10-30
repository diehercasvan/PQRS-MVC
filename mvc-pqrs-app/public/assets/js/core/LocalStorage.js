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