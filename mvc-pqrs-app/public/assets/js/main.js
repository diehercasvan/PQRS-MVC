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