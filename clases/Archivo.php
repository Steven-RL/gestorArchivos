<?php
/**
 * Clase Archivo
 * -------------
 * Representa un archivo guardado en el sistema. Sus datos vienen de la
 * tabla `archivos` de MySQL (no del filesystem directamente).
 * Propiedades:
 *  - id:               clave primaria en la BD
 *  - nombreOriginal:   nombre tal como el usuario lo subió
 *  - nombrePersonalizado: alias opcional editado por el usuario
 *  - nombreFisico:     nombre único con el que se guarda en /uploads
 *  - tamano:           tamaño en bytes
 *  - extension:        extensión en minúsculas
 *  - fechaSubida:      DATETIME de cuando se subió
 *  - subidoPor:        usuario que subió el archivo
 */
class Archivo {
    public function __construct(
        public int $id,
        public string $nombreOriginal,
        public ?string $nombrePersonalizado,
        public string $nombreFisico,
        public int $tamano,
        public string $extension,
        public string $fechaSubida,
        public string $subidoPor,
        public string $tokenAcceso
    ) {}

    // Devuelve el nombre a mostrar: usa el personalizado si existe, si no el original
    public function getNombreMostrar(): string {
        return $this->nombrePersonalizado ?: $this->nombreOriginal;
    }

    // Tamaño formateado en KB / MB
    public function getTamanoFormateado(): string {
        if ($this->tamano >= 1048576) return round($this->tamano / 1048576, 2) . ' MB';
        if ($this->tamano >= 1024)    return round($this->tamano / 1024, 2) . ' KB';
        return $this->tamano . ' B';
    }

    // Ícono visual según la extensión
    public function getIcono(): string {
        return match (strtolower($this->extension)) {
            'jpg','jpeg','png','gif','webp','svg' => '🖼️',
            'pdf'                                 => '📕',
            'doc','docx'                          => '📘',
            'xls','xlsx','csv'                    => '📊',
            'ppt','pptx'                          => '📙',
            'zip','rar','7z'                      => '🗜️',
            'mp3','wav'                           => '🎵',
            'mp4','avi','mov'                     => '🎬',
            'txt'                                 => '📄',
            default                               => '📁',
        };
    }

    // Devuelve la fecha formateada (ej. 15/01/2025)
    public function getFechaFormateada(): string
    {
        $timestamp = strtotime($this->fechaSubida);
        return date('d/m/Y', $timestamp);
    }

    // Devuelve la hora formateada (ej. 14:30)
    public function getHoraFormateada(): string
    {
        $timestamp = strtotime($this->fechaSubida);
        return date('H:i', $timestamp);
    }

    // Devuelve fecha y hora en un formato más legible (opcional)
    public function getFechaHoraFormateada(): string
    {
        $timestamp = strtotime($this->fechaSubida);
        return date('d/m/Y H:i', $timestamp);
    }
}
