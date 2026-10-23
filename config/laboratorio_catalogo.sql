-- Catálogo de laboratorio: exámenes (lista de precios oficial) y combos (perfiles).
-- El valor de cada examen es su COSTO (lo que cobra el laboratorio); el doctor no puede cobrar menos que la suma.
-- Se puede ejecutar más de una vez: no repite un examen o combo que ya exista en la sucursal (misma sigla).
--
-- ANTES DE EJECUTAR: elige la base de datos y revisa @vet (id de la sucursal que recibe el catálogo).

SET NAMES utf8;

CREATE TABLE IF NOT EXISTS laboratorio_examenes (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    veterinaria_id INT NOT NULL,
    codigo         VARCHAR(30) NOT NULL,
    nombre         VARCHAR(100) NOT NULL,
    costo          DECIMAL(10,2) NOT NULL DEFAULT 0,
    activo         TINYINT(1) NOT NULL DEFAULT 1,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    es_combo       TINYINT(1) NOT NULL DEFAULT 0,
    incluye        VARCHAR(255) DEFAULT NULL,
    incluye_extra  VARCHAR(255) DEFAULT NULL,
    tipo_muestra   VARCHAR(100) DEFAULT NULL,
    tiempo_entrega VARCHAR(50) NOT NULL DEFAULT 'Mismo día',
    KEY idx_lab_examenes_vet (veterinaria_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Sucursal: por nombre; si no la encuentra, escribe el id a mano (SET @vet = 1;)
SET @vet = (SELECT id FROM veterinarias WHERE activo = 1 AND nombre LIKE '%JUNIORISTA%' ORDER BY id LIMIT 1);

-- ── Exámenes sueltos ─────────────────────────────────────────────────────────
-- (sigla, nombre, tipo de muestra, costo)
INSERT INTO laboratorio_examenes (veterinaria_id, codigo, nombre, tipo_muestra, costo)
SELECT @vet, t.codigo, t.nombre, t.muestra, t.costo
  FROM (
        SELECT 'HH'         AS codigo, 'Hemograma / Hemoparásito'          AS nombre, 'Tubo tapa lila (EDTA)'   AS muestra, 13000 AS costo
  UNION ALL SELECT 'COPRO',    'Coprológico',                                  'Heces',                              9000
  UNION ALL SELECT 'ORINA',    'Parcial de orina',                             'Orina',                              9000
  UNION ALL SELECT 'KOH',      'Raspado de piel (KOH)',                        'Pelos, costras y piel',              9000
  UNION ALL SELECT 'PT',       'Tiempo de protrombina (PT)',                   'Tubo tapa azul (citrato)',          11000
  UNION ALL SELECT 'PTT',      'Tiempo de tromboplastina parcial activado (PTT)', 'Tubo tapa azul (citrato)',        11000
  UNION ALL SELECT 'CITOOIDO', 'Citología de oído',                            'Hisopado de oído',                   9000
  UNION ALL SELECT 'CITOVAG',  'Citología vaginal',                            'Hisopado vaginal, recipiente estéril', 11000
  UNION ALL SELECT 'FIVFELV',  'Perfil de FIV/FELV felino',                    'Tubo tapa lila (EDTA)',             45000
  UNION ALL SELECT 'PARVO',    'Parvovirosis',                                 'Heces',                             40000
  UNION ALL SELECT 'DISTEMPER','Distemper canino',                             'Tubo tapa lila (EDTA)',             40000
  UNION ALL SELECT 'HPR',      'HPR básico (urea, BUN, crea, AST, ALT)',       'Tubo seco',                         35000
       ) t
 WHERE @vet IS NOT NULL
   AND NOT EXISTS (SELECT 1 FROM laboratorio_examenes x
                    WHERE x.veterinaria_id = @vet AND x.codigo = t.codigo AND x.activo = 1);

-- ── Combos (perfiles) ────────────────────────────────────────────────────────
-- (sigla, nombre, siglas de los exámenes del catálogo que incluye, otros exámenes escritos, muestra, costo del combo)
-- El costo del combo es propio: puede ser menor que la suma de sus exámenes.
INSERT INTO laboratorio_examenes (veterinaria_id, codigo, nombre, es_combo, incluye, incluye_extra, tipo_muestra, costo)
SELECT @vet, c.codigo, c.nombre, 1,
       (SELECT GROUP_CONCAT(e.id ORDER BY e.id) FROM laboratorio_examenes e
         WHERE e.veterinaria_id = @vet AND e.activo = 1 AND e.es_combo = 0 AND FIND_IN_SET(e.codigo, c.siglas)),
       NULLIF(c.extra, ''), c.muestra, c.costo
  FROM (
        SELECT 'PERFIL-HEPATORENAL' AS codigo, 'Perfil de hemograma y hepato-renal' AS nombre, 'HH' AS siglas,
               'Urea, BUN, Creatinina, ALT (GPT), AST' AS extra, 'Tubo tapa lila (EDTA) y tubo seco' AS muestra, 49000 AS costo
  UNION ALL SELECT 'PERFIL-2', 'Perfil 2 (hemograma + coprológico)',            'HH,COPRO',     '', 'Tubo tapa lila (EDTA) y heces', 23000
  UNION ALL SELECT 'PERFIL-3', 'Perfil 3 (hemograma + parcial de orina)',       'HH,ORINA',     '', 'Tubo tapa lila (EDTA) y orina', 23000
  UNION ALL SELECT 'PERFIL-4', 'Perfil 4 (hemograma + raspado de piel)',        'HH,KOH',       '', 'Tubo tapa lila (EDTA) y piel',  23000
  UNION ALL SELECT 'PERFIL-5', 'Perfil 5 (hemograma + parvovirosis)',           'HH,PARVO',     '', 'Tubo tapa lila (EDTA) y heces', 45000
  UNION ALL SELECT 'PERFIL-6', 'Perfil 6 (hemograma + distemper canino)',       'HH,DISTEMPER', '', 'Tubo tapa lila (EDTA)',         50000
  UNION ALL SELECT 'PERFIL-7', 'Perfil 7 (hemograma + perfil FIV/FELV felino)', 'HH,FIVFELV',   '', 'Tubo tapa lila (EDTA)',         49000
       ) c
 WHERE @vet IS NOT NULL
   AND NOT EXISTS (SELECT 1 FROM laboratorio_examenes x
                    WHERE x.veterinaria_id = @vet AND x.codigo = c.codigo AND x.activo = 1);

-- Revisión
SELECT codigo, nombre, es_combo, tipo_muestra, tiempo_entrega, costo, incluye, incluye_extra
  FROM laboratorio_examenes WHERE veterinaria_id = @vet AND activo = 1 ORDER BY es_combo, codigo;
