<?php
/**
 * eliminar.php — Borra archivo físico + registro BD.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../clases/GestorArchivos.php';
requerir_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: panel.php');
    exit;
}
csrf_check($_POST['csrf'] ?? null);

$id = (int)($_POST['id'] ?? 0);

global $EXTENSIONES_PERMITIDAS, $EXTENSIONES_PROHIBIDAS;
$gestor = new GestorArchivos(db(), UPLOAD_DIR, $EXTENSIONES_PERMITIDAS, $EXTENSIONES_PROHIBIDAS);

$r = $gestor->eliminar($id);
$return_url = $_POST['return_url'] ?? 'panel.php';
flash($r['ok'] ? 'ok' : 'error', $r['msg']);
header('Location: ' . $return_url);

exit;
