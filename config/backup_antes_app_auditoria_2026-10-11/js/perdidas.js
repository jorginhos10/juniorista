let lineaIdxP = 0;

const motivoConfig = {
    perdida:     { clase: 'motivo-perdida',     texto: 'Los productos serán descontados del stock por Pérdida' },
    vencimiento: { clase: 'motivo-vencimiento', texto: 'Los productos serán retirados del stock por Vencimiento' },
    a_bodega:    { clase: 'motivo-bodega',       texto: 'Los productos serán trasladados A Bodega' },
    propietario: { clase: 'motivo-propietario', texto: 'Los productos fueron Tomados por el propietario' },
};

function actualizarColorMotivo(sel) {
    const val    = sel.value;
    const banner = document.getElementById('motivoBanner');
    const texto  = document.getElementById('motivoBannerTexto');
    const cfg    = motivoConfig[val] || motivoConfig.perdida;
    banner.className  = 'motivo-banner ' + cfg.clase;
    texto.textContent = cfg.texto;
}

// ── LÍNEAS ───────────────────────────────────────────

function agregarLineaP() {
    const body = document.getElementById('lineasBodyP');
    const idx  = lineaIdxP++;

    // Solo productos con stock > 0
    const div = document.createElement('div');
    div.className = 'linea-row';
    div.id = `lineap-${idx}`;
    div.innerHTML = `
        <div class="linea-col linea-col-producto">
            <label class="linea-lbl d-md-none">Producto</label>
            <div class="prod-ac" id="acp-${idx}">
                <input type="text" class="form-control form-control-sm prod-ac-input"
                       placeholder="Buscar producto…" autocomplete="off">
                <input type="hidden" name="producto_id[]" class="prod-ac-value" required>
                <div class="prod-ac-list"></div>
            </div>
        </div>
        <div class="linea-col linea-col-cant">
            <label class="linea-lbl d-md-none">Cantidad</label>
            <div class="input-group input-group-sm">
                <input type="number" class="form-control text-center" name="cantidad[]"
                       id="cantp-${idx}" min="1" max="0" value="1"
                       oninput="validarCantidadP(this, ${idx})" required>
                <span class="input-group-text px-2" id="unidadp-${idx}" style="font-size:.72rem;min-width:36px;">u.</span>
            </div>
            <div class="text-danger mt-1" id="errp-${idx}" style="font-size:.7rem;display:none;"></div>
        </div>
        <div class="linea-col linea-col-precio">
            <label class="linea-lbl d-md-none">P. Referencia</label>
            <div class="input-group input-group-sm">
                <span class="input-group-text px-2">$</span>
                <input type="number" class="form-control" name="precio_unitario[]"
                       id="preciop-${idx}" step="0.01" min="0" value="0.00"
                       oninput="recalcularLineaP(${idx})">
            </div>
        </div>
        <div class="linea-col linea-col-sub">
            <label class="linea-lbl d-md-none">Subtotal</label>
            <div class="linea-subtotal" id="subp-${idx}">$0.00</div>
        </div>
        <div class="linea-col linea-col-del">
            <button type="button" class="btn btn-outline-danger btn-sm btn-accion"
                    onclick="quitarLineaP(${idx})">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>`;

    body.appendChild(div);

    initAutocomplete(
        div.querySelector(`#acp-${idx}`),
        PRODUCTOS_P,
        ({ stock, precio, unidad }) => {
            const inpCant = document.getElementById(`cantp-${idx}`);
            inpCant.max   = stock > 0 ? stock : 0;
            inpCant.value = stock > 0 ? Math.min(parseInt(inpCant.value) || 1, stock) : 0;
            document.getElementById(`preciop-${idx}`).value       = precio.toFixed(2);
            document.getElementById(`unidadp-${idx}`).textContent = unidad || 'u.';
            validarCantidadP(inpCant, idx);
        },
        () => {
            const inpCant = document.getElementById(`cantp-${idx}`);
            inpCant.max   = 0;
            inpCant.value = 1;
            document.getElementById(`errp-${idx}`).style.display  = 'none';
            inpCant.classList.remove('is-invalid');
            document.getElementById(`preciop-${idx}`).value       = '0.00';
            document.getElementById(`unidadp-${idx}`).textContent = 'u.';
            document.getElementById(`subp-${idx}`).textContent    = '$0.00';
            recalcularTotalP();
            actualizarBtnGuardar();
        },
        'precio_venta'
    );

    actualizarUIP();
    div.querySelector('.prod-ac-input').focus();
}

function validarCantidadP(input, idx) {
    const sel   = document.querySelector(`#lineap-${idx} select[name="producto_id[]"]`);
    const opt   = sel?.options[sel.selectedIndex];
    const stock = parseInt(opt?.dataset.stock || 0);
    const cant  = parseInt(input.value) || 0;
    const err   = document.getElementById(`errp-${idx}`);

    if (!opt?.value) { recalcularLineaP(idx); return; }

    if (stock <= 0) {
        err.textContent    = 'Sin stock disponible.';
        err.style.display  = '';
        input.value        = 0;
        input.classList.add('is-invalid');
    } else if (cant > stock) {
        err.textContent    = `Máximo disponible: ${stock}`;
        err.style.display  = '';
        input.value        = stock;
        input.classList.add('is-invalid');
    } else if (cant <= 0) {
        err.textContent    = 'La cantidad debe ser al menos 1.';
        err.style.display  = '';
        input.classList.add('is-invalid');
    } else {
        err.style.display  = 'none';
        input.classList.remove('is-invalid');
    }

    recalcularLineaP(idx);
    actualizarBtnGuardar();
}

function actualizarBtnGuardar() {
    const hayErrores = document.querySelectorAll('#lineasBodyP .is-invalid').length > 0;
    const hayLineas  = document.querySelectorAll('#lineasBodyP .linea-row').length > 0;
    document.getElementById('btnGuardarP').disabled = hayErrores || !hayLineas;
}

function recalcularLineaP(idx) {
    const cant  = parseFloat(document.getElementById(`cantp-${idx}`)?.value)   || 0;
    const prec  = parseFloat(document.getElementById(`preciop-${idx}`)?.value) || 0;
    const sub   = document.getElementById(`subp-${idx}`);
    if (sub) sub.textContent = '$' + (cant * prec).toFixed(2);
    recalcularTotalP();
}

function recalcularTotalP() {
    let total = 0;
    document.querySelectorAll('#lineasBodyP .linea-row').forEach(row => {
        const idx  = row.id.replace('lineap-', '');
        const cant = parseFloat(document.getElementById(`cantp-${idx}`)?.value)   || 0;
        const prec = parseFloat(document.getElementById(`preciop-${idx}`)?.value) || 0;
        total += cant * prec;
    });
    document.getElementById('totalGeneralP').textContent = '$' + total.toFixed(2);
}

function quitarLineaP(idx) {
    document.getElementById(`lineap-${idx}`)?.remove();
    recalcularTotalP();
    actualizarUIP();
}

function actualizarUIP() {
    const n      = document.querySelectorAll('#lineasBodyP .linea-row').length;
    const header = document.querySelector('.linea-header');
    document.getElementById('msgSinLineasP').style.display  = n === 0 ? '' : 'none';
    document.getElementById('totalWrapperP').style.display  = n > 0  ? '' : 'none';
    if (header) header.style.display = n > 0 ? '' : 'none';
    actualizarBtnGuardar();
}

// Reset al abrir (solo modo nuevo)
document.getElementById('modalPerdida')?.addEventListener('show.bs.modal', () => {
    if (document.getElementById('inp-perdida-id').value !== '0') return;
    document.getElementById('lineasBodyP').innerHTML = '';
    document.getElementById('totalGeneralP').textContent = '$0.00';
    lineaIdxP = 0;
    actualizarUIP();
    agregarLineaP();
    actualizarColorMotivo(document.getElementById('inp-motivo'));
});

// Restaurar al cerrar
document.getElementById('modalPerdida')?.addEventListener('hidden.bs.modal', () => {
    document.getElementById('inp-perdida-id').value = '0';
    document.getElementById('formPerdida').action   = `${BASE_URL}/perdidas/registrar`;
    document.getElementById('modalPerdidaTitulo').innerHTML =
        `<i class="bi bi-box-arrow-up-right text-danger me-2"></i>Registrar pérdida de productos`;
    document.getElementById('btnGuardarPTexto').textContent = 'Registrar pérdida';
    document.getElementById('inp-responsable').value = '';
    document.getElementById('inp-notas-cab').value   = '';
    document.getElementById('inp-motivo').value = 'perdida';
    actualizarColorMotivo(document.getElementById('inp-motivo'));
});

// ── EDITAR ───────────────────────────────────────────

function editarPerdida(id, motivo, responsable, notas) {
    document.getElementById('lineasBodyP').innerHTML = '';
    document.getElementById('totalGeneralP').textContent = '$0.00';
    lineaIdxP = 0;

    document.getElementById('inp-perdida-id').value = id;
    document.getElementById('formPerdida').action   = `${BASE_URL}/perdidas/editar`;
    document.getElementById('modalPerdidaTitulo').innerHTML =
        `<i class="bi bi-pencil-fill text-warning me-2"></i>Editar pérdida`;
    document.getElementById('btnGuardarPTexto').textContent = 'Guardar cambios';
    document.getElementById('inp-motivo').value       = motivo;
    document.getElementById('inp-responsable').value  = responsable;
    document.getElementById('inp-notas-cab').value    = notas;
    actualizarColorMotivo(document.getElementById('inp-motivo'));

    fetch(`${BASE_URL}/perdidas/detalle?id=${id}`)
        .then(r => r.json())
        .then(lineas => {
            lineas.forEach(l => {
                agregarLineaP();
                const idx  = lineaIdxP - 1;
                const prod = PRODUCTOS_P.find(p => p.id == l.producto_id);
                const ac   = document.querySelector(`#lineap-${idx} #acp-${idx}`);
                if (ac && prod) {
                    ac.querySelector('.prod-ac-input').value = prod.nombre;
                    ac.querySelector('.prod-ac-value').value = prod.id;
                }
                // En edición: max = stock_actual + cantidad_original
                const maxCant = (prod?.stock || 0) + parseInt(l.cantidad);
                const inpCant = document.getElementById(`cantp-${idx}`);
                inpCant.max   = maxCant;
                inpCant.value = l.cantidad;
                document.getElementById(`unidadp-${idx}`).textContent = l.unidad || 'u.';
                document.getElementById(`preciop-${idx}`).value        = parseFloat(l.precio_unitario).toFixed(2);
                recalcularLineaP(idx);
            });
            actualizarUIP();
            new bootstrap.Modal(document.getElementById('modalPerdida')).show();
        });
}

// ── DETALLE ──────────────────────────────────────────

function verDetalle(id, motivo) {
    document.getElementById('detalleMotivo').textContent = motivo;
    document.getElementById('detalleBody').innerHTML =
        '<div class="text-center py-4"><div class="spinner-border text-danger"></div></div>';
    new bootstrap.Modal(document.getElementById('modalDetalle')).show();

    fetch(`${BASE_URL}/perdidas/detalle?id=${id}`)
        .then(r => r.json())
        .then(lineas => {
            if (!lineas.length) {
                document.getElementById('detalleBody').innerHTML = '<p class="text-center text-muted py-3">Sin productos.</p>';
                return;
            }
            let total = 0;
            const filas = lineas.map((l, i) => {
                const sub = parseFloat(l.subtotal);
                total += sub;
                return `<tr>
                    <td class="ps-3 text-muted small">${i + 1}</td>
                    <td>
                        <div class="fw-semibold">${escHtml(l.producto_nombre)}</div>
                        ${l.codigo ? `<div class="text-muted small">Cód: ${escHtml(l.codigo)}</div>` : ''}
                    </td>
                    <td class="text-center">
                        <span class="badge bg-danger-soft text-danger">-${l.cantidad} ${escHtml(l.unidad)}</span>
                    </td>
                    <td class="text-end text-muted small">$${parseFloat(l.precio_unitario).toFixed(2)}</td>
                    <td class="text-end fw-semibold">$${sub.toFixed(2)}</td>
                </tr>`;
            }).join('');
            document.getElementById('detalleBody').innerHTML = `
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light" style="font-size:.8rem;">
                            <tr>
                                <th class="ps-3">#</th><th>Producto</th>
                                <th class="text-center">Cantidad</th>
                                <th class="text-end">P. Referencia</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>${filas}</tbody>
                        <tfoot>
                            <tr class="table-light fw-bold">
                                <td colspan="4" class="text-end pe-3">Total:</td>
                                <td class="text-end">$${total.toFixed(2)}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>`;
        });
}

// Buscador
document.getElementById('buscador')?.addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#tablaPerdidas tbody tr').forEach(tr => {
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});
