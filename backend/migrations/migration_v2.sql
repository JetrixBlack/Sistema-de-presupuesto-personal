-- ============================================================
-- SCRIPT COMPLETO: Crear presupuesto_bd, migrar y limpiar
-- ============================================================

-- 1. Crear la nueva base de datos limpia
CREATE DATABASE IF NOT EXISTS presupuesto_bd
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

-- 2. Usar presupuesto_dig como fuente para copiar tablas
-- (Se hace por mysqldump en el script PowerShell)

USE presupuesto_bd;

-- ============================================================
-- MIGRACIÓN V2 (aplicada sobre presupuesto_bd ya copiada)
-- ============================================================

-- Agregar columnas a users (IF NOT EXISTS por seguridad)
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS full_name VARCHAR(150) NOT NULL DEFAULT '' AFTER username,
  ADD COLUMN IF NOT EXISTS username_changed_at DATETIME NULL DEFAULT NULL AFTER full_name,
  ADD COLUMN IF NOT EXISTS profile_changed_at  DATETIME NULL DEFAULT NULL AFTER username_changed_at;

-- Tabla de tickets de soporte
CREATE TABLE IF NOT EXISTS support_tickets (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  user_id      INT NOT NULL,
  type         VARCHAR(60) NOT NULL DEFAULT 'general',
  description  TEXT NOT NULL,
  image_path   VARCHAR(255) NULL DEFAULT NULL,
  status       ENUM('pendiente','en_proceso','resuelto') NOT NULL DEFAULT 'pendiente',
  admin_note   TEXT NULL DEFAULT NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de log de actividad
CREATE TABLE IF NOT EXISTS activity_log (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT NOT NULL,
  full_name  VARCHAR(150) NOT NULL DEFAULT '',
  role       VARCHAR(30)  NOT NULL DEFAULT 'user',
  action     VARCHAR(255) NOT NULL,
  entity     VARCHAR(60)  NULL DEFAULT NULL,
  entity_id  INT          NULL DEFAULT NULL,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user_id (user_id),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de caché BCV
CREATE TABLE IF NOT EXISTS bcv_rate_cache (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  currency   VARCHAR(10)    NOT NULL DEFAULT 'USD',
  rate       DECIMAL(10,4)  NOT NULL DEFAULT 0,
  fetched_at DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Eliminar tabla obsoleta de chat (si se copió)
DROP TABLE IF EXISTS support_messages;

-- Poblar full_name con username donde esté vacío
UPDATE users SET full_name = username WHERE full_name = '' OR full_name IS NULL;
