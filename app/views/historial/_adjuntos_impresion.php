<?php
/**
 * Dibuja los adjuntos al final de una impresión del historial: cada página de un PDF y cada imagen en su propia hoja.
 * Recibe $adjuntosImprimir: lista de ['id', 'nombre', 'titulo'] en el orden en que deben salir.
 * Expone window.adjuntosListos (promesa): la impresión espera a que todo esté dibujado antes de abrir el diálogo.
 * Los archivos se piden a historial/adjunto con la sesión del usuario; los PDF los convierte pdf.js en páginas imprimibles y las imágenes se colocan tal cual.
 */
$adjuntosImprimir = $adjuntosImprimir ?? [];
?>
<?php if ($adjuntosImprimir): ?>
<style>
    .adjuntos-estado { font-size: 11px; font-weight: 600; color: #4f46e5; align-self: center; }
    .adjunto-pag { break-before: page; page-break-before: always; text-align: center; }
    .adjunto-cap { font-size: 10px; color: #6b7280; text-align: left; margin-bottom: 6px; }
    .adjunto-pag img, .adjunto-pag canvas { display: block; margin: 0 auto; max-width: 100%; max-height: 232mm; width: auto; height: auto; border: 1px solid #e5e7eb; }
    .adjunto-error { break-before: page; page-break-before: always; border: 1px dashed #dc2626; color: #dc2626; padding: 14px; font-size: 11px; }
    @media print { .adjunto-pag canvas, .adjunto-pag img { border: none; } }
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
        const hayPdfjs = typeof pdfjsLib !== 'undefined';
        if (hayPdfjs) pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

        let n = 0;
        for (const o of document.querySelectorAll('.adjunto-origen')) {
            n++;
            if (estado) estado.textContent = 'Preparando adjuntos (' + n + ' de ' + total + ')…';
            try {
                const resp = await fetch(o.dataset.url, { credentials: 'include' });
                if (!resp.ok) throw new Error('http ' + resp.status);
                const tipo = resp.headers.get('content-type') || '';

                if (tipo.startsWith('image/')) {
                    // Imagen: una hoja con la imagen completa
                    const url = URL.createObjectURL(await resp.blob());
                    const img = new Image();
                    await new Promise((ok, fallo) => { img.onload = ok; img.onerror = fallo; img.src = url; });
                    const hoja = document.createElement('div');
                    hoja.className = 'adjunto-pag';
                    const cap = document.createElement('div');
                    cap.className = 'adjunto-cap';
                    cap.textContent = o.dataset.titulo + ' · ' + o.dataset.nombre;
                    hoja.appendChild(cap);
                    hoja.appendChild(img);
                    contenedor.appendChild(hoja);
                    continue;
                }

                if (!hayPdfjs) throw new Error('sin visor de PDF');
                const pdf = await pdfjsLib.getDocument({ data: await resp.arrayBuffer() }).promise;
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
