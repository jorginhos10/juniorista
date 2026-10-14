-- Base de datos PetControl
CREATE DATABASE IF NOT EXISTS petcontrol
    CHARACTER SET utf8
    COLLATE utf8_general_ci;

USE petcontrol;

-- Tabla de usuarios del sistema
CREATE TABLE IF NOT EXISTS usuarios (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    nombre     VARCHAR(100) NOT NULL,
    email      VARCHAR(150) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    rol        ENUM('admin', 'veterinario', 'recepcion') NOT NULL DEFAULT 'recepcion',
    activo     TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Usuarios de prueba (contraseña: password)
INSERT INTO usuarios (nombre, email, password, rol) VALUES
('Administrador',  'admin@petcontrol.com',       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Dra. García',   'veterinario@petcontrol.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'veterinario'),
('Recepcionista',  'recepcion@petcontrol.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'recepcion');
