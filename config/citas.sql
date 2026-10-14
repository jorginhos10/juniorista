-- Módulo de citas: mascotas y citas
USE petcontrol;

CREATE TABLE IF NOT EXISTS mascotas (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id       INT NOT NULL,
    nombre           VARCHAR(100) NOT NULL,
    especie          VARCHAR(50)  NOT NULL DEFAULT 'Perro',
    raza             VARCHAR(100) DEFAULT NULL,
    sexo             ENUM('macho', 'hembra') DEFAULT NULL,
    fecha_nacimiento DATE DEFAULT NULL,
    notas            TEXT DEFAULT NULL,
    activo           TINYINT(1) NOT NULL DEFAULT 1,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_mascotas_cliente (cliente_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS citas (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    veterinaria_id INT NOT NULL,
    cliente_id     INT NOT NULL,
    mascota_id     INT NOT NULL,
    veterinario_id INT DEFAULT NULL,
    fecha          DATE NOT NULL,
    hora           TIME NOT NULL,
    duracion       SMALLINT NOT NULL DEFAULT 30,
    tipo           ENUM('consulta','vacunacion','desparasitacion','control','cirugia','estetica','urgencia','otro') NOT NULL DEFAULT 'consulta',
    motivo         VARCHAR(200) DEFAULT NULL,
    estado         ENUM('pendiente','confirmada','en_curso','atendida','cancelada','no_asistio') NOT NULL DEFAULT 'pendiente',
    notas          TEXT DEFAULT NULL,
    usuario_id     INT DEFAULT NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_citas_vet_fecha (veterinaria_id, fecha),
    KEY idx_citas_mascota (mascota_id),
    KEY idx_citas_veterinario (veterinario_id, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Apariencia: tema visual por usuario
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS tema VARCHAR(30) NOT NULL DEFAULT 'original' AFTER sucursal_id;

-- Mascotas: el dueño (cliente) es opcional
ALTER TABLE mascotas MODIFY cliente_id INT NULL DEFAULT NULL;

-- Usuarios: eliminación lógica (se conserva el nombre en el historial y se libera el correo)
ALTER TABLE usuarios
    ADD COLUMN IF NOT EXISTS eliminado TINYINT(1) NOT NULL DEFAULT 0 AFTER activo,
    ADD COLUMN IF NOT EXISTS eliminado_at TIMESTAMP NULL DEFAULT NULL AFTER eliminado;

-- Permisos por módulo: lista JSON de módulos habilitados (NULL = todos). El admin siempre tiene acceso total.
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS permisos TEXT DEFAULT NULL AFTER tema;

-- Sucursales asignadas por usuario: lista JSON de ids (NULL = todas). sucursal_id se mantiene con la única asignada, si es una sola.
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS sucursales TEXT DEFAULT NULL AFTER sucursal_id;
UPDATE usuarios SET sucursales = CONCAT('[', sucursal_id, ']') WHERE sucursal_id IS NOT NULL AND sucursales IS NULL;

-- Foto del producto (ruta relativa en assets/img/productos)
ALTER TABLE productos ADD COLUMN IF NOT EXISTS foto VARCHAR(200) DEFAULT NULL AFTER descripcion;

-- Categorías de productos (se administran en Almacén → Categorías; productos.categoria guarda el nombre)
CREATE TABLE IF NOT EXISTS categorias (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    nombre     VARCHAR(80) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_categorias_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
INSERT IGNORE INTO categorias (nombre) SELECT DISTINCT TRIM(categoria) FROM productos WHERE categoria IS NOT NULL AND TRIM(categoria) <> '' ORDER BY 1;
