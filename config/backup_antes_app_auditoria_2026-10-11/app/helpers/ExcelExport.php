<?php
// app/helpers/ExcelExport.php
// Genera y descarga un .xlsx sencillo (una hoja, encabezado en negrita) sin librerías externas.

class ExcelExport {

    /**
     * Envía al navegador un Excel con las filas dadas.
     * Si el servidor no tiene ZipArchive, cae a CSV (que Excel también abre).
     *
     * @param string $nombreArchivo Nombre sin extensión
     * @param string $hoja          Nombre de la hoja
     * @param array  $encabezados   Títulos de columna
     * @param array  $filas         Filas de datos (los enteros se guardan como número, el resto como texto)
     * @param array  $anchos        Ancho de cada columna (en caracteres)
     */
    public static function descargar($nombreArchivo, $hoja, array $encabezados, array $filas, array $anchos = []) {
        array_unshift($filas, $encabezados);

        if (!class_exists('ZipArchive')) {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $nombreArchivo . '.csv"');
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            foreach ($filas as $fila) fputcsv($out, $fila, ';');
            fclose($out);
            return;
        }

        $esc = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_XML1, 'UTF-8'); };

        $xmlFilas = '';
        foreach ($filas as $r => $fila) {
            $xmlFilas .= '<row r="' . ($r + 1) . '">';
            foreach (array_values($fila) as $c => $valor) {
                $ref = self::columna($c) . ($r + 1);
                $estilo = $r === 0 ? ' s="1"' : '';
                if (is_int($valor) || is_float($valor)) {
                    $xmlFilas .= '<c r="' . $ref . '"' . $estilo . '><v>' . $valor . '</v></c>';
                } else {
                    $xmlFilas .= '<c r="' . $ref . '"' . $estilo . ' t="inlineStr"><is><t xml:space="preserve">' . $esc($valor) . '</t></is></c>';
                }
            }
            $xmlFilas .= '</row>';
        }

        $xmlCols = '';
        foreach (array_values($anchos) as $c => $ancho) {
            $xmlCols .= '<col min="' . ($c + 1) . '" max="' . ($c + 1) . '" width="' . (float)$ancho . '" customWidth="1"/>';
        }
        if ($xmlCols !== '') $xmlCols = '<cols>' . $xmlCols . '</cols>';

        // Excel no admite más de 31 caracteres ni : \ / ? * [ ] en el nombre de la hoja
        $hoja = mb_substr(str_replace([':', '\\', '/', '?', '*', '[', ']'], ' ', $hoja), 0, 31, 'UTF-8');

        $cab    = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $nsMain = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $nsRel  = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
        $nsPkg  = 'http://schemas.openxmlformats.org/package/2006';
        $archivos = [
            '[Content_Types].xml' => $cab . '<Types xmlns="' . $nsPkg . '/content-types">'
                . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
                . '<Default Extension="xml" ContentType="application/xml"/>'
                . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
                . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
                . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
                . '</Types>',
            '_rels/.rels' => $cab . '<Relationships xmlns="' . $nsPkg . '/relationships">'
                . '<Relationship Id="rId1" Type="' . $nsRel . '/officeDocument" Target="xl/workbook.xml"/>'
                . '</Relationships>',
            'xl/workbook.xml' => $cab . '<workbook xmlns="' . $nsMain . '" xmlns:r="' . $nsRel . '">'
                . '<sheets><sheet name="' . $esc($hoja) . '" sheetId="1" r:id="rId1"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => $cab . '<Relationships xmlns="' . $nsPkg . '/relationships">'
                . '<Relationship Id="rId1" Type="' . $nsRel . '/worksheet" Target="worksheets/sheet1.xml"/>'
                . '<Relationship Id="rId2" Type="' . $nsRel . '/styles" Target="styles.xml"/>'
                . '</Relationships>',
            // Estilo 1 = encabezado en negrita
            'xl/styles.xml' => $cab . '<styleSheet xmlns="' . $nsMain . '">'
                . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
                . '<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
                . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
                . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
                . '<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
                . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/></cellXfs>'
                . '</styleSheet>',
            'xl/worksheets/sheet1.xml' => $cab . '<worksheet xmlns="' . $nsMain . '">'
                . $xmlCols . '<sheetData>' . $xmlFilas . '</sheetData></worksheet>',
        ];

        $tmp = tempnam(sys_get_temp_dir(), 'xls');
        $zip = new ZipArchive();
        $zip->open($tmp, ZipArchive::OVERWRITE);
        foreach ($archivos as $ruta => $contenido) {
            $zip->addFromString($ruta, $contenido);
        }
        $zip->close();

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '.xlsx"');
        header('Content-Length: ' . filesize($tmp));
        header('Cache-Control: max-age=0');
        readfile($tmp);
        unlink($tmp);
    }

    // Índice de columna (0 = A, 25 = Z, 26 = AA…)
    private static function columna($i) {
        $letras = '';
        for ($i++; $i > 0; $i = intdiv($i - 1, 26)) {
            $letras = chr(65 + ($i - 1) % 26) . $letras;
        }
        return $letras;
    }

    // Filtro de texto sin distinguir mayúsculas, igual que los buscadores de las pantallas
    public static function contiene($texto, $q) {
        return mb_strpos(mb_strtolower((string)$texto, 'UTF-8'), $q) !== false;
    }
}
