-- Historial médico (historia clínica por mascota, formato SOAP)
USE petcontrol;

CREATE TABLE IF NOT EXISTS historial_medico (
    id                     INT AUTO_INCREMENT PRIMARY KEY,
    mascota_id             INT NOT NULL,
    veterinaria_id         INT DEFAULT NULL,
    veterinario_id         INT DEFAULT NULL,
    fecha                  DATE NOT NULL,
    tipo                   ENUM('consulta','control','vacunacion','desparasitacion','cirugia','laboratorio','urgencia','otro') NOT NULL DEFAULT 'consulta',
    motivo                 VARCHAR(200) NOT NULL,
    -- S: subjetivo
    anamnesis              TEXT DEFAULT NULL,
    -- O: objetivo (constantes y examen físico)
    peso                   DECIMAL(6,2) DEFAULT NULL,
    temperatura            DECIMAL(4,1) DEFAULT NULL,
    frecuencia_cardiaca    SMALLINT DEFAULT NULL,
    frecuencia_respiratoria SMALLINT DEFAULT NULL,
    mucosas                VARCHAR(30) DEFAULT NULL,
    tllc                   VARCHAR(10) DEFAULT NULL,
    hidratacion            VARCHAR(20) DEFAULT NULL,
    condicion_corporal     TINYINT DEFAULT NULL,
    examen_fisico          TEXT DEFAULT NULL,
    -- A: análisis
    diagnostico            TEXT DEFAULT NULL,
    -- P: plan
    tratamiento            TEXT DEFAULT NULL,
    producto_aplicado      VARCHAR(150) DEFAULT NULL,
    lote                   VARCHAR(60) DEFAULT NULL,
    proximo_control        DATE DEFAULT NULL,
    observaciones          TEXT DEFAULT NULL,
    usuario_id             INT DEFAULT NULL,
    activo                 TINYINT(1) NOT NULL DEFAULT 1,
    created_at             TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at             TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_historial_mascota (mascota_id, fecha),
    KEY idx_historial_proximo (proximo_control)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
