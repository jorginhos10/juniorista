-- Módulo de tratamientos (registro diario)
USE petcontrol;

CREATE TABLE IF NOT EXISTS tratamientos (
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
    KEY idx_tratamientos_vet_fecha (veterinaria_id, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Abonos: un tratamiento se paga en uno o varios abonos; los cierres suman lo abonado, no el valor
CREATE TABLE IF NOT EXISTS tratamiento_abonos (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    tratamiento_id INT NOT NULL,
    veterinaria_id INT NOT NULL,
    fecha          DATE NOT NULL,
    monto          DECIMAL(10,2) NOT NULL,
    nota           VARCHAR(150) DEFAULT NULL,
    usuario_id     INT DEFAULT NULL,
    activo         TINYINT(1) NOT NULL DEFAULT 1,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_abonos_tratamiento (tratamiento_id),
    KEY idx_abonos_vet_fecha (veterinaria_id, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Tratamientos anteriores a los abonos se consideran pagados el mismo día
INSERT INTO tratamiento_abonos (tratamiento_id, veterinaria_id, fecha, monto, nota, usuario_id)
SELECT t.id, t.veterinaria_id, t.fecha, t.valor, 'Pago registrado antes de los abonos', t.usuario_id
  FROM tratamientos t
 WHERE t.valor > 0
   AND NOT EXISTS (SELECT 1 FROM tratamiento_abonos a WHERE a.tratamiento_id = t.id);

-- Estado del tratamiento: un tratamiento cancelado no recibe más abonos (lo ya abonado sigue contando como cobrado)
ALTER TABLE tratamientos
    ADD COLUMN IF NOT EXISTS estado ENUM('activo', 'cancelado') NOT NULL DEFAULT 'activo' AFTER valor,
    ADD COLUMN IF NOT EXISTS motivo_cancelacion VARCHAR(200) DEFAULT NULL AFTER estado,
    ADD COLUMN IF NOT EXISTS fecha_cancelacion DATE DEFAULT NULL AFTER motivo_cancelacion;
