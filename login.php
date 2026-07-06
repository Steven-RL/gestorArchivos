<?php
/**
 * login.php
 * Página de inicio de sesión. Único punto de entrada al gestor.
 */
require_once __DIR__ . '/includes/config.php';

// Si ya está logueado, ir directo al gestor
if (esta_logueado()) {
    header('Location: admin/panel.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1) Validar CSRF
    csrf_check($_POST['csrf'] ?? null);

    // 2) Recoger credenciales
    $usuario = trim($_POST['usuario'] ?? '');
    $pass    = $_POST['password'] ?? '';

    // 3) Verificar (comparación segura con password_verify)
    if ($usuario === ADMIN_USER && password_verify($pass, ADMIN_PASS_HASH)) {
        // Regenerar ID de sesión para evitar session fixation
        session_regenerate_id(true);
        $_SESSION['auth_user'] = $usuario;
        $_SESSION['auth_time'] = time();
        header('Location: admin/panel.php');
        exit;
    } else {
        // Mensaje genérico para no revelar si el usuario existe
        $error = 'Usuario o contraseña incorrectos.';
        // Pequeño delay para frenar fuerza bruta básica
        usleep(400000);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Iniciar sesión · Gestor de Archivos</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="css/estilos.css">
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-indigo-900 to-slate-900 flex items-center justify-center p-4">

  <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl p-8 fade-in">
    <div class="text-center mb-6">
      <div class="text-5xl mb-2">🔐</div>
      <h1 class="text-2xl font-bold text-gray-800">Gestor de Archivos</h1>
      <p class="text-gray-500 text-sm">Inicia sesión para continuar</p>
    </div>

    <?php if ($error): ?>
      <div class="bg-red-100 border border-red-300 text-red-700 px-4 py-3 rounded-lg mb-4 text-sm">
        <?= e($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Usuario</label>
        <input type="text" name="usuario" required autofocus
               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none">
      </div>

      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Contraseña</label>
        <input type="password" name="password" required
               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none">
      </div>

      <button type="submit"
              class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 rounded-lg transition-colors">
        Entrar
      </button>
    </form>

    <p class="text-xs text-gray-400 text-center mt-6">
      Credenciales por defecto: <strong>admin / Admin123*</strong><br>    
    </p>
  </div>

</body>
</html>
