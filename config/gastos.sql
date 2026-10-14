-- Módulo de gastos
USE petcontrol;

CREATE TABLE IF NOT EXISTS gastos (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    veterinaria_id INT NOT NULL,
    fecha          DATE NOT NULL,
    categoria      ENUM('alquiler','servicios','sueldos','insumos','medicamentos','mantenimiento','impuestos','marketing','transporte','otros') NOT NULL DEFAULT 'otros',
    descripcion    VARCHAR(200) NOT NULL,
    monto          DECIMAL(10,2) NOT NULL,
    metodo_pago    ENUM('efectivo','transferencia','tarjeta','yape_plin','otro') NOT NULL DEFAULT 'efectivo',
    comprobante    VARCHAR(60) DEFAULT NULL,
    proveedor_id   INT DEFAULT NULL,
    notas          TEXT DEFAULT NULL,
    usuario_id     INT DEFAULT NULL,
    activo         TINYINT(1) NOT NULL DEFAULT 1,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_gastos_vet_fecha (veterinaria_id, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
