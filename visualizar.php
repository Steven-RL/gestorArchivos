<?php
/**
 * visualizar.php — Muestra el archivo en el navegador (inline).
 * Útil para imágenes, PDF, videos, audio.
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

// Obtener el tipo MIME real del archivo (para enviar el header correcto)
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $ruta);
finfo_close($finfo);

// Si no se detecta MIME, usar application/octet-stream
if (!$mime) {
    $mime = 'application/octet-stream';
}

// Nombre para el archivo (usamos el nombre original o alias)
$nombreDescarga = $archivo->getNombreMostrar();
$nombreDescarga = str_replace(["\r","\n","\""], '', $nombreDescarga);

// Cabeceras para visualizar inline (en lugar de descarga forzada)
header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . $nombreDescarga . '"');
header('Content-Length: ' . filesize($ruta));
header('Cache-Control: public, max-age=86400'); // cache por 1 día
header('X-Content-Type-Options: nosniff');

// Para imágenes, PDF, etc., el navegador mostrará el contenido directamente
readfile($ruta);
exit;