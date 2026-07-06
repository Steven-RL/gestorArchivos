<?php
/**
 * =====================================================================
 * CONFIGURACIÓN GLOBAL DEL GESTOR DE ARCHIVOS
 * =====================================================================
 * Este archivo centraliza:
 *  - Configuración de sesión segura (HttpOnly, SameSite, etc.)
 *  - Cabeceras HTTP de seguridad (CSP, X-Frame-Options, etc.)
 *  - Conexión a la base de datos MySQL (PDO)
 *  - Credenciales del usuario admin (único usuario autorizado)
 *  - Funciones helper: escape XSS, CSRF, login, logout
 * =====================================================================
 */
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/credenciales_admin.php';

// ZONA HORARIA y reporte de errores
// ---------------------------------------------------------------------
date_default_timezone_set('America/Bogota');
error_reporting(E_ALL);
ini_set('display_errors', '1'); // En producción real ponlo en '0'

// SESIÓN SEGURA
//    - HttpOnly: la cookie no es accesible por JS (anti-XSS)
//    - SameSite=Strict: anti-CSRF a nivel de cookie
//    - use_strict_mode: evita session fixation

// Configurar opciones de sesión ANTES de iniciarla
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_samesite', 'Strict');

    // Si se definió SESSION_LIFETIME, ajustar tiempos de sesión
    if (defined('SESSION_LIFETIME')) {
        ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
        ini_set('session.cookie_lifetime', SESSION_LIFETIME);
    }

    // Ahora iniciar la sesión con un nombre personalizado
    session_name('GESTORSESS');
    session_start();
}

// LÍMITES Y EXTENSIONES PERMITIDAS
// ---------------------------------------------------------------------
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10 MB
define('UPLOAD_DIR', __DIR__ . '/../uploads/');

// Whitelist: solo estas extensiones se aceptan
$EXTENSIONES_PERMITIDAS = [
    'jpg','jpeg','png',
    'pdf','doc','docx'
];

// Blacklist: bloqueo absoluto, nunca permitir
$EXTENSIONES_PROHIBIDAS = [
    'php','php3','php4','php5','phtml','phar',
    'exe','sh','bat','cmd','com','msi',
    'js','jsp','asp','aspx','cgi','pl','py','rb','htaccess'
];

// CABECERAS DE SEGURIDAD HTTP
// ---------------------------------------------------------------------
header('X-Content-Type-Options: nosniff');     // No "adivinar" tipos MIME
header('X-Frame-Options: DENY');               // Anti clickjacking
header('Referrer-Policy: no-referrer');        // No filtrar URL al salir
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com; script-src 'self' https://cdn.tailwindcss.com 'unsafe-inline'; font-src 'self' data:;");

// 5) CONEXIÓN A BASE DE DATOS (PDO con manejo de errores y prepared statements)

function db(): PDO {
    return conectarBD();
}

// 6) HELPER ANTI-XSS
//    Escapa toda salida HTML para evitar inyección de scripts.

function e(?string $valor): string {
    return htmlspecialchars($valor ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

//  TOKEN CSRF
//    Generamos un token único por sesión y lo validamos en cada acción
//    que modifica el estado (subir, eliminar, renombrar, login).

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_check(?string $token): void {
    if (empty($token) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
        http_response_code(403);
        die('Token CSRF inválido. Recarga la página e intenta de nuevo.');
    }
}

// FUNCIONES DE AUTENTICACIÓN
// 
function esta_logueado(): bool {
    return !empty($_SESSION['auth_user']);
}

function requerir_login(): void {
    if (!esta_logueado()) {
        header('Location: login.php');
        exit;
    }
}

function usuario_actual(): ?string {
    return $_SESSION['auth_user'] ?? null;
}

// FLASH MESSAGES (mensajes de un solo uso)
// 

function flash(string $tipo, string $mensaje): void {
    $_SESSION['flash'][] = ['tipo' => $tipo, 'msg' => $mensaje];
}

function flash_obtener(): array {
    $msgs = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $msgs;
}
