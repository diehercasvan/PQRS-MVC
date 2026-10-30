# Paso 3: Utilidades de UI

## Paso 3.1: Crear `core/Alert.js`

Este archivo maneja:

* **Toasts** (mensajes flotantes de éxito/error/info/warning).
* **Modales de confirmación** (para acciones destructivas como eliminar/desactivar).
* **Modales informativos** (para mensajes más largos).

Crea  **`public/assets/js/core/Alert.js`** :

```JavaScript
// ============================================================
// Alert - Toasts y modales de confirmación/información
// ============================================================

export class Alert {
    // ------------------------------------------------------------
    // TOASTS
    // ------------------------------------------------------------

    /**
     * Muestra un toast flotante.
     * @param {string} message
     * @param {string} type - 'success', 'danger', 'warning', 'info'
     * @param {number} duration - ms (default 4000)
     */
    static toast(message, type = 'success', duration = 4000) {
        const container = this._getToastContainer();

        const icons = {
            success: 'bi-check-circle-fill',
            danger:  'bi-x-circle-fill',
            warning: 'bi-exclamation-triangle-fill',
            info:    'bi-info-circle-fill',
        };

        const bgClass = {
            success: 'text-bg-success',
            danger:  'text-bg-danger',
            warning: 'text-bg-warning',
            info:    'text-bg-info',
        }[type] || 'text-bg-secondary';

        const toast = document.createElement('div');
        toast.className = `toast align-items-center ${bgClass} border-0 show mb-2`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center gap-2">
                    <i class="bi ${icons[type] || icons.info}"></i>
                    <span>${this._escapeHtml(message)}</span>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;

        container.appendChild(toast);

        // Auto-eliminar después del tiempo
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, duration);

        // Botón de cerrar
        toast.querySelector('.btn-close').addEventListener('click', () => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        });
    }

    static success(message) { this.toast(message, 'success'); }
    static error(message)   { this.toast(message, 'danger'); }
    static warning(message) { this.toast(message, 'warning'); }
    static info(message)    { this.toast(message, 'info'); }

    // ------------------------------------------------------------
    // MODAL DE CONFIRMACIÓN
    // ------------------------------------------------------------

    /**
     * Muestra un modal de confirmación.
     * @param {string} title
     * @param {string} message
     * @param {object} options - { confirmText, cancelText, confirmClass }
     * @returns {Promise<boolean>} true si confirma, false si cancela.
     */
    static confirm(title, message, options = {}) {
        const {
            confirmText = 'Confirmar',
            cancelText  = 'Cancelar',
            confirmClass = 'btn-danger',
        } = options;

        return new Promise((resolve) => {
            const modalId = 'confirmModal_' + Date.now();
            const modalHtml = `
                <div class="modal fade" id="${modalId}" tabindex="-1" data-bs-backdrop="static">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">${this._escapeHtml(title)}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p class="mb-0">${this._escapeHtml(message)}</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">${cancelText}</button>
                                <button type="button" class="btn ${confirmClass}" id="${modalId}_confirm">${confirmText}</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modalHtml);

            const modalEl = document.getElementById(modalId);
            const modal = new bootstrap.Modal(modalEl);

            let confirmed = false;

            document.getElementById(`${modalId}_confirm`).addEventListener('click', () => {
                confirmed = true;
                modal.hide();
            });

            modalEl.addEventListener('hidden.bs.modal', () => {
                modalEl.remove();
                resolve(confirmed);
            });

            modal.show();
        });
    }

    // ------------------------------------------------------------
    // MODAL INFORMATIVO
    // ------------------------------------------------------------

    /**
     * Muestra un modal informativo (solo botón "Aceptar").
     * @param {string} title
     * @param {string} message - Puede contener HTML simple (se inserta tal cual).
     */
    static info(title, message) {
        return new Promise((resolve) => {
            const modalId = 'infoModal_' + Date.now();
            const modalHtml = `
                <div class="modal fade" id="${modalId}" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">${this._escapeHtml(title)}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">${message}</div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Aceptar</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            const modalEl = document.getElementById(modalId);
            const modal = new bootstrap.Modal(modalEl);
            modalEl.addEventListener('hidden.bs.modal', () => {
                modalEl.remove();
                resolve();
            });
            modal.show();
        });
    }

    // ------------------------------------------------------------
    // HELPERS PRIVADOS
    // ------------------------------------------------------------

    static _getToastContainer() {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }
        return container;
    }

    static _escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}
```

## Paso 3.2: Crear `core/Forms.js`

Este archivo maneja:

* **Serialización** de formularios a objeto.
* **Validación** basada en reglas.
* **Mostrar/limpiar errores** visuales (clases `is-invalid` de Bootstrap).
* **Bloqueo/desbloqueo** de formularios durante el envío.
* **Método `submit()`** que combina todo lo anterior.

Crea  **`public/assets/js/core/Forms.js`** :

```JavaScript
// ============================================================
// Forms - Serialización, validación, errores y bloqueo
// ============================================================

export class Forms {
    // ------------------------------------------------------------
    // SERIALIZACIÓN
    // ------------------------------------------------------------

    /**
     * Serializa un formulario a un objeto plano.
     * @param {HTMLFormElement} formElement
     * @returns {object}
     */
    static serialize(formElement) {
        const formData = new FormData(formElement);
        const data = {};
        for (const [key, value] of formData.entries()) {
            // Si la clave ya existe (checkboxes múltiples), convertir a array
            if (data[key] !== undefined) {
                if (!Array.isArray(data[key])) data[key] = [data[key]];
                data[key].push(value);
            } else {
                data[key] = value;
            }
        }
        return data;
    }

    // ------------------------------------------------------------
    // VALIDACIÓN
    // ------------------------------------------------------------

    /**
     * Valida un formulario según reglas.
     * @param {HTMLFormElement} formElement
     * @param {object} rules - ej: { username: { required: true, minLength: 3 }, email: { required: true, email: true } }
     * @returns {object} Errores por campo (vacío si no hay errores).
     */
    static validate(formElement, rules) {
        const data = this.serialize(formElement);
        const errors = {};

        for (const [field, rule] of Object.entries(rules)) {
            const value = data[field];

            if (rule.required && (!value || String(value).trim() === '')) {
                errors[field] = `El campo ${rule.label || field} es obligatorio.`;
                continue;
            }

            // Si el valor está vacío y no es requerido, no validar más
            if (!value || String(value).trim() === '') continue;

            if (rule.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                errors[field] = `El ${rule.label || field} no es un email válido.`;
            }
            if (rule.minLength && String(value).length < rule.minLength) {
                errors[field] = `El ${rule.label || field} debe tener al menos ${rule.minLength} caracteres.`;
            }
            if (rule.maxLength && String(value).length > rule.maxLength) {
                errors[field] = `El ${rule.label || field} no debe superar ${rule.maxLength} caracteres.`;
            }
            if (rule.numeric && isNaN(Number(value))) {
                errors[field] = `El ${rule.label || field} debe ser numérico.`;
            }
            if (rule.pattern && !new RegExp(rule.pattern).test(value)) {
                errors[field] = rule.patternMessage || `El ${rule.label || field} no tiene un formato válido.`;
            }
        }

        return errors;
    }

    // ------------------------------------------------------------
    // MOSTRAR / LIMPIAR ERRORES
    // ------------------------------------------------------------

    /**
     * Muestra los errores en el formulario (usa clases de Bootstrap).
     * @param {HTMLFormElement} formElement
     * @param {object} errors - { campo: 'mensaje' }
     */
    static showErrors(formElement, errors) {
        this.clearErrors(formElement);

        for (const [field, message] of Object.entries(errors)) {
            const input = formElement.querySelector(`[name="${field}"]`);
            if (!input) continue;

            input.classList.add('is-invalid');

            // Buscar el contenedor .invalid-feedback
            let feedback = input.parentElement.querySelector('.invalid-feedback');
            if (!feedback) {
                feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                input.parentElement.appendChild(feedback);
            }
            feedback.textContent = message;
        }
    }

    /**
     * Limpia todos los errores visuales del formulario.
     * @param {HTMLFormElement} formElement
     */
    static clearErrors(formElement) {
        formElement.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        formElement.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
    }

    // ------------------------------------------------------------
    // BLOQUEO / DESBLOQUEO
    // ------------------------------------------------------------

    /**
     * Bloquea todos los inputs, selects, textareas y botones del formulario.
     * @param {HTMLFormElement} formElement
     */
    static lock(formElement) {
        formElement.querySelectorAll('input, select, textarea, button').forEach(el => {
            el.disabled = true;
        });
        formElement.dataset.locked = 'true';
    }

    /**
     * Desbloquea todos los elementos del formulario.
     * @param {HTMLFormElement} formElement
     */
    static unlock(formElement) {
        formElement.querySelectorAll('input, select, textarea, button').forEach(el => {
            el.disabled = false;
        });
        formElement.dataset.locked = 'false';
    }

    /**
     * Verifica si el formulario está bloqueado.
     * @param {HTMLFormElement} formElement
     * @returns {boolean}
     */
    static isLocked(formElement) {
        return formElement.dataset.locked === 'true';
    }

    /**
     * Resetea el formulario y limpia errores.
     * @param {HTMLFormElement} formElement
     */
    static reset(formElement) {
        formElement.reset();
        this.clearErrors(formElement);
        formElement.dataset.locked = 'false';
    }

    // ------------------------------------------------------------
    // SUBMIT CON BLOQUEO AUTOMÁTICO
    // ------------------------------------------------------------

    /**
     * Ejecuta un handler de submit con bloqueo automático.
     * Si el formulario ya está bloqueado, no hace nada.
     * @param {HTMLFormElement} formElement
     * @param {function} handler - async () => { ... }
     */
    static async submit(formElement, handler) {
        if (this.isLocked(formElement)) return;

        this.lock(formElement);
        try {
            await handler();
        } catch (error) {
            console.error('Forms.submit error:', error);
        } finally {
            this.unlock(formElement);
        }
    }
}
```

## Paso 3.3: Actualizar `main.js` para probar `Alert` y `Forms`

Actualiza **`public/assets/js/main.js`** con pruebas temporales:

```JavaScript
// ============================================================
// PUNTO DE ENTRADA (temporal - se completará en el Paso 7)
// ============================================================

import { LocalStorage } from './core/LocalStorage.js';
import { Loader } from './core/Loader.js';
import { ApiService } from './core/ApiService.js';
import { Alert } from './core/Alert.js';
import { Forms } from './core/Forms.js';

console.log('PQRS System - Frontend inicializado correctamente.');

// ------------------------------------------------------------
// TEST TEMPORAL: Probar Alert y Forms
// ------------------------------------------------------------
async function testUI() {
    Loader.forceHide();

    // --- Test 1: Toast de éxito ---
    setTimeout(() => Alert.success('Esto es un toast de éxito.'), 500);

    // --- Test 2: Toast de error ---
    setTimeout(() => Alert.error('Esto es un toast de error.'), 1000);

    // --- Test 3: Toast de warning ---
    setTimeout(() => Alert.warning('Esto es un toast de advertencia.'), 1500);

    // --- Test 4: Toast de info ---
    setTimeout(() => Alert.info('Esto es un toast informativo.'), 2000);

    // --- Test 5: Modal de confirmación ---
    setTimeout(async () => {
        const confirmed = await Alert.confirm(
            '¿Estás seguro?',
            'Esta acción no se puede deshacer. ¿Deseas continuar?',
            { confirmText: 'Sí, continuar', cancelText: 'No, cancelar', confirmClass: 'btn-danger' }
        );
        Alert.info('Resultado', `El usuario ${confirmed ? 'confirmó' : 'canceló'} la acción.`);
    }, 2500);

    // --- Test 6: Forms.serialize ---
    // Creamos un formulario temporal en el DOM
    setTimeout(() => {
        const testForm = document.createElement('form');
        testForm.id = 'test-form';
        testForm.innerHTML = `
            <input type="text" name="username" value="juan" />
            <input type="email" name="email" value="juan@example.com" />
            <input type="password" name="password" value="123" />
            <input type="text" name="email_invalid" value="no-es-email" />
        `;
        document.body.appendChild(testForm);

        // Test serialize
        const data = Forms.serialize(testForm);
        console.log('✅ Forms.serialize:', data);

        // Test validate
        const errors = Forms.validate(testForm, {
            username: { required: true, minLength: 3, label: 'usuario' },
            email: { required: true, email: true, label: 'correo' },
            password: { required: true, minLength: 6, label: 'contraseña' },
            email_invalid: { email: true, label: 'email inválido' },
        });
        console.log('✅ Forms.validate (errores):', errors);

        // Test showErrors
        Forms.showErrors(testForm, errors);
        console.log('✅ Forms.showErrors ejecutado. Verifica visualmente los campos en rojo.');

        // Test lock/unlock
        Forms.lock(testForm);
        console.log('✅ Forms.lock. ¿Bloqueado?', Forms.isLocked(testForm));
        setTimeout(() => {
            Forms.unlock(testForm);
            console.log('✅ Forms.unlock. ¿Bloqueado?', Forms.isLocked(testForm));
        }, 2000);

        // Limpiar el formulario de prueba
        setTimeout(() => {
            testForm.remove();
            console.log('🧹 Formulario de prueba eliminado.');
        }, 4000);
    }, 5000);
}

testUI();
```

## Verificación del Paso 3

Recarga `http://localhost:8080/` y observa la consola y la pantalla:

## 1. **Toasts (0.5s a 2s)**

Deberías ver **4 toasts** aparecer en la esquina superior derecha, en orden:

* Verde (éxito)
* Rojo (error)
* Amarillo (warning)
* Azul (info)

Cada uno desaparece a los 4 segundos.

## 2. **Modal de confirmación (2.5s)**

Aparece un modal preguntando "¿Estás seguro?". Si haces clic en "Sí, continuar" o "No, cancelar", aparece otro modal informativo con el resultado.

## 3. **Formulario de prueba (5s)**

Aparece un formulario temporal en la parte inferior del body con:

* `username` en rojo (porque `minLength: 3` y tiene "juan", que cumple... espera, "juan" tiene 4 caracteres, así que **no** debería estar en rojo).
* `email` con valor válido → **no** en rojo.
* `password` con "123" → **rojo** (porque `minLength: 6`).
* `email_invalid` con "no-es-email" → **rojo** (porque no es email).

> **Corrección:** Si `username` tiene "juan" (4 caracteres), **no** debería mostrar error. Los únicos campos con error deberían ser `password` y `email_invalid`. Si ves `username` en rojo, revisa la lógica.

## 3. **Consola (F12)**

Deberías ver:

```
PQRS System - Frontend inicializado correctamente.
✅ Forms.serialize: {username: 'juan', email: 'juan@example.com', password: '123', email_invalid: 'no-es-email'}
✅ Forms.validate (errores): {password: 'El contraseña debe tener al menos 6 caracteres.', email_invalid: 'El email inválido no es un email válido.'}
✅ Forms.showErrors ejecutado...
✅ Forms.lock. ¿Bloqueado? true
✅ Forms.unlock. ¿Bloqueado? false
🧹 Formulario de prueba eliminado.
```

**Nota:** El mensaje "El contraseña" suena raro (concordancia de género), pero es porque usamos "contraseña" como label. Si prefieres "La contraseña", puedes ajustar el label a "La contraseña" o cambiar el mensaje en `Forms.validate`.

## Checklist del Paso 3

* `core/Alert.js` creado con `toast`, `success`, `error`, `warning`, `info`, `confirm`, `info` (modal).
* `core/Forms.js` creado con `serialize`, `validate`, `showErrors`, `clearErrors`, `lock`, `unlock`, `isLocked`, `reset`, `submit`.
* `main.js` actualizado con el test de UI.
* Los 4 toasts aparecen en orden (success, error, warning, info).
* El modal de confirmación funciona (botones "Sí" y "No").
* El modal informativo muestra el resultado.
* El formulario de prueba aparece con los campos en rojo correctos (`password` y `email_invalid`).
* En consola aparecen los 5 mensajes "✅".
* El formulario de prueba desaparece después de 4 segundos.
