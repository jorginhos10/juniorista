-- Los usuarios ingresan con nombre de usuario y contraseña (ya no con correo).
-- La columna email se convierte en «usuario»; a los existentes se les deja lo que iba antes de la @.
ALTER TABLE usuarios CHANGE email usuario VARCHAR(60) NOT NULL;
UPDATE usuarios SET usuario = LOWER(SUBSTRING_INDEX(usuario, '@', 1)) WHERE usuario LIKE '%@%';
ALTER TABLE usuarios DROP INDEX email, ADD UNIQUE KEY usuario (usuario);
