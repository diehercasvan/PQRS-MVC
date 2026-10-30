## Parte 1: Crear la estructura de carpetas

Antes de escribir, una aclaración importante: como el frontend vivirá dentro de CI4 en `public/`, el `index.html` será servido en `http://localhost:8080/`. Pero cuidado: **CodeIgniter tiene su propio `public/index.php`** que es el punto de entrada de la API. No hay conflicto porque `index.html` es un archivo estático que Apache/Nginx sirve directamente antes de llegar a PHP.

> ⚠️ **Verificación previa:** Asegúrate de que en `public/`**no exista** un archivo `index.html` previo que interfiera. Si existe, bórralo o renómbralo.

## Paso 1.1: Crear la estructura de carpetas

Dentro de `public/`, crea las siguientes carpetas:

```
public/
├── assets/
│   ├── css/
│   ├── js/
│   │   ├── core/
│   │   ├── components/
│   │   ├── pages/
│   │   └── templates/
│   └── img/
```

Puedes crearlas desde la terminal (dentro de `public/`):

```Shell
mkdir -p assets/css assets/js/core assets/js/components assets/js/pages assets/js/templates assets/img
```

## Paso 1.2: Crear `public/index.html`

Este archivo es el **shell** de la SPA. Contendrá:

* Los enlaces a Bootstrap y Bootstrap Icons (CDN).
* El enlace a `styles.css`.
* El contenedor del **loader global** (overlay de carga).
* El contenedor del **login** (visible solo si no hay sesión).
* El **layout principal** (navbar, sidebar, contenido, footer), oculto por defecto.
* El **contenedor de toasts** para las alertas.
* El script `main.js` como módulo ES6.

Crea **`public/index.html`** con este contenido:

```HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PQRS System</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Estilos personalizados -->
    <link href="./assets/css/styles.css" rel="stylesheet">
</head>
<body>

    <!-- ============================================================
         LOADER GLOBAL (overlay de carga)
         Se muestra durante cada petición HTTP y se oculta al terminar.
         ============================================================ -->
    <div id="global-loader" class="global-loader">
        <div class="loader-content">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2 mb-0 text-muted">Cargando...</p>
        </div>
    </div>

    <!-- ============================================================
         CONTENEDOR DEL LOGIN
         Visible solo si el usuario no está autenticado.
         ============================================================ -->
    <div id="login-container" class="login-container d-none">
        <!-- El contenido se inyecta dinámicamente desde LoginPage.js -->
    </div>

    <!-- ============================================================
         LAYOUT PRINCIPAL (SPA)
         Visible solo si el usuario está autenticado.
         ============================================================ -->
    <div id="main-layout" class="main-layout d-none">

        <!-- Navbar (barra superior) -->
        <header id="navbar">
            <!-- Se inyecta desde Navbar.js -->
        </header>

        <!-- Contenedor flex: Sidebar + Contenido -->
        <div class="app-body">

            <!-- Sidebar (menú lateral dinámico) -->
            <aside id="sidebar" class="sidebar">
                <!-- Se inyecta desde Sidebar.js -->
            </aside>

            <!-- Contenido principal (donde se cargan las páginas) -->
            <main id="app-content" class="app-content">
                <!-- Se inyecta dinámicamente desde Router.js -->
            </main>

        </div>

        <!-- Footer (pie de página) -->
        <footer id="footer" class="footer">
            <!-- Se inyecta desde Footer.js -->
        </footer>

    </div>

    <!-- ============================================================
         CONTENEDOR DE TOASTS (alertas flotantes)
         Se llena dinámicamente desde Alert.js
         ============================================================ -->
    <div id="toast-container" class="toast-container position-fixed top-0 end-0 p-3"></div>

    <!-- ============================================================
         BOOTSTRAP JS (necesario para modales, toasts, dropdowns)
         ============================================================ -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- ============================================================
         PUNTO DE ENTRADA DE LA APLICACIÓN (módulo ES6)
         ============================================================ -->
    <script type="module" src="./assets/js/main.js"></script>

</body>
</html>
```

## Paso 1.3: Crear `public/assets/css/styles.css`

Este archivo contiene los estilos personalizados que complementan Bootstrap. Incluye:

* Variables CSS para colores y medidas.
* Estilos del **loader** (overlay con animación).
* Estilos del **layout** (navbar fija, sidebar, contenido, footer).
* Estilos del **login** (centrado, tarjeta).
* Estilos de **sidebar colapsable** (para móviles).
* Estilos de **tablas** y **badges** de estado.
* Animaciones suaves.

Crea **`public/assets/css/styles.css`** con este contenido:

```CSS
/* ============================================================
   VARIABLES GLOBALES
   ============================================================ */
:root {
    --sidebar-width: 260px;
    --sidebar-collapsed-width: 0px;
    --navbar-height: 60px;
    --footer-height: 50px;
    --primary-color: #0d6efd;
    --sidebar-bg: #1e293b;
    --sidebar-hover: #334155;
    --sidebar-text: #cbd5e1;
    --sidebar-active: #3b82f6;
    --content-bg: #f8fafc;
    --transition-speed: 0.3s;
}

/* ============================================================
   RESET Y BASE
   ============================================================ */
* {
    box-sizing: border-box;
}

html, body {
    height: 100%;
    margin: 0;
    padding: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: var(--content-bg);
}

/* ============================================================
   LOADER GLOBAL
   ============================================================ */
.global-loader {
    position: fixed;
    inset: 0;
    background-color: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(2px);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 9998;
    transition: opacity 0.2s ease-in-out;
}

.global-loader.active {
    display: flex;
    opacity: 1;
}

.global-loader .loader-content {
    text-align: center;
    background: #fff;
    padding: 2rem 3rem;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

/* ============================================================
   LOGIN
   ============================================================ */
.login-container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
    padding: 1rem;
}

.login-card {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
    width: 100%;
    max-width: 420px;
    padding: 2.5rem;
}

.login-card .login-logo {
    text-align: center;
    margin-bottom: 1.5rem;
    color: var(--primary-color);
}

.login-card .login-logo i {
    font-size: 3rem;
}

.login-card .login-logo h3 {
    font-weight: 700;
    margin-top: 0.5rem;
}

/* ============================================================
   LAYOUT PRINCIPAL
   ============================================================ */
.main-layout {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

/* ---------- NAVBAR ---------- */
#navbar {
    height: var(--navbar-height);
    position: sticky;
    top: 0;
    z-index: 1030;
}

/* ---------- APP BODY (flex: sidebar + content) ---------- */
.app-body {
    display: flex;
    flex: 1;
    overflow: hidden;
}

/* ---------- SIDEBAR ---------- */
.sidebar {
    width: var(--sidebar-width);
    background-color: var(--sidebar-bg);
    color: var(--sidebar-text);
    transition: width var(--transition-speed) ease;
    overflow-y: auto;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
}

.sidebar.collapsed {
    width: var(--sidebar-collapsed-width);
    overflow: hidden;
}

.sidebar .sidebar-header {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.sidebar .sidebar-header h5 {
    margin: 0;
    color: #fff;
    font-weight: 700;
    white-space: nowrap;
}

.sidebar .nav {
    padding: 0.5rem 0;
    flex: 1;
}

.sidebar .nav-item {
    margin: 0;
}

.sidebar .nav-link {
    color: var(--sidebar-text);
    padding: 0.75rem 1.25rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    white-space: nowrap;
    border-left: 3px solid transparent;
    transition: all 0.2s ease;
}

.sidebar .nav-link:hover {
    background-color: var(--sidebar-hover);
    color: #fff;
}

.sidebar .nav-link.active {
    background-color: rgba(59, 130, 246, 0.15);
    color: #fff;
    border-left-color: var(--sidebar-active);
    font-weight: 600;
}

.sidebar .nav-link i {
    font-size: 1.2rem;
    width: 1.5rem;
    text-align: center;
}

/* ---------- CONTENIDO PRINCIPAL ---------- */
.app-content {
    flex: 1;
    padding: 1.5rem;
    overflow-y: auto;
    background-color: var(--content-bg);
}

/* ---------- FOOTER ---------- */
.footer {
    height: var(--footer-height);
    background-color: #fff;
    border-top: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    color: #64748b;
    flex-shrink: 0;
}

/* ============================================================
   TABLAS
   ============================================================ */
.table-container {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.table-container .table {
    margin-bottom: 0;
}

.table-container .table thead th {
    background-color: #f1f5f9;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.5px;
    color: #475569;
    border-bottom: 2px solid #e2e8f0;
    white-space: nowrap;
}

.table-container .table tbody td {
    vertical-align: middle;
}

/* ============================================================
   BADGES DE ESTADO
   ============================================================ */
.badge-active {
    background-color: #10b981;
    color: #fff;
}

.badge-inactive {
    background-color: #ef4444;
    color: #fff;
}

/* ============================================================
   BOTONES DE ACCIÓN EN TABLAS
   ============================================================ */
.btn-action {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    border-radius: 4px;
}

/* ============================================================
   TOASTS
   ============================================================ */
.toast-container {
    z-index: 9999;
}

.toast {
    min-width: 280px;
}

/* ============================================================
   FORMULARIOS
   ============================================================ */
.form-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    padding: 1.5rem;
}

.form-card .form-label {
    font-weight: 600;
    font-size: 0.875rem;
    color: #334155;
}

/* ============================================================
   RESPONSIVE
   ============================================================ */
@media (max-width: 768px) {
    .sidebar {
        position: fixed;
        top: var(--navbar-height);
        left: 0;
        height: calc(100vh - var(--navbar-height));
        z-index: 1020;
        transform: translateX(-100%);
        transition: transform var(--transition-speed) ease;
    }

    .sidebar.show {
        transform: translateX(0);
    }

    .sidebar.collapsed {
        width: var(--sidebar-width);
        transform: translateX(-100%);
    }

    .app-content {
        padding: 1rem;
    }
}

/* ============================================================
   UTILIDADES
   ============================================================ */
.cursor-pointer {
    cursor: pointer;
}

.text-truncate-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
```


`active`## Paso 1.4: Crear un `main.js` temporal

Para que el `index.html` no arroje error de "módulo no encontrado", crea un `main.js` mínimo por ahora. Lo completaremos en el Paso 7.

Crea  **`public/assets/js/main.js`** :

```JavaScript
// ============================================================
// PUNTO DE ENTRADA (temporal - se completará en el Paso 7)
// ============================================================
console.log('PQRS System - Frontend inicializado correctamente.');
```

## Paso 1.5: Verificar que el layout funciona

Editar `app/Config/Routes.php`

Abre **`app/Config/Routes.php`** y busca la línea:

```PHP
$routes->get('/', 'Home::index');
```

Cámbiala por:

```PHP
// Ruta raíz: sirve el SPA (index.html)
$routes->get('/', function () {
    return file_get_contents(ROOTPATH . 'public/index.html');
});
```

**¿Por qué `ROOTPATH . 'public/index.html'`?** Porque `ROOTPATH` apunta a la raíz del proyecto CI4 (donde está `app/`, `public/`, `vendor/`, etc.). El `index.html` está dentro de `public/`.

Asegúrate de que el servidor de CI4 esté corriendo:

```Shell
php spark serve
```

Abre en tu navegador:

```
http://localhost:8080/
```

**Resultado esperado:**

* Deberías ver **solo el spinner de carga** (el overlay blanco con el spinner azul). Esto es porque aún no hay lógica que oculte el loader ni que muestre el login.
* Abre la consola del navegador (F12) y verifica que aparezca:

```
PQRS System - Frontend inicializado correctamente.
```

* Si hay errores 404 en la pestaña "Network" (por ejemplo, `styles.css` o `main.js` no encontrados), revisa que las rutas de los archivos sean correctas.

> ⚠️ **Nota sobre el loader visible:** Es normal que el loader se quede visible porque no hemos creado `Loader.js` todavía. En el Paso 2 agregaremos la lógica para ocultarlo.

## Verificación de la estructura

```
public/
├── index.html                    ✅
├── index.php                     (existente, no tocar)
├── assets/
│   ├── css/
│   │   └── styles.css            ✅
│   ├── js/
│   │   ├── main.js               ✅ (temporal)
│   │   ├── core/                 (vacía)
│   │   ├── components/           (vacía)
│   │   ├── pages/                (vacía)
│   │   └── templates/            (vacía)
│   └── img/                      (vacía)
```

## Checklist del Paso 1

* Carpeta `public/assets/` creada con subcarpetas.
* `public/index.html` creado con Bootstrap, Icons, loader, login container, main layout y toast container.
* `public/assets/css/styles.css` creado con variables, layout, loader, sidebar, tablas, formularios y responsive.
* `public/assets/js/main.js` creado con un `console.log` temporal.
* Servidor corriendo (`php spark serve`).
* `http://localhost:8080/` carga sin errores 404 en Network.
* En consola aparece "PQRS System - Frontend inicializado correctamente."
* El loader está visible (se ocultará en el Paso 2).
