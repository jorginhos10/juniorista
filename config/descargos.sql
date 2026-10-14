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
