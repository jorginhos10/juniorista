<?php

/**
 * Subida segura de imágenes (logos de sucursal, fotos de producto).
 *  - Solo JPG, PNG o WebP de hasta 2 MB; el tipo se determina por el contenido real del archivo.
 *  - El nombre lo genera el servidor (nunca el del archivo subido).
 *  - Las carpetas de destino tienen un .htaccess que impide ejecutar código.
 */
class Imagenes {

    public const MAX_BYTES = 2 * 1024 * 1024;
    private const TIPOS    = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    private const CARPETAS = ['assets/img/logos', 'assets/img/productos'];

    /**
     * Procesa el archivo de un <input type="file">.
     * @param array|null  $archivo  Entrada de $_FILES (o null si el formulario no la envió)
     * @param string      $carpeta  Una de CARPETAS
     * @param string      $prefijo  Inicio del nombre, p. ej. "sucursal_4" o "producto_12"
     * @param string|null $actual   Ruta de la imagen actual (se borra si se reemplaza o se quita)
     * @param bool        $quitar   El usuario marcó «quitar la imagen actual»
     * @return array{0: bool, 1: ?string, 2: string}  [hay cambio, nueva ruta o null, mensaje de error]
     */
    public static function procesar(?array $archivo, string $carpeta, string $prefijo, ?string $actual, bool $quitar): array {
        if (!in_array($carpeta, self::CARPETAS, true)) {
            return [false, null, 'carpeta de destino no válida.'];
        }

        if (!$archivo || $archivo['error'] === UPLOAD_ERR_NO_FILE) {
            if ($quitar && $actual) {
                self::borrar($actual);
                return [true, null, ''];
            }
            return [false, null, ''];
        }
        if (in_array($archivo['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) || $archivo['size'] > self::MAX_BYTES) {
            return [false, null, 'la imagen supera los 2 MB.'];
        }
        if ($archivo['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($archivo['tmp_name'])) {
            return [false, null, 'no se pudo recibir el archivo.'];
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);
        if (!isset(self::TIPOS[$mime]) || @getimagesize($archivo['tmp_name']) === false) {
            return [false, null, 'debe ser una imagen JPG, PNG o WebP.'];
        }

        $dir = ROOT . '/' . $carpeta;
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            return [false, null, 'no se pudo crear la carpeta de imágenes.'];
        }
        // El sufijo aleatorio evita que el navegador siga mostrando la imagen anterior
        $ruta = $carpeta . '/' . preg_replace('/[^a-z0-9_]/', '', $prefijo) . '_' . bin2hex(random_bytes(4)) . '.' . self::TIPOS[$mime];
        if (!move_uploaded_file($archivo['tmp_name'], ROOT . '/' . $ruta)) {
            return [false, null, 'no se pudo guardar la imagen.'];
        }

        if ($actual) {
            self::borrar($actual);
        }
        return [true, $ruta, ''];
    }

    /** Borra una imagen subida, solo si está dentro de una de las carpetas permitidas y tiene un nombre generado por el sistema */
    public static function borrar(string $ruta): void {
        $carpetas = implode('|', array_map(fn($c) => preg_quote($c, '#'), self::CARPETAS));
        if (preg_match('#^(' . $carpetas . ')/[a-z0-9_]+\.(jpg|png|webp)$#', $ruta)) {
            @unlink(ROOT . '/' . $ruta);
        }
    }
}
