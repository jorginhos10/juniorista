-- Módulo de laboratorios (registro diario, recaudo directo; misma estructura que peluquerías)
USE petcontrol;

CREATE TABLE IF NOT EXISTS laboratorios (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    veterinaria_id INT NOT NULL,
    fecha          DATE NOT NULL,
    nombre         VARCHAR(100) NOT NULL,
    especie        ENUM('perro', 'gato') NOT NULL DEFAULT 'perro',
    descripcion    VARCHAR(255) NOT NULL,
    valor          DECIMAL(10,2) NOT NULL DEFAULT 0,
    usuario_id     INT DEFAULT NULL,
    activo         TINYINT(1) NOT NULL DEFAULT 1,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    cliente_id     INT DEFAULT NULL,
    mascota_id     INT DEFAULT NULL,
    KEY idx_laboratorios_vet_fecha (veterinaria_id, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ── Abonos, costo y catálogo de exámenes ─────────────────────────────────────
-- LaboratorioModel crea esto solo la primera vez (SHOW TABLES / SHOW COLUMNS); se deja aquí como referencia.
-- Los exámenes que ya existían se cobraron completos: la migración los pasa a un abono por todo su valor.

ALTER TABLE laboratorios
    ADD COLUMN estado             ENUM('activo','cancelado') NOT NULL DEFAULT 'activo',
    ADD COLUMN motivo_cancelacion VARCHAR(200) DEFAULT NULL,
    ADD COLUMN fecha_cancelacion  DATE DEFAULT NULL,
    ADD COLUMN costo              DECIMAL(10,2) NOT NULL DEFAULT 0;   -- suma de los exámenes del catálogo = valor mínimo a cobrar

CREATE TABLE IF NOT EXISTS laboratorio_abonos (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    tratamiento_id INT NOT NULL,            -- id del registro en laboratorios (mismo nombre de columna que tratamiento_abonos)
    veterinaria_id INT NOT NULL,
    fecha          DATE NOT NULL,
    monto          DECIMAL(10,2) NOT NULL,
    nota           VARCHAR(150) DEFAULT NULL,
    usuario_id     INT DEFAULT NULL,
    activo         TINYINT(1) NOT NULL DEFAULT 1,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_lab_abonos_registro (tratamiento_id),
    KEY idx_lab_abonos_vet_fecha (veterinaria_id, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS laboratorio_examenes (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    veterinaria_id INT NOT NULL,
    codigo         VARCHAR(30) NOT NULL,    -- sigla: HH, PHR, CDV…
    nombre         VARCHAR(100) NOT NULL,
    costo          DECIMAL(10,2) NOT NULL DEFAULT 0,
    activo         TINYINT(1) NOT NULL DEFAULT 1,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_lab_examenes_vet (veterinaria_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT INTO laboratorio_abonos (tratamiento_id, veterinaria_id, fecha, monto, nota, usuario_id, created_at)
SELECT id, veterinaria_id, fecha, valor, 'Pago completo', usuario_id, created_at
  FROM laboratorios WHERE activo = 1 AND valor > 0;
