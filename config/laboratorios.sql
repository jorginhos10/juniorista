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
