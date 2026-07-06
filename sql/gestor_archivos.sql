-- =====================================================================
-- INSTALACIÓN DE LA BASE DE DATOS DEL GESTOR DE ARCHIVOS
-- =====================================================================

CREATE DATABASE IF NOT EXISTS gestor_archivos
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE gestor_archivos;

-- ---------------------------------------------------------------------
-- Tabla: archivos
-- Guarda los metadatos. El binario sigue viviendo en /uploads.
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS archivos;
CREATE TABLE archivos (
  id                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre_original      VARCHAR(100)  NOT NULL,
  nombre_personalizado VARCHAR(100)  NULL,
  nombre_fisico        VARCHAR(100)  NOT NULL,
  tamano               BIGINT UNSIGNED NOT NULL,
  extension            VARCHAR(20)   NOT NULL,
  mime                 VARCHAR(120)  NOT NULL,
  subido_por           VARCHAR(60)   NOT NULL,
  fecha_subida         TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
  token_acceso         VARCHAR(64)   NOT NULL UNIQUE, 
  PRIMARY KEY (id),
  UNIQUE KEY uniq_nombre_fisico (nombre_fisico),
  UNIQUE KEY uniq_token_acceso (token_acceso),  -- Índice para búsquedas rápidas por token
  KEY idx_busqueda (nombre_original, nombre_personalizado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;