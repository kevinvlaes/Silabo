<?php
// ---------------------------------------------
// Bootstrap básico
// ---------------------------------------------
ini_set('display_errors', '1');
error_reporting(E_ALL);

// Zona horaria (ajusta si corresponde)
date_default_timezone_set('America/Lima');

// Sesión (solo una vez)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Acción por defecto
$action = $_GET['action'] ?? 'loginForm';

switch ($action) {
    /* ---------- AUTENTICACIÓN ---------- */
    case 'loginForm':
        require_once __DIR__ . '/../app/Controllers/AuthController.php';
        (new AuthController())->loginForm();
        break;

    case 'login':
        require_once __DIR__ . '/../app/Controllers/AuthController.php';
        $ctl = new AuthController();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // nombres posibles desde el form
            $email = trim($_POST['email'] ?? $_POST['correo'] ?? '');
            $pass  = trim($_POST['password'] ?? $_POST['contrasena'] ?? $_POST['clave'] ?? '');
            $ctl->login($email, $pass); // método con 2 argumentos
        } else {
            $ctl->loginForm();
        }
        break;

    case 'logout':
        require_once __DIR__ . '/../app/Controllers/AuthController.php';
        (new AuthController())->logout();
        break;

    /* ---------- DASHBOARD ---------- */
    case 'dashboard':
        require_once __DIR__ . '/../app/Controllers/DashboardController.php';
        (new DashboardController())->index();
        break;

    /* ---------- SÍLABOS ---------- */
    case 'subirForm':
        require_once __DIR__ . '/../app/Controllers/SilaboController.php';
        (new SilaboController())->subirForm();
        break;

    case 'subir':
        require_once __DIR__ . '/../app/Controllers/SilaboController.php';
        (new SilaboController())->subir();
        break;

    /* Alias histórico: antes existía "actualizar"; ahora usamos "reemplazar" */
    case 'silabo_actualizar':
        require_once __DIR__ . '/../app/Controllers/SilaboController.php';
        (new SilaboController())->reemplazar();
        break;

    case 'silabo_reemplazar':
        require_once __DIR__ . '/../app/Controllers/SilaboController.php';
        (new SilaboController())->reemplazar();
        break;

    case 'consulta':
        require_once __DIR__ . '/../app/Controllers/SilaboController.php';
        (new SilaboController())->consulta();
        break;

    // API usada por la vista de SUBIR (docente/coordinador) para listar UDs
    case 'api_unidades':
        require_once __DIR__ . '/../app/Controllers/SilaboController.php';
        (new SilaboController())->api_unidades();
        break;

    /* ---------- USUARIOS ---------- */
    case 'lugares': // ignora si no la usas; mantenido por compatibilidad
    case 'usuarios':
        require_once __DIR__ . '/../app/Controllers/UsuarioController.php';
        (new UsuarioController())->index();
        break;

    case 'usuario_crear':
        require_once __DIR__ . '/../app/Controllers/UsuarioController.php';
        (new UsuarioController())->crear();
        break;

    case 'usuario_eliminar':
        require_once __DIR__ . '/../app/Controllers/UsuarioController.php';
        (new UsuarioController())->eliminar();
        break;

    case 'usuario_actualizar':
        require_once __DIR__ . '/../app/Controllers/UsuarioController.php';
        (new UsuarioController())->actualizar();
        break;

    /* ---------- AÑOS ACADÉMICOS ---------- */
    case 'anios':
        require_once __DIR__ . '/../app/Controllers/AnioController.php';
        (new AnioController())->index();
        break;

    case 'anio_crear':
        require_once __DIR__ . '/../app/Controllers/AnioController.php';
        (new AnioController())->crear();
        break;

    case 'anio_eliminar':
        require_once __DIR__ . '/../app/Controllers/AnioController.php';
        (new AnioController())->eliminar();
        break;

    /* ---------- PERIODO DE SUBIDA ---------- */
    case 'periodo':
        require_once __DIR__ . '/../app/Controllers/PeriodoController.php';
        (new PeriodoController())->index();
        break;

    case 'periodo_guardar':
        require_once __DIR__ . '/../app/Controllers/PeriodoController.php';
        (new PeriodoController())->guardar();
        break;

    /* ---------- ASIGNACIONES DE UD (JEFE) ---------- */
    case 'asignaciones':
        require_once __DIR__ . '/../app/Controllers/AsignacionController.php';
        (new AsignacionController())->index();
        break;

    case 'asignacion_crear':
        require_once __DIR__ . '/../app/Controllers/AsignacionController.php';
        (new AsignacionController())->crear();
        break;

    case 'asignacion_eliminar':
        require_once __DIR__ . '/../app/Controllers/AsignacionController.php';
        (new AsignacionController())->eliminar();
        break;

    /* --- NUEVO: API para poblar el combo "Unidad Didáctica" en ASIGNACIONES --- */
    // Usa este si tu JS llama a action=api_ud_asignaciones
    case 'api_ud_asignaciones':
        require_once __DIR__ . '/../app/Controllers/AsignacionController.php';
        (new AsignacionController())->api_unidades(); // o api_ud_asignaciones() si ese es tu método
        break;

    // Alias adicional por compatibilidad (por si la vista usa otro nombre)
    case 'asig_api_unidades':
        require_once __DIR__ . '/../app/Controllers/AsignacionController.php';
        (new AsignacionController())->api_unidades();
        break;

    /* ---------- DEFAULT ---------- */
    default:
        // Si hay sesión, envía al dashboard; si no, muestra login
        if (!empty($_SESSION['usuario'])) {
            header('Location: index.php?action=dashboard');
            exit;
        }
        require_once __DIR__ . '/../app/Controllers/AuthController.php';
        (new AuthController())->loginForm();
        break;
}
