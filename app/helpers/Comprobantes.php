<?php

/**
 * Fotos de comprobantes (transferencias de las ventas).
 * Se guardan fuera de la aplicación, en la carpeta vecina «sistebendey»:
 *     sistebendey/<AAAA-MM-DD>/<SUCURSAL>/venta_<id>_<hora>_<aleatorio>.jpg
 * La carpeta no se sirve directamente por la web: las fotos solo se entregan con sesión o token válidos.
 */
class Comprobantes {

    private const TIPOS = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    public  const MAX_BYTES = 6 * 1024 * 1024;   // por foto
    public  const MAX_POR_VENTA = 5;

    /** Carpeta base (vecina de la aplicación) */
    public static function base(): string {
        return dirname(ROOT) . DIRECTORY_SEPARATOR . 'sistebendey';
    }

    /** Nombre de carpeta seguro para la sucursal: «Veterinaria Juniorista» → VETERINARIA_JUNIORISTA */
    public static function carpetaSucursal(string $sucursal): string {
        $s = strtr(mb_strtoupper(trim($sucursal), 'UTF-8'), ['Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N']);
        $s = trim(preg_replace('/[^A-Z0-9]+/', '_', $s), '_');
        return $s !== '' ? $s : 'SUCURSAL';
    }

    /**
     * Guarda una foto recibida como data URL (data:image/jpeg;base64,...) o como bytes.
     * Devuelve la ruta relativa a la carpeta base (para guardarla en la base de datos) o null si no es una imagen válida.
     */
    public static function guardar(int $venta_id, string $sucursal, string $contenido, ?string $fecha = null): ?string {
        if (str_starts_with($contenido, 'data:')) {
            $coma = strpos($contenido, ',');
            if ($coma === false) return null;
            $contenido = base64_decode(substr($contenido, $coma + 1), true);
            if ($contenido === false) return null;
        }
        if ($contenido === '' || strlen($contenido) > self::MAX_BYTES) return null;

        // Solo imágenes reales (se mira el contenido, no la extensión ni lo que diga el navegador)
        $info = @getimagesizefromstring($contenido);
        if (!$info || !isset(self::TIPOS[$info[2]])) return null;

        $fecha   = $fecha ?: date('Y-m-d');
        $relDir  = $fecha . '/' . self::carpetaSucursal($sucursal);
        $absDir  = self::base() . '/' . $relDir;
        if (!is_dir($absDir) && !@mkdir($absDir, 0775, true) && !is_dir($absDir)) return null;
        self::protegerCarpeta();

        $nombre = sprintf('venta_%d_%s_%s.%s', $venta_id, date('His'), bin2hex(random_bytes(3)), self::TIPOS[$info[2]]);
        if (@file_put_contents($absDir . '/' . $nombre, $contenido) === false) return null;
        return $relDir . '/' . $nombre;
    }

    /** Ruta absoluta de una foto guardada, solo si está dentro de la carpeta base */
    public static function rutaAbsoluta(string $relativa): ?string {
        $base = realpath(self::base());
        $abs  = realpath(self::base() . '/' . $relativa);
        if (!$base || !$abs || !str_starts_with($abs, $base . DIRECTORY_SEPARATOR) || !is_file($abs)) return null;
        return $abs;
    }

    /** Envía la foto al navegador o a la app y termina */
    public static function enviar(string $relativa): void {
        $abs = self::rutaAbsoluta($relativa);
        if (!$abs) {
            http_response_code(404);
            exit;
        }
        $tipos = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
        header('Content-Type: ' . ($tipos[strtolower(pathinfo($abs, PATHINFO_EXTENSION))] ?? 'application/octet-stream'));
        header('Content-Length: ' . filesize($abs));
        header('Cache-Control: private, max-age=86400');
        header('X-Content-Type-Options: nosniff');
        readfile($abs);
        exit;
    }

    /** La carpeta de fotos no se lista ni se descarga directamente desde la web */
    private static function protegerCarpeta(): void {
        $ht = self::base() . '/.htaccess';
        if (!is_file($ht)) {
            @file_put_contents($ht, "# Fotos de comprobantes: solo se entregan a traves de la aplicacion\nRequire all denied\n");
        }
    }
}
