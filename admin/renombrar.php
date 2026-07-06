<?php
/**
 * renombrar.php — Cambia el alias visible del archivo.
 * NO toca el nombre original ni el archivo físico.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../clases/GestorArchivos.php';
requerir_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: panel.php');
    exit;
}
csrf_check($_POST['csrf'] ?? null);

$id    = (int)($_POST['id'] ?? 0);
$nuevo = (string)($_POST['nuevo_nombre'] ?? '');

global $EXTENSIONES_PERMITIDAS, $EXTENSIONES_PROHIBIDAS;
$gestor = new GestorArchivos(db(), UPLOAD_DIR, $EXTENSIONES_PERMITIDAS, $EXTENSIONES_PROHIBIDAS);

if ($id <= 0 || !$gestor->obtenerPorId($id)) {
    flash('error', 'Archivo no encontrado.');
} else {
    $r = $gestor->renombrar($id, $nuevo);
    flash($r['ok'] ? 'ok' : 'error', $r['msg']);
}

$return_url = $_POST['return_url'] ?? 'panel.php';
header('Location: ' . $return_url);
exit;
