<?php
/**
 * subir.php — Procesa la subida de un archivo.
 * Requiere: sesión activa, token CSRF, POST con $_FILES['archivo'].
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../clases/GestorArchivos.php';
requerir_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: panel.php');
    exit;
}

csrf_check($_POST['csrf'] ?? null);

global $EXTENSIONES_PERMITIDAS, $EXTENSIONES_PROHIBIDAS;
$gestor = new GestorArchivos(db(), UPLOAD_DIR, $EXTENSIONES_PERMITIDAS, $EXTENSIONES_PROHIBIDAS);

$nombrePersonalizado = $_POST['nombre_personalizado'] ?? null;
$resultado = $gestor->subir($_FILES['archivo'] ?? [], $nombrePersonalizado, usuario_actual());

$return_url = $_POST['return_url'] ?? 'panel.php';
flash($resultado['ok'] ? 'ok' : 'error', $resultado['msg']);
header('Location: ' . $return_url);
exit;
