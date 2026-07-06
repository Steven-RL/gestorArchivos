<?php
// ============================================================
// credenciales_admin.php — Credenciales del panel de administración
// ============================================================

// Usuario administrador
define('ADMIN_USER', 'admin');

// Hash de la contraseña (Admin123*)
// Para generar un nuevo hash: php -r "echo password_hash('TuClave', PASSWORD_DEFAULT);"
define('ADMIN_PASS_HASH', '$2y$12$mkSRTdk40UeXGOgOhNP/FuAG9.Xvdp2LvPn9OL.7Gny8MtkaKzq02');

// Tiempo máximo de sesión en segundos (30 minutos) - Opcional
define('SESSION_LIFETIME', 1800);

?>