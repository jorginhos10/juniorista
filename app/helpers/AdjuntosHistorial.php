<?php

require_once ROOT . '/app/helpers/Comprobantes.php';

/**
 * PDF adjuntos a los registros del historial clínico (resultados de laboratorio, radiografías, informes…).
 * Se guardan fuera de la aplicación, en la misma carpeta vecina «sistebendey» de los comprobantes:
 *     sistebendey/historial/<SUCURSAL>/mascota_<id>/<fecha_hora>_<aleatorio>.pdf
 * La carpeta no se sirve por la web: los PDF solo se entregan con sesión iniciada.
 */
class AdjuntosHistorial {

    public const MAX_BYTES     = 15 * 1024 * 1024;   // por archivo
    public const MAX_POR_ENVIO = 10;                 // archivos en un mismo guardado

    private static function base(): string {
        return Comprobantes::base() . '/historial';
    }

    /**
     * Guarda un PDF recibido como bytes. Devuelve la ruta relativa a la carpeta base de historial o un mensaje de error.
     * @return array{0: ?string, 1: string} [ruta, error]
     */
    public static function guardarBytes(string $contenido, string $sucursal, int $mascota_id): array {
        if ($contenido === '') return [null, 'El archivo está vacío.'];
        if (strlen($contenido) > self::MAX_BYTES) return [null, 'Supera el tamaño máximo de ' . (self::MAX_BYTES / 1048576) . ' MB.'];
        // Solo PDF reales: se mira el contenido, no la extensión ni el tipo que diga el navegador
        if (!str_starts_with($contenido, '%PDF-')) return [null, 'No es un PDF válido.'];

        $rel = Comprobantes::carpetaSucursal($sucursal) . '/mascota_' . $mascota_id;
        $abs = self::base() . '/' . $rel;
        if (!is_dir($abs) && !@mkdir($abs, 0775, true) && !is_dir($abs)) return [null, 'No se pudo crear la carpeta de adjuntos.'];
        self::proteger();

        $nombre = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.pdf';
        if (@file_put_contents($abs . '/' . $nombre, $contenido) === false) return [null, 'No se pudo guardar el archivo.'];
        return [$rel . '/' . $nombre, ''];
    }

    /**
     * Guarda los PDF subidos en $_FILES['adjuntos'] (campo múltiple).
     * @return array{0: array<int,array{nombre:string,archivo:string,tamano:int}>, 1: string[]} [guardados, errores]
     */
    public static function guardarSubidos(array $files, string $sucursal, int $mascota_id): array {
        $guardados = [];
        $errores   = [];
        $nombres   = (array)($files['name'] ?? []);
        if (count($nombres) > self::MAX_POR_ENVIO) {
            $errores[] = 'Máximo ' . self::MAX_POR_ENVIO . ' archivos por guardado; se tomaron los primeros.';
        }
        foreach (array_slice(array_keys($nombres), 0, self::MAX_POR_ENVIO) as $i) {
            $nombre = (string)$nombres[$i];
            $error  = (int)($files['error'][$i] ?? UPLOAD_ERR_NO_FILE);
            if ($error === UPLOAD_ERR_NO_FILE) continue;
            $nombre = mb_substr(preg_replace('/[\x00-\x1f\\\\\/]+/', '', $nombre), 0, 150);

            if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) { $errores[] = "«$nombre» supera el tamaño que permite el servidor."; continue; }
            if ($error !== UPLOAD_ERR_OK) { $errores[] = "«$nombre» no se pudo subir."; continue; }

            $tmp = (string)($files['tmp_name'][$i] ?? '');
            if (!is_uploaded_file($tmp)) { $errores[] = "«$nombre» no es una subida válida."; continue; }
            [$ruta, $msg] = self::guardarBytes((string)file_get_contents($tmp), $sucursal, $mascota_id);
            if ($ruta === null) { $errores[] = "«$nombre»: $msg"; continue; }
            $guardados[] = ['nombre' => $nombre !== '' ? $nombre : 'adjunto.pdf', 'archivo' => $ruta, 'tamano' => (int)($files['size'][$i] ?? 0)];
        }
        return [$guardados, $errores];
    }

    /** Ruta absoluta de un adjunto guardado, solo si está dentro de la carpeta de historial */
    public static function rutaAbsoluta(string $relativa): ?string {
        $base = realpath(self::base());
        $abs  = realpath(self::base() . '/' . $relativa);
        if (!$base || !$abs || !str_starts_with($abs, $base . DIRECTORY_SEPARATOR) || !is_file($abs)) return null;
        return $abs;
    }

    /** Envía el PDF al navegador (para verlo o para que la impresión lo dibuje) y termina */
    public static function enviar(string $relativa, string $nombre): void {
        $abs = self::rutaAbsoluta($relativa);
        if (!$abs) {
            http_response_code(404);
            exit;
        }
        $seguro = preg_replace('/[^A-Za-z0-9._ -]+/', '_', $nombre) ?: 'adjunto.pdf';
        header('Content-Type: application/pdf');
        header('Content-Length: ' . filesize($abs));
        header('Content-Disposition: inline; filename="' . $seguro . '"');
        header('Cache-Control: private, max-age=3600');
        header('X-Content-Type-Options: nosniff');
        readfile($abs);
        exit;
    }

    /** La carpeta no se lista ni se descarga directamente desde la web */
    private static function proteger(): void {
        foreach ([Comprobantes::base(), self::base()] as $dir) {
            $ht = $dir . '/.htaccess';
            if (is_dir($dir) && !is_file($ht)) {
                @file_put_contents($ht, "# Adjuntos del historial: solo se entregan a traves de la aplicacion\nRequire all denied\n");
            }
        }
    }
}
