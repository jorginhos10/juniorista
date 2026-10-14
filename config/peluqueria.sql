-- Módulo de peluquería (registro diario)
USE petcontrol;

CREATE TABLE IF NOT EXISTS peluquerias (
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
    KEY idx_peluquerias_vet_fecha (veterinaria_id, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Reparto de peluquería: % que le corresponde a la peluquería (el resto es del negocio).
-- Se configura por sucursal y se guarda en cada servicio al registrarlo (los cambios no alteran lo ya registrado).
ALTER TABLE veterinarias ADD COLUMN IF NOT EXISTS pct_peluqueria DECIMAL(5,2) NOT NULL DEFAULT 50.00;
ALTER TABLE peluquerias  ADD COLUMN IF NOT EXISTS pct_peluqueria DECIMAL(5,2) DEFAULT NULL AFTER valor;
