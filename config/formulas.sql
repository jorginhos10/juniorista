-- 2026-10-21: fórmulas médicas veterinarias (recetas). El modelo FormulaModel las crea solo si no existen.
CREATE TABLE IF NOT EXISTS formulas_medicas (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    veterinaria_id      INT NOT NULL,
    mascota_id          INT NOT NULL,
    veterinario_id      INT DEFAULT NULL,
    fecha               DATE NOT NULL,
    peso                DECIMAL(6,2) DEFAULT NULL,
    diagnostico         TEXT DEFAULT NULL,
    indicaciones        TEXT DEFAULT NULL,
    proximo_control     DATE DEFAULT NULL,
    tarjeta_profesional VARCHAR(40) DEFAULT NULL,
    usuario_id          INT DEFAULT NULL,
    activo              TINYINT(1) NOT NULL DEFAULT 1,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_formulas_vet_fecha (veterinaria_id, fecha),
    KEY idx_formulas_mascota (mascota_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS formula_items (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    formula_id   INT NOT NULL,
    orden        SMALLINT NOT NULL DEFAULT 0,
    producto_id  INT DEFAULT NULL,
    medicamento  VARCHAR(200) NOT NULL,
    presentacion VARCHAR(120) DEFAULT NULL,
    cantidad     VARCHAR(60)  DEFAULT NULL,
    dosis        VARCHAR(120) DEFAULT NULL,
    via          VARCHAR(30)  DEFAULT NULL,
    frecuencia   VARCHAR(80)  DEFAULT NULL,
    duracion     VARCHAR(80)  DEFAULT NULL,
    indicaciones VARCHAR(255) DEFAULT NULL,
    KEY idx_formula_items_formula (formula_id),
    CONSTRAINT fk_formula_items_formula FOREIGN KEY (formula_id) REFERENCES formulas_medicas (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- 2026-10-21: recetar a pacientes que no están registrados como mascota (FormulaModel lo aplica solo).
ALTER TABLE formulas_medicas
    MODIFY mascota_id INT DEFAULT NULL,
    ADD COLUMN paciente_nombre      VARCHAR(100) DEFAULT NULL AFTER mascota_id,
    ADD COLUMN paciente_especie     VARCHAR(50)  DEFAULT NULL AFTER paciente_nombre,
    ADD COLUMN paciente_raza        VARCHAR(100) DEFAULT NULL AFTER paciente_especie,
    ADD COLUMN paciente_sexo        VARCHAR(10)  DEFAULT NULL AFTER paciente_raza,
    ADD COLUMN paciente_edad        VARCHAR(40)  DEFAULT NULL AFTER paciente_sexo,
    ADD COLUMN propietario_nombre   VARCHAR(150) DEFAULT NULL AFTER paciente_edad,
    ADD COLUMN propietario_telefono VARCHAR(40)  DEFAULT NULL AFTER propietario_nombre;
