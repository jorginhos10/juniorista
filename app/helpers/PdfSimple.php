<?php

/**
 * Generador de PDF mínimo, sin librerías: páginas A4 con texto (Helvetica normal y negrita),
 * líneas y rectángulos de color. Suficiente para comprobantes y listados en tabla.
 * Coordenadas en puntos, con el origen arriba a la izquierda.
 */
class PdfSimple {

    public const ANCHO = 595.28;   // A4 vertical
    public const ALTO  = 841.89;

    /** Anchos de Helvetica (milésimas de em) para los caracteres 32–126 */
    private const ANCHOS = [
        278, 278, 355, 556, 556, 889, 667, 191, 333, 333, 389, 584, 278, 333, 278, 278,
        556, 556, 556, 556, 556, 556, 556, 556, 556, 556, 278, 278, 584, 584, 584, 556,
        1015, 667, 667, 722, 722, 667, 611, 778, 722, 278, 500, 667, 556, 833, 722, 778,
        667, 778, 722, 667, 611, 722, 667, 944, 667, 667, 611, 278, 278, 278, 469, 556,
        333, 556, 556, 500, 556, 556, 278, 556, 556, 222, 222, 500, 222, 833, 556, 556,
        556, 556, 333, 500, 278, 556, 500, 722, 500, 500, 500, 334, 260, 334, 584,
    ];

    private array  $paginas = [];
    private string $actual  = '';

    public function __construct() {
        $this->nuevaPagina();
    }

    public function nuevaPagina(): void {
        if ($this->actual !== '') $this->paginas[] = $this->actual;
        $this->actual = "0.2 w\n";
    }

    public function numeroPagina(): int {
        return count($this->paginas) + 1;
    }

    /** Ancho del texto en puntos (la negrita es algo más ancha) */
    public function ancho(string $texto, float $tam, bool $negrita = false): float {
        $w = 0;
        foreach (str_split(self::aLatin1($texto)) as $c) {
            $o  = ord($c);
            $w += ($o >= 32 && $o <= 126) ? self::ANCHOS[$o - 32] : 600;
        }
        return $w * $tam / 1000 * ($negrita ? 1.07 : 1);
    }

    /** Recorta el texto con "…" para que quepa en el ancho dado */
    public function ajustar(string $texto, float $anchoMax, float $tam, bool $negrita = false): string {
        if ($this->ancho($texto, $tam, $negrita) <= $anchoMax) return $texto;
        while ($texto !== '' && $this->ancho($texto . '...', $tam, $negrita) > $anchoMax) {
            $texto = mb_substr($texto, 0, -1, 'UTF-8');
        }
        return rtrim($texto) . '...';
    }

    /** $alinear: 'L' desde x, 'R' termina en x, 'C' centrado en x. $gris: 0 negro … 1 blanco */
    public function texto(float $x, float $y, string $texto, float $tam = 9, bool $negrita = false, string $alinear = 'L', float $gris = 0): void {
        if ($texto === '') return;
        if ($alinear === 'R') $x -= $this->ancho($texto, $tam, $negrita);
        if ($alinear === 'C') $x -= $this->ancho($texto, $tam, $negrita) / 2;
        $t = strtr(self::aLatin1($texto), ['\\' => '\\\\', '(' => '\\(', ')' => '\\)']);
        $this->actual .= sprintf("BT /F%d %.2F Tf %.3F g %.2F %.2F Td (%s) Tj ET\n",
            $negrita ? 2 : 1, $tam, $gris, $x, self::ALTO - $y, $t);
    }

    public function linea(float $x1, float $y1, float $x2, float $y2, float $gris = 0.75): void {
        $this->actual .= sprintf("%.3F G %.2F %.2F m %.2F %.2F l S\n", $gris, $x1, self::ALTO - $y1, $x2, self::ALTO - $y2);
    }

    public function rectangulo(float $x, float $y, float $w, float $h, float $gris = 0.93): void {
        $this->actual .= sprintf("%.3F g %.2F %.2F %.2F %.2F re f\n", $gris, $x, self::ALTO - $y - $h, $w, $h);
    }

    /** Envía el PDF al navegador como descarga */
    public function descargar(string $nombreArchivo): void {
        $pdf = $this->generar();
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '.pdf"');
        header('Content-Length: ' . strlen($pdf));
        header('Cache-Control: max-age=0');
        echo $pdf;
    }

    public function generar(): string {
        $paginas = array_merge($this->paginas, [$this->actual]);
        $n = count($paginas);

        // 1 catálogo, 2 árbol de páginas, 3 y 4 fuentes; después, por página: objeto página + contenido
        $obj = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
        ];
        $kids = [];
        foreach ($paginas as $i => $contenido) {
            $pag = 5 + $i * 2;
            $kids[] = "$pag 0 R";
            $obj[$pag] = sprintf('<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents %d 0 R >>',
                self::ALTO > 0 ? self::ANCHO : 0, self::ALTO, $pag + 1);
            $obj[$pag + 1] = '<< /Length ' . strlen($contenido) . " >>\nstream\n" . $contenido . "endstream";
        }
        $obj[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . $n . ' >>';
        ksort($obj);

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $pos = [];
        foreach ($obj as $id => $cuerpo) {
            $pos[$id] = strlen($pdf);
            $pdf .= "$id 0 obj\n$cuerpo\nendobj\n";
        }
        $xref = strlen($pdf);
        $total = count($obj) + 1;
        $pdf .= "xref\n0 $total\n0000000000 65535 f \n";
        foreach ($obj as $id => $_) {
            $pdf .= sprintf("%010d 00000 n \n", $pos[$id]);
        }
        return $pdf . "trailer\n<< /Size $total /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";
    }

    /** UTF-8 → Windows-1252 (lo que entienden las fuentes base del PDF); lo que no existe se sustituye */
    private static function aLatin1(string $texto): string {
        $r = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $texto);
        return $r === false ? preg_replace('/[^\x20-\x7E]/', '?', $texto) : $r;
    }
}
