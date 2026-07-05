<?php

/**
 * index.php - Vista pública
 * Muestra tabla de archivos con opción de descarga (sin login).
 * Incluye paginación y búsqueda.
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/clases/GestorArchivos.php';

global $EXTENSIONES_PERMITIDAS, $EXTENSIONES_PROHIBIDAS;
$gestor = new GestorArchivos(db(), UPLOAD_DIR, $EXTENSIONES_PERMITIDAS, $EXTENSIONES_PROHIBIDAS);

$busqueda = trim($_GET['q'] ?? '');
$pagina   = max(1, (int)($_GET['page'] ?? 1));
$limite   = 10;
$offset   = ($pagina - 1) * $limite;

$archivos = $gestor->listarPaginado($pagina, $limite, $busqueda);
$total    = $gestor->contar($busqueda);
$totalPaginas = ceil($total / $limite);

$flashes = flash_obtener();
$logueado = esta_logueado();
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestor de Archivos</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="css/estilos.css">
</head>

<body class="bg-gradient-to-br from-slate-100 to-indigo-100 min-h-screen">

  <div class="max-w-6xl mx-auto p-6">
    <!-- Encabezado -->
    <header class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg p-6 mb-8 fade-in border border-white/20">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center gap-4">
          <div class="text-4xl">📁</div>
          <div>
            <h1 class="text-2xl md:text-3xl font-bold bg-gradient-to-r from-slate-700 to-slate-900 bg-clip-text text-transparent">
              Gestor de Archivos
            </h1>
            <div class="flex items-center gap-2 text-sm text-gray-500 mt-0.5 flex-wrap">
              <p class="text-sm text-gray-500 mt-0.5">Sube, busca y administra tus archivos (máx. 10 MB)</p>
              <?php if ($logueado): ?>
                <span class="text-gray-400 hidden sm:inline">· ·</span>
                <a href="admin/panel.php" class="text-slate-600 hover:text-slate-800 font-medium transition-colors flex items-center gap-1 bg-slate-100 hover:bg-slate-200 px-3 py-1 rounded-full border border-slate-300">
                  ⚙️ Panel de Administracion
                </a>
                <span class="text-gray-400 hidden sm:inline">· ·</span>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="flex items-center gap-3 flex-wrap">
          <?php if ($logueado): ?>
            <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors shadow-sm">
              🚪 Cerrar sesión
            </a>
          <?php else: ?>
            <!-- Aviso de invitación a login (todo el bloque es un enlace) 🚀 -->
            <a href="login.php" class="group flex items-center gap-4 bg-gradient-to-r from-indigo-50/80 to-purple-50/80 backdrop-blur-sm border border-indigo-200/40 hover:border-indigo-300/60 text-slate-700 px-6 py-3 rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 hover:scale-[1.02] no-underline">
              <span class="text-2xl group-hover:scale-110 transition-transform duration-300">🔐</span>
              <div class="flex-1">
                <span class="font-semibold text-indigo-700 group-hover:text-indigo-900 transition-colors">Inicia sesión</span>
                <span class="text-sm text-slate-500 block sm:inline">para administrar archivos (subir, descargar o eliminar).</span>
              </div>
              <span class="text-indigo-400 group-hover:translate-x-1 transition-transform duration-300"><strong>➜ </strong></span>

            </a>

          <?php endif; ?>
        </div>
      </div>
    </header>

    <!-- Mensajes flash -->
    <?php foreach ($flashes as $f): ?>
      <div class="mb-4 px-4 py-3 rounded-lg slide-in
      <?= $f['tipo'] === 'ok' ? 'bg-green-100 text-green-800 border border-green-300' : 'bg-red-100 text-red-800 border border-red-300' ?>">
        <?= e($f['msg']) ?>
      </div>
    <?php endforeach; ?>

    <!-- Lista de archivos en tabla -->
    <section class="bg-white rounded-xl shadow-lg p-6 fade-in border border-slate-700">
      <h2 class="text-xl font-bold text-gray-800 mb-4">
        🗂️ Total de archivos en biblioteca: [ <?= number_format($total) ?> ]
      </h2>

      <?php if (empty($archivos)): ?>
        <p class="text-gray-500 text-center py-10">
          <?= $busqueda !== '' ? 'No se encontraron archivos para esa búsqueda.' : 'Aún no hay archivos.' ?>
        </p>
        <div class="flex justify-center">
          <a href="index.php" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-semibold">Volver
          </a>
        </div>
      <?php else: ?>
        <!-- Búsqueda -->
        <div class="bg-white rounded-xl shadow-lg p-4 mb-6 fade-in">
          <form method="GET" class="flex flex-wrap gap-2 items-center">
            <input type="search" name="q" value="<?= e($busqueda) ?>" placeholder="🔍 Buscar por nombre..."
              class="flex-1 min-w-[200px] px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            <button class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-semibold">🔍Buscar</button>
            <?php if ($busqueda !== ''): ?>
              <a href="index.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold">Limpiar</a>
            <?php endif; ?>
          </form>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full min-w-[800px] text-left">
            <thead class="bg-slate-800 text-white">
              <tr>
                <th class="px-4 py-3">Archivo</th>
                <th class="px-4 py-3">Subido por</th>
                <th class="px-4 py-3">Tamaño</th>
                <th class="px-4 py-3">Fecha</th>
                <th class="px-4 py-3 text-center">Acciones</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <?php foreach ($archivos as $a): ?>
                <tr class="hover:bg-gray-50 transition-colors">
                  <td class="px-4 py-3 whitespace-nowrap">
                    <div class="flex items-center">
                      <span class="text-2xl mr-2"><?= $a->getIcono() ?></span>
                      <div>
                        <div class="font-medium text-gray-900"><?= e($a->getNombreMostrar()) ?></div>
                        <?php if ($a->nombrePersonalizado): ?>
                          <div class="text-xs text-gray-500">Original: <?= e($a->nombreOriginal) ?></div>
                        <?php endif; ?>
                      </div>
                    </div>
                  </td>
                  <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600"><?= e($a->subidoPor) ?>
                  </td>
                  <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600"><?= e($a->getTamanoFormateado()) ?>
                  </td>

                  <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                    <div class="flex flex-col">
                      <span class="font-medium"><?= e($a->getFechaFormateada()) ?></span>
                      <span class="text-xs text-gray-400"><?= e($a->getHoraFormateada()) ?></span>
                    </div>
                  </td>

                  <td class="px-4 py-3 whitespace-nowrap text-sm text-center">
                    <div class="flex flex-col gap-1">
                    <!-- Visualizar (abre en nueva pestaña) -->
                    <a href="visualizar.php?token=<?= $a->tokenAcceso ?>" target="_blank"
                      class="bg-blue-500 hover:bg-blue-600 text-white text-xs font-semibold px-3 py-1.5 rounded-lg inline-block">
                      👁️ Visualizar
                    </a>
                    <a href="descargar.php?token=<?= $a->tokenAcceso ?>"
                      class="bg-green-500 hover:bg-green-600 text-white text-xs font-semibold px-3 py-1.5 rounded-lg inline-block">⬇️ Descargar</a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Paginación -->
        <?php if ($totalPaginas > 1): ?>
          <div class="flex justify-center items-center gap-2 mt-6">
            <?php
            $queryParams = http_build_query(array_filter(['q' => $busqueda, 'page' => $pagina - 1]));
            $urlBase = '?' . ($busqueda ? 'q=' . urlencode($busqueda) . '&' : '') . 'page=';
            ?>
            <?php if ($pagina > 1): ?>
              <a href="<?= $urlBase . ($pagina - 1) ?>" class="px-3 py-1 bg-gray-200 hover:bg-gray-300 rounded-lg text-sm"> « </a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
              <a href="<?= $urlBase . $i ?>" class="px-3 py-1 <?= $i === $pagina ? 'bg-indigo-600 text-white' : 'bg-gray-200 hover:bg-gray-300' ?> rounded-lg text-sm">
                <?= $i ?>
              </a>
            <?php endfor; ?>

            <?php if ($pagina < $totalPaginas): ?>
              <a href="<?= $urlBase . ($pagina + 1) ?>" class="px-3 py-1 bg-gray-200 hover:bg-gray-300 rounded-lg text-sm"> » </a>
            <?php endif; ?>
          </div>
        <?php endif; ?>
        <p class="text-center text-sm text-slate-500 mt-3">
          Página <?= $pagina ?> de <?= $totalPaginas ?>
        </p>
      <?php endif; ?>
    </section>

    <footer class="text-center text-gray-500 text-xs mt-8">
      Gestor de Archivos PHP · POO · MySQL · Tailwind CSS
    </footer>
  </div>
</body>

</html>