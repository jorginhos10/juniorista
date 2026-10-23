<?php
/**
 * Dibuja los PDF adjuntos al final de una impresión del historial (cada página del PDF en su propia hoja).
 * Recibe $adjuntosImprimir: lista de ['id', 'nombre', 'titulo'] en el orden en que deben salir.
 * Expone window.adjuntosListos (promesa): la impresión espera a que todo esté dibujado antes de abrir el diálogo.
 * Los PDF se piden a historial/adjunto con la sesión del usuario; la biblioteca pdf.js los convierte en páginas imprimibles.
 */
$adjuntosImprimir = $adjuntosImprimir ?? [];
?>
<?php if ($adjuntosImprimir): ?>
<style>
    .adjuntos-estado { font-size: 11px; font-weight: 600; color: #4f46e5; align-self: center; }
    .adjunto-pag { break-before: page; page-break-before: always; text-align: center; }
    .adjunto-cap { font-size: 10px; color: #6b7280; text-align: left; margin-bottom: 6px; }
    .adjunto-pag canvas { display: block; margin: 0 auto; max-width: 100%; max-height: 232mm; width: auto; height: auto; border: 1px solid #e5e7eb; }
    .adjunto-error { break-before: page; page-break-before: always; border: 1px dashed #dc2626; color: #dc2626; padding: 14px; font-size: 11px; }
    @media print { .adjunto-pag canvas { border: none; } }
</style>

<div id="adjuntosImpresion">
    <?php foreach ($adjuntosImprimir as $a): ?>
    <div class="adjunto-origen" hidden
         data-url="<?= BASE_URL ?>/historial/adjunto?id=<?= (int)$a['id'] ?>"
         data-nombre="<?= htmlspecialchars($a['nombre']) ?>"
         data-titulo="<?= htmlspecialchars($a['titulo']) ?>"></div>
    <?php endforeach; ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
(function () {
    const botones = () => document.querySelectorAll('[data-imprimir]');
    const estado  = document.getElementById('adjuntosEstado');
    const total   = document.querySelectorAll('.adjunto-origen').length;
    botones().forEach(b => { b.disabled = true; b.dataset.texto = b.textContent; b.textContent = 'Preparando adjuntos…'; });

    function avisoError(contenedor, texto) {
        const d = document.createElement('div');
        d.className = 'adjunto-error';
        d.textContent = texto;
        contenedor.appendChild(d);
    }

    window.adjuntosListos = (async () => {
        const contenedor = document.getElementById('adjuntosImpresion');
        if (typeof pdfjsLib === 'undefined') {
            avisoError(contenedor, 'No se pudo cargar el visor de PDF (sin conexión): los adjuntos no se imprimirán.');
            return;
        }
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

        let n = 0;
        for (const o of document.querySelectorAll('.adjunto-origen')) {
            n++;
            if (estado) estado.textContent = 'Preparando adjuntos (' + n + ' de ' + total + ')…';
            try {
                const pdf = await pdfjsLib.getDocument({ url: o.dataset.url, withCredentials: true }).promise;
                for (let p = 1; p <= pdf.numPages; p++) {
                    const pagina = await pdf.getPage(p);
                    const ancho  = pagina.getViewport({ scale: 1 }).width;
                    const vista  = pagina.getViewport({ scale: Math.min(2.5, Math.max(1, 1100 / ancho)) });
                    const canvas = document.createElement('canvas');
                    canvas.width = vista.width;
                    canvas.height = vista.height;
                    await pagina.render({ canvasContext: canvas.getContext('2d'), viewport: vista }).promise;

                    const hoja = document.createElement('div');
                    hoja.className = 'adjunto-pag';
                    const cap = document.createElement('div');
                    cap.className = 'adjunto-cap';
                    cap.textContent = o.dataset.titulo + ' · ' + o.dataset.nombre + ' · página ' + p + ' de ' + pdf.numPages;
                    hoja.appendChild(cap);
                    hoja.appendChild(canvas);
                    contenedor.appendChild(hoja);
                }
            } catch (e) {
                avisoError(contenedor, 'No se pudo cargar el adjunto «' + o.dataset.nombre + '».');
            }
        }
    })().finally(() => {
        if (estado) estado.textContent = '';
        botones().forEach(b => { b.disabled = false; b.textContent = b.dataset.texto || 'Imprimir'; });
    });
})();
</script>
<?php else: ?>
<script>window.adjuntosListos = Promise.resolve();</script>
<?php endif; ?>
