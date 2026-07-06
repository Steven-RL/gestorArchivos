<?php

/**
 * admin.php - Panel de administración (requiere login)
 * Muestra tabla de archivos con opciones de subir, renombrar y eliminar.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../clases/GestorArchivos.php';
requerir_login();

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
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de administración</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../css/estilos.css">
</head>

<body class="bg-gradient-to-br from-slate-100 to-indigo-100 min-h-screen">

    <div class="max-w-6xl mx-auto px-4 py-8">

        <!-- Encabezado -->
        <header class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg p-6 mb-8 fade-in border border-white/20">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="text-4xl">🔐</div>
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold bg-gradient-to-r from-slate-700 to-slate-900 bg-clip-text text-transparent">
                            Panel de Administración
                        </h1>
                        <div class="flex items-center gap-2 text-sm text-gray-500 mt-0.5 flex-wrap">
                            <span class="bg-slate-200 text-slate-700 px-3 py-0.5 rounded-full text-xs font-medium">
                                👤 <?= e(usuario_actual()) ?>
                            </span>
                            <span class="text-gray-400 hidden sm:inline">· ·</span>
                            <a href="../index.php" class="text-slate-600 hover:text-slate-800 font-medium transition-colors flex items-center gap-1 bg-slate-100 hover:bg-slate-200 px-3 py-1 rounded-full border border-slate-300">
                                <span>👁️</span> Ver vista pública
                            </a>
                            <span class="text-gray-400 hidden sm:inline">· ·</span>
                        </div>
                    </div>
                </div>

                <a href="../logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors shadow-sm">
                    🚪 Cerrar sesión
                </a>
            </div>
        </header>

        <!-- Mensajes flash -->
        <?php foreach ($flashes as $f): ?>
            <div class="mb-4 px-4 py-3 rounded-lg slide-in
      <?= $f['tipo'] === 'ok' ? 'bg-green-100 text-green-800 border border-green-300' : 'bg-red-100 text-red-800 border border-red-300' ?>">
                <?= e($f['msg']) ?>
            </div>
        <?php endforeach; ?>

        <!-- ============ FORMULARIO DE SUBIDA (DROPZONE + ALIAS) ============ -->
        <section class="bg-white backdrop-blur p-6 rounded-2xl shadow-xl border border-slate-700 mb-8 fade-in">
            <h2 class="text-xl font-semibold mb-4">⬆️ Subir archivo</h2>
            <form action="subir.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                <!-- Token CSRF: nombre 'csrf' para que lo valide subir.php -->
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

                <!-- Dropzone -->
                <label class="block">
                    <div class="dropzone border-2 border-dashed border-slate-600 hover:border-cyan-400
                            rounded-xl p-8 text-center cursor-pointer transition">
                        <div class="text-4xl mb-2">📤</div>
                        <p class="text-slate-700">Haz clic o arrastra un archivo aquí</p>
                        <p class="text-xs text-slate-500 mt-1">Máximo 10 MB · Formatos permitidos: Imágenes, PDF, Office · </p>
                        <input type="file" name="archivo" required class="hidden" id="inputArchivo">
                        <p id="nombreArchivo" class="text-cyan-400 text-sm mt-3"></p>
                    </div>
                </label>

                <!-- Campo nombre personalizado (alias) -->
                <div>
                    <label for="nombre_personalizado" class="block text-sm font-medium text-slate-800 mb-1">
                        Nombre personalizado <span class="text-slate-500">(opcional)</span>
                    </label>
                    <input type="text" name="nombre_personalizado" id="nombre_personalizado"
                        maxlength="150" placeholder="Ej: Informe final auditoría"
                        class="w-full px-4 py-2 bg-white border border-slate-600 rounded-lg
                              focus:ring-2 focus:ring-cyan-400 focus:outline-none text-gray-800 placeholder-slate-400">
                </div>

                <button type="submit"
                    class="w-full bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500
                           text-white font-semibold py-3 rounded-lg transition shadow-lg">
                    Subir archivo
                </button>
            </form>
        </section>

        <!-- Lista de archivos en tabla con acciones admin -->
        <section class="bg-white rounded-xl shadow-lg p-6 fade-in border border-slate-700">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
                🗂️ Total de archivos en biblioteca: [ <?= number_format($total) ?> ]
            </h2>

            <?php if (empty($archivos)): ?>
                <p class="text-gray-500 text-center py-10">
                    <?= $busqueda !== '' ? 'No se encontraron archivos para esa búsqueda.' : 'Aún no has subido archivos.' ?>
                </p>
                <div class="flex justify-center">
                    <a href="panel.php" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-semibold">Volver
                    </a>
                </div>
            <?php else: ?>
                <!-- Búsqueda -->
                <div class="bg-white rounded-xl shadow-lg p-4 mb-6 fade-in">

                    <form method="GET" action="panel.php" class="flex flex-wrap gap-2 items-center">

                        <input type="search" name="q" value="<?= e($busqueda) ?>" placeholder="🔍 Buscar por nombre..."
                            class="flex-1 min-w-[200px] px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <button class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-semibold">🔍 Buscar</button>
                        <?php if ($busqueda !== ''): ?>
                            <a href="panel.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold">Limpiar
                            </a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[800px] text-left">
                        <thead class="bg-slate-800 text-white">
                            <tr>
                                <th class="px-4 py-3">📋 Archivo</th>
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
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600"><?= e($a->subidoPor) ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600"><?= e($a->getTamanoFormateado()) ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                                        <div class="flex flex-col">
                                            <span class="font-medium"><?= e($a->getFechaFormateada()) ?></span>
                                            <span class="text-xs text-gray-400"><?= e($a->getHoraFormateada()) ?></span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-center">
    <div class="flex flex-col gap-1">
        <!-- Fila 1: Visualizar + Descargar -->
        <div class="flex items-center justify-center gap-1">
            <a href="../visualizar.php?token=<?= $a->tokenAcceso ?>" target="_blank"
               class="bg-blue-500 hover:bg-blue-600 text-white text-xs font-semibold px-3 py-1.5 rounded-lg inline-block" title="Visualizar archivo">
                👁️ Visualizar
            </a>
            <a href="../descargar.php?token=<?= $a->tokenAcceso ?>"
               class="bg-green-500 hover:bg-green-600 text-white text-xs font-semibold px-3 py-1.5 rounded-lg inline-block">
                ⬇️ Descargar
            </a>
        </div>
        <!-- Fila 2: Renombrar + Eliminar -->
        <div class="flex items-center justify-center gap-1">
            <form action="renombrar.php" method="POST" class="inline"
                  onsubmit="const n = prompt('Nuevo nombre personalizado (vacío para quitar el alias):', '<?= e($a->nombrePersonalizado ?? '') ?>'); if (n === null) return false; this.nuevo_nombre.value = n; return true;">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int)$a->id ?>">
                <input type="hidden" name="nuevo_nombre" value="">
                <input type="hidden" name="return_url" value="<?= e($_SERVER['REQUEST_URI']) ?>">
                <button class="bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold px-3 py-1.5 rounded-lg">✏️ Renombrar</button>
            </form>
            <form action="eliminar.php" method="POST" class="inline"
                  onsubmit="return confirm('¿Eliminar este archivo? Esta acción no se puede deshacer.');">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int)$a->id ?>">
                <input type="hidden" name="return_url" value="<?= e($_SERVER['REQUEST_URI']) ?>">
                <button class="bg-red-500 hover:bg-red-600 text-white text-xs font-semibold px-3 py-1.5 rounded-lg">🗑️ Eliminar</button>
            </form>
        </div>
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

    <!-- ============ SCRIPT DROPZONE ============ -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const inputFile = document.getElementById('inputArchivo');
            const nombreArchivo = document.getElementById('nombreArchivo');
            const dropzone = document.querySelector('.dropzone');

            if (inputFile && nombreArchivo && dropzone) {
                // Mostrar nombre al seleccionar
                inputFile.addEventListener('change', function() {
                    if (this.files.length > 0) {
                        nombreArchivo.textContent = '📎 ' + this.files[0].name;
                    } else {
                        nombreArchivo.textContent = '';
                    }
                });

                // Arrastrar y soltar (sin evento click adicional)
                dropzone.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    this.classList.add('border-cyan-400', 'bg-slate-700/30');
                });
                dropzone.addEventListener('dragleave', function(e) {
                    e.preventDefault();
                    this.classList.remove('border-cyan-400', 'bg-slate-700/30');
                });
                dropzone.addEventListener('drop', function(e) {
                    e.preventDefault();
                    this.classList.remove('border-cyan-400', 'bg-slate-700/30');
                    if (e.dataTransfer.files.length > 0) {
                        inputFile.files = e.dataTransfer.files;
                        nombreArchivo.textContent = '📎 ' + e.dataTransfer.files[0].name;
                    }
                });
            }
        });
    </script>

</body>

</html>