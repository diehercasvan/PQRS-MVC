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