<?php
/**
 * descargar.php — Envía el archivo al cliente como descarga forzada.
 * - Verifica sesión (opcional)
 * - Busca por ID en BD
 * - Valida ruta segura (anti Path Traversal)
 * - Usa el nombre personalizado o el original al descargar, siempre con la extensión correcta
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/clases/GestorArchivos.php';

$token = $_GET['token'] ?? '';
if ($token === '') {
    http_response_code(400);
    die('Token no proporcionado.');
}

global $EXTENSIONES_PERMITIDAS, $EXTENSIONES_PROHIBIDAS;
$gestor = new GestorArchivos(db(), UPLOAD_DIR, $EXTENSIONES_PERMITIDAS, $EXTENSIONES_PROHIBIDAS);

$archivo = $gestor->obtenerPorToken($token);
if (!$archivo) {
    http_response_code(404);
    die('Archivo no encontrado.');
}

$ruta = $gestor->rutaSegura($archivo->nombreFisico);
if ($ruta === null || !is_file($ruta)) {
    http_response_code(404);
    die('Archivo no disponible.');
}

// Nombre base (alias o original) y extensión
$nombreBase = $archivo->getNombreMostrar();
$nombreBase = str_replace(["\r","\n","\""], '', $nombreBase);
$extension = $archivo->extension; // extensión real del archivo
$nombreDescarga = $nombreBase . '.' . $extension;

// Headers de descarga segura
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream'); // siempre genérico para descarga
header('Content-Disposition: attachment; filename="' . $nombreDescarga . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($ruta));
header('X-Content-Type-Options: nosniff');

readfile($ruta);
exit;