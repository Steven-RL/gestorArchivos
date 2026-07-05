<?php
require_once __DIR__ . '/Archivo.php';

/**
 * Clase GestorArchivos
 * --------------------
 * Centraliza toda la lógica de negocio:
 *  - subir():     valida y guarda un archivo en /uploads + registro en BD
 *  - listar():    devuelve archivos desde BD (con búsqueda opcional)
 *  - renombrar(): cambia el alias visible (nombre_personalizado) sin
 *                 tocar el nombre original ni el archivo físico
 *  - eliminar():  borra archivo físico + registro en BD
 *  - obtenerRutaDescarga(): valida y devuelve la ruta segura
 *
 * SEGURIDAD aplicada:
 *  - Validación de extensión (whitelist + blacklist)
 *  - Validación de tamaño máximo
 *  - Validación de MIME real con finfo (anti spoofing)
 *  - Anti Path Traversal con basename() + realpath()
 *  - Nombre físico aleatorio (no expone el original al filesystem)
 *  - Prepared statements en TODAS las consultas (anti SQL Injection)
 */
class GestorArchivos {

    private PDO $pdo;
    private string $directorio;
    private array $extPermitidas;
    private array $extProhibidas;

    public function __construct(PDO $pdo, string $directorio, array $extPermitidas, array $extProhibidas) {
        $this->pdo = $pdo;
        $this->directorio = rtrim($directorio, '/') . '/';
        $this->extPermitidas = $extPermitidas;
        $this->extProhibidas = $extProhibidas;

        // Crear /uploads si no existe
        if (!is_dir($this->directorio)) {
            mkdir($this->directorio, 0755, true);
        }
    }

    /**
     * Sube un archivo: lo valida, lo mueve a /uploads con un nombre físico
     * único y registra los metadatos en la BD.
     */
    public function subir(array $archivo, ?string $nombrePersonalizado, string $usuario): array {
        // 1) Verificar que no haya error en la subida
        if (!isset($archivo['error']) || $archivo['error'] !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'msg' => 'Error al recibir el archivo.'];
        }

        // 2) Validar tamaño
        if ($archivo['size'] > MAX_FILE_SIZE) {
            return ['ok' => false, 'msg' => 'El archivo supera el límite de 10 MB.'];
        }

        // 3) Sanitizar nombre original (anti Path Traversal)
        $nombreOriginal = basename($archivo['name']);
        $nombreOriginal = preg_replace('/[^\w\-. ]+/u', '_', $nombreOriginal);

        // 4) Validar extensión (whitelist + blacklist)
        $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
        if ($extension === '' || in_array($extension, $this->extProhibidas, true)) {
            return ['ok' => false, 'msg' => "Extensión .{$extension} no permitida."];
        }
        // En la validación de extensión:
        if (!in_array($extension, $this->extPermitidas, true)) {
            return ['ok' => false, 'msg' => "! Cuidado extensión .{$extension} no permitida. Solo se aceptan PDF, JPG y PNG, DOC, DOCX. !"];
        }

        // 5) Validar tipo MIME REAL (anti spoofing: .jpg que es realmente .php)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeReal = $finfo->file($archivo['tmp_name']);
        // ... después de obtener $mimeReal
        if (!$this->mimeCoincideConExtension($extension, $mimeReal)) {
            return ['ok' => false, 'msg' => "El contenido del archivo no coincide con la extensión .{$extension}."];
        }
        if ($mimeReal === false) {
            return ['ok' => false, 'msg' => 'No se pudo verificar el tipo del archivo.'];
        }

        // 6) Generar nombre físico aleatorio (no expone el nombre real)
        $nombreFisico = bin2hex(random_bytes(16)) . '.' . $extension;
        $rutaDestino = $this->directorio . $nombreFisico;

        // 7) Mover archivo de forma segura
        if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
            return ['ok' => false, 'msg' => 'No se pudo guardar el archivo en el servidor.'];
        }

        // 8) Sanitizar nombre personalizado (si lo hay)
        $nombreSinExtension = pathinfo($nombreOriginal, PATHINFO_FILENAME);
        $aliasBase = null;

        // Si el usuario proporcionó alias
        if ($nombrePersonalizado !== null && trim($nombrePersonalizado) !== '') {
            $aliasUsuario = trim($nombrePersonalizado);
            $aliasUsuario = preg_replace('/[^\w\-. ]+/u', '_', $aliasUsuario);
            $aliasUsuario = mb_substr($aliasUsuario, 0, 150);

            // Si el alias es igual al nombre original (sin extensión), se anula
            if ($aliasUsuario === $nombreSinExtension) {
                $aliasBase = null;
            } else {
                $aliasBase = $aliasUsuario;
            }
        }

        // Si no hay alias base, usar el nombre original como base
        if ($aliasBase === null) {
            $aliasBase = $nombreSinExtension;
        }

        // Generar alias único (con número si ya existe)
        $personalizado = $this->generarAliasUnico($aliasBase, $extension, null);

        $tokenAcceso = bin2hex(random_bytes(32));
        // 9) Registrar metadatos en BD con prepared statement
        $stmt = $this->pdo->prepare(
            "INSERT INTO archivos (nombre_original, nombre_personalizado, nombre_fisico, tamano, extension, mime, subido_por, fecha_subida, token_acceso)
             VALUES (:no, :np, :nf, :tam, :ext, :mime, :usr, NOW(), :token)"
        );
        $stmt->execute([
            ':no'   => $nombreOriginal,
            ':np'   => $personalizado,
            ':nf'   => $nombreFisico,
            ':tam'  => (int)$archivo['size'],
            ':ext'  => $extension,
            ':mime' => $mimeReal,
            ':usr'  => $usuario,
            ':token'=> $tokenAcceso,
        ]);

        return ['ok' => true, 'msg' => 'Archivo subido correctamente.'];
    }

    /**
     * Lista archivos desde la BD, con búsqueda opcional por nombre
     * original o personalizado (LIKE seguro con prepared statement).
     */
    public function listar(string $busqueda = ''): array {
        if ($busqueda !== '') {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM archivos
                 WHERE nombre_original LIKE :q OR nombre_personalizado LIKE :q
                 ORDER BY fecha_subida DESC"
            );
            $stmt->execute([':q' => '%' . $busqueda . '%']);
        } else {
            $stmt = $this->pdo->query("SELECT * FROM archivos ORDER BY fecha_subida DESC");
        }

        $resultado = [];
        foreach ($stmt->fetchAll() as $fila) {
            $resultado[] = new Archivo(
                (int)$fila['id'],
                $fila['nombre_original'],
                $fila['nombre_personalizado'],
                $fila['nombre_fisico'],
                (int)$fila['tamano'],
                $fila['extension'],
                $fila['fecha_subida'],
                $fila['subido_por'],
                $fila['token_acceso']
            );
        }
        return $resultado;
    }

    /**
     * Busca un archivo por ID. Devuelve null si no existe.
     */
    public function obtenerPorId(int $id): ?Archivo {
        $stmt = $this->pdo->prepare("SELECT * FROM archivos WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        if (!$fila) return null;
        return new Archivo(
            (int)$fila['id'],
            $fila['nombre_original'],
            $fila['nombre_personalizado'],
            $fila['nombre_fisico'],
            (int)$fila['tamano'],
            $fila['extension'],
            $fila['fecha_subida'],
            $fila['subido_por'],
            $fila['token_acceso']
        );
    }

    /**
     * Cambia el alias del archivo (sin tocar el original ni el físico).
     */
    public function renombrar(int $id, string $nuevoNombre): array
    {
        // Obtener el archivo actual
        $archivo = $this->obtenerPorId($id);
        if (!$archivo) {
            return ['ok' => false, 'msg' => 'Archivo no encontrado.'];
        }

        $nuevoNombre = trim($nuevoNombre);
        if ($nuevoNombre === '') {
            // Si queda vacío, se borra el alias
            $personalizado = null;
        } else {
            // Sanitizar alias
            $aliasUsuario = preg_replace('/[^\w\-. ]+/u', '_', $nuevoNombre);
            $aliasUsuario = mb_substr($aliasUsuario, 0, 150);

            // Si el alias es igual al nombre original (sin extensión), se anula
            $nombreSinExtension = pathinfo($archivo->nombreOriginal, PATHINFO_FILENAME);
            if ($aliasUsuario === $nombreSinExtension) {
                $aliasBase = $nombreSinExtension;
            } else {
                $aliasBase = $aliasUsuario;
            }

            // Generar alias único (excluyendo el propio archivo)
            $personalizado = $this->generarAliasUnico($aliasBase, $archivo->extension, $id);
        }

        // Actualizar en BD
        $stmt = $this->pdo->prepare("UPDATE archivos SET nombre_personalizado = :np WHERE id = :id");
        $stmt->execute([':np' => $personalizado, ':id' => $id]);

        return ['ok' => true, 'msg' => 'Nombre actualizado.'];
    }

    /**
     * Elimina archivo físico y registro en BD.
     */
    public function eliminar(int $id): array {
        $archivo = $this->obtenerPorId($id);
        if (!$archivo) return ['ok' => false, 'msg' => 'Archivo no encontrado.'];

        $ruta = $this->rutaSegura($archivo->nombreFisico);
        if ($ruta !== null && is_file($ruta)) {
            @unlink($ruta);
        }
        $stmt = $this->pdo->prepare("DELETE FROM archivos WHERE id = :id");
        $stmt->execute([':id' => $id]);

        return ['ok' => true, 'msg' => 'Archivo eliminado.'];
    }

    /**
     * Devuelve una ruta absoluta SOLO si está dentro del directorio /uploads.
     * Esto evita ataques tipo ../../etc/passwd.
     */
    public function rutaSegura(string $nombreFisico): ?string {
        $nombreFisico = basename($nombreFisico); // anti traversal
        $ruta = realpath($this->directorio . $nombreFisico);
        $base = realpath($this->directorio);
        if ($ruta === false || $base === false) return null;
        if (!str_starts_with($ruta, $base)) return null; // fuera de /uploads → bloqueado
        return $ruta;
    }

     /** Devuelve un array de objetos Archivo para una página específica.
     * @param int $pagina Número de página (1-indexed)
     * @param int $limite Cantidad de registros por página
     * @param string $busqueda Término de búsqueda (opcional)
     * @return Archivo[]
     */

    public function listarPaginado(int $pagina, int $limite, string $busqueda = ''): array
    {
        $offset = (int)(($pagina - 1) * $limite);
        $limite = (int)$limite;

        if ($busqueda !== '') {
            $sql = "SELECT * FROM archivos
                WHERE nombre_original LIKE :q1 OR nombre_personalizado LIKE :q2
                ORDER BY fecha_subida DESC
                LIMIT {$offset}, {$limite}";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':q1', '%' . $busqueda . '%', PDO::PARAM_STR);
            $stmt->bindValue(':q2', '%' . $busqueda . '%', PDO::PARAM_STR);
        } else {
            $sql = "SELECT * FROM archivos ORDER BY fecha_subida DESC LIMIT {$offset}, {$limite}";
            $stmt = $this->pdo->prepare($sql);
        }
        $stmt->execute();

        $resultado = [];
        foreach ($stmt->fetchAll() as $fila) {
            $resultado[] = new Archivo(
                (int)$fila['id'],
                $fila['nombre_original'],
                $fila['nombre_personalizado'],
                $fila['nombre_fisico'],
                (int)$fila['tamano'],
                $fila['extension'],
                $fila['fecha_subida'],
                $fila['subido_por'],
                $fila['token_acceso']
            );
        }
        return $resultado;
    }

    /**
     * Cuenta el total de registros (con o sin búsqueda).
     * @param string $busqueda Término de búsqueda (opcional)
     * @return int
     */
    public function contar(string $busqueda = ''): int
    {
        if ($busqueda !== '') {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM archivos
             WHERE nombre_original LIKE :q1 OR nombre_personalizado LIKE :q2"
            );
            $stmt->bindValue(':q1', '%' . $busqueda . '%', PDO::PARAM_STR);
            $stmt->bindValue(':q2', '%' . $busqueda . '%', PDO::PARAM_STR);
            $stmt->execute();
        } else {
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM archivos");
        }
        return (int)$stmt->fetchColumn();
    }

    /**
     * Verifica que el MIME real sea compatible con la extensión esperada.
     * Solo para PDF, JPG y PNG.
     */
    private function mimeCoincideConExtension(string $extension, string $mime): bool
    {
        $mapa = [
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'pdf'  => 'application/pdf',
            'doc'  => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];

        // Si la extensión no está en el mapa (no debería, porque ya pasó whitelist)
        if (!isset($mapa[$extension])) {
            return false; // o true si quieres permitir otras, pero mejor false
        }

        return $mime === $mapa[$extension];
    }

    /**
     * Cuenta cuántos archivos tienen exactamente el mismo nombre original.
     */
    private function contarDuplicadosPorNombreOriginal(string $nombreOriginal): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM archivos WHERE nombre_original = :no");
        $stmt->execute([':no' => $nombreOriginal]);
        return (int)$stmt->fetchColumn();
    }

    /**
 * Cuenta cuántos archivos tienen el mismo alias (excluyendo un ID opcional).
 */
    private function contarDuplicadosPorAlias(string $alias, ?int $excluirId = null): int
    {
        if ($excluirId !== null) {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM archivos WHERE nombre_personalizado = :alias AND id != :excluirId");
            $stmt->execute([':alias' => $alias, ':excluirId' => $excluirId]);
        } else {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM archivos WHERE nombre_personalizado = :alias");
            $stmt->execute([':alias' => $alias]);
        }
        return (int)$stmt->fetchColumn();
    }

    private function generarAliasUnico(string $base, string $extension, ?int $excluirId = null): string
    {
        // Limpiar base
        $base = trim($base);
        if ($base === '') {
            return '';
        }

        // Verificar si el alias ya existe
        $contador = $this->contarDuplicadosPorAlias($base . '.' . $extension, $excluirId);
        if ($contador === 0) {
            return $base . '.' . $extension;
        }

        // Si existe, buscar un número disponible
        $i = 1;
        do {
            $nuevoAlias = $base . ' (' . $i . ').' . $extension;
            $existe = $this->contarDuplicadosPorAlias($nuevoAlias, $excluirId) > 0;
            $i++;
        } while ($existe);

        return $nuevoAlias;
    }

    /**
     * Busca un archivo por token.
     */
    public function obtenerPorToken(string $token): ?Archivo {
        $stmt = $this->pdo->prepare("SELECT * FROM archivos WHERE token_acceso = :token LIMIT 1");
        $stmt->execute([':token' => $token]);
        $fila = $stmt->fetch();
        if (!$fila) return null;
        return new Archivo(
            (int)$fila['id'],
            $fila['nombre_original'],
            $fila['nombre_personalizado'],
            $fila['nombre_fisico'],
            (int)$fila['tamano'],
            $fila['extension'],
            $fila['fecha_subida'],
            $fila['subido_por'],
            $fila['token_acceso']
        );
    }
}
