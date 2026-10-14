// Captura de fotos para comprobantes: usa la cámara del equipo y, si no está disponible
// (sin permiso, sin cámara o página sin HTTPS), abre el selector de archivos, que en el celular ofrece la cámara.
// Uso: Camara.tomar().then(dataUrl => { ... })  ·  Camara.elegirArchivo().then(dataUrl => { ... })
// Devuelve siempre un JPEG reducido (lado mayor 1600 px) como data URL, o null si se cancela.
const Camara = (() => {
    const LADO_MAX = 1600;
    const CALIDAD  = 0.82;
    let modal = null, video = null, stream = null, resolver = null;

    function aJpeg(fuente, ancho, alto) {
        const escala = Math.min(1, LADO_MAX / Math.max(ancho, alto));
        const canvas = document.createElement('canvas');
        canvas.width  = Math.round(ancho * escala);
        canvas.height = Math.round(alto * escala);
        canvas.getContext('2d').drawImage(fuente, 0, 0, canvas.width, canvas.height);
        return canvas.toDataURL('image/jpeg', CALIDAD);
    }

    function elegirArchivo() {
        return new Promise(resolve => {
            const inp = document.createElement('input');
            inp.type = 'file';
            inp.accept = 'image/*';
            inp.setAttribute('capture', 'environment');   // en el celular abre la cámara trasera
            inp.onchange = () => {
                const archivo = inp.files[0];
                if (!archivo) { resolve(null); return; }
                const img = new Image();
                img.onload  = () => { resolve(aJpeg(img, img.naturalWidth, img.naturalHeight)); URL.revokeObjectURL(img.src); };
                img.onerror = () => { alert('El archivo elegido no es una imagen.'); resolve(null); };
                img.src = URL.createObjectURL(archivo);
            };
            inp.click();
        });
    }

    function crearModal() {
        const div = document.createElement('div');
        div.className = 'modal fade';
        div.tabIndex = -1;
        div.innerHTML = `
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold"><i class="bi bi-camera-fill text-primary me-2"></i>Tomar foto del comprobante</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <video autoplay playsinline muted style="width:100%;border-radius:10px;background:#0f172a;max-height:60vh;"></video>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary fw-bold px-4" data-capturar><i class="bi bi-camera me-1"></i>Capturar</button>
                    </div>
                </div>
            </div>`;
        document.body.appendChild(div);
        video = div.querySelector('video');
        div.querySelector('[data-capturar]').addEventListener('click', () => {
            if (!video.videoWidth) return;
            const foto = aJpeg(video, video.videoWidth, video.videoHeight);
            const r = resolver; resolver = null;
            bootstrap.Modal.getInstance(div).hide();
            if (r) r(foto);
        });
        // Al cerrar (capturando o cancelando) se apaga la cámara
        div.addEventListener('hidden.bs.modal', () => {
            if (stream) { stream.getTracks().forEach(t => t.stop()); stream = null; }
            video.srcObject = null;
            if (resolver) { const r = resolver; resolver = null; r(null); }
        });
        return div;
    }

    async function tomar() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) return elegirArchivo();
        try {
            stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false });
        } catch (e) {
            return elegirArchivo();   // sin permiso o sin cámara
        }
        modal = modal || crearModal();
        video.srcObject = stream;
        return new Promise(resolve => {
            resolver = resolve;
            bootstrap.Modal.getOrCreateInstance(modal).show();
        });
    }

    return { tomar, elegirArchivo };
})();
