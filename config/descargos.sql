-- Módulo de descargos de inventario
USE petcontrol;

CREATE TABLE IF NOT EXISTS descargos (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    veterinaria_id  INT NOT NULL,
    producto_id     INT NOT NULL,
    cantidad        INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL DEFAULT 0,
    motivo          ENUM('vencido', 'perdido', 'otros') NOT NULL,
    motivo_detalle  VARCHAR(255) DEFAULT NULL,
    usuario_id      INT DEFAULT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_descargos_vet_fecha (veterinaria_id, created_at),
    KEY idx_descargos_producto (producto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- 2026-10-19: el descargo lleva su propia fecha (puede ser anterior al día en que se registra).
ALTER TABLE descargos ADD COLUMN fecha DATE NULL AFTER producto_id;
UPDATE descargos SET fecha = DATE(created_at) WHERE fecha IS NULL;
ALTER TABLE descargos MODIFY fecha DATE NOT NULL, ADD KEY idx_descargos_vet_fecha_d (veterinaria_id, fecha);

-- 2026-10-21: varios productos por descargo; comparten «grupo» (número del acta). DescargoModel lo aplica solo.
ALTER TABLE descargos ADD COLUMN grupo INT DEFAULT NULL AFTER id, ADD KEY idx_descargos_grupo (grupo);
