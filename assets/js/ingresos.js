// Buscador en tabla principal
document.getElementById('buscador')?.addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#tablaIngresos tbody tr').forEach(tr => {
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});

// ── LÍNEAS DE PRODUCTOS ──────────────────────────────

let lineaIdx = 0;

function agregarLinea() {
    const body = document.getElementById('lineasBody');
    const idx  = lineaIdx++;

    const div = document.createElement('div');
    div.className = 'linea-row';
    div.id = `linea-${idx}`;
    div.innerHTML = `
        <div class="linea-col linea-col-producto">
            <label class="linea-lbl d-md-none">Producto</label>
            <div class="prod-ac" id="ac-${idx}">
                <input type="text" class="form-control form-control-sm prod-ac-input"
                       placeholder="Buscar producto…" autocomplete="off">
                <input type="hidden" name="producto_id[]" class="prod-ac-value" required>
                <div class="prod-ac-list"></div>
            </div>
            <div class="linea-lote">
                <input type="text" class="form-control form-control-sm" name="lote[]" id="lote-${idx}"
                       maxlength="60" placeholder="Lote (opcional)" autocomplete="off">
                <div class="input-group input-group-sm">
                    <span class="input-group-text px-2" title="Fecha de vencimiento (opcional)">Vence</span>
                    <input type="date" class="form-control" name="fecha_vencimiento[]" id="vence-${idx}"
                           title="Fecha de vencimiento (opcional)">
                </div>
            </div>
        </div>
        <div class="linea-col linea-col-cant">
            <label class="linea-lbl d-md-none">Cantidad</label>
            <div class="input-group input-group-sm">
                <input type="number" class="form-control text-center" name="cantidad[]"
                       id="cant-${idx}" min="1" value="1"
                       oninput="recalcularLinea(${idx})" required>
                <span class="input-group-text px-2" id="unidad-${idx}" style="font-size:.72rem;min-width:36px;">u.</span>
            </div>
        </div>
        <div class="linea-col linea-col-precio">
            <label class="linea-lbl d-md-none">P. Unitario</label>
            <div class="input-group input-group-sm">
                <span class="input-group-text px-2">$</span>
                <input type="number" class="form-control" name="precio_unitario[]"
                       id="precio-${idx}" step="0.01" min="0" value="0.00"
                       oninput="recalcularLinea(${idx})">
            </div>
        </div>
        <div class="linea-col linea-col-sub">
            <label class="linea-lbl d-md-none">Subtotal</label>
            <div class="linea-subtotal" id="sub-${idx}">$0.00</div>
        </div>
        <div class="linea-col linea-col-del">
            <button type="button" class="btn btn-outline-danger btn-sm btn-accion"
                    onclick="quitarLinea(${idx})" title="Quitar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>`;

    body.appendChild(div);

    initAutocomplete(
        div.querySelector(`#ac-${idx}`),
        PRODUCTOS,
        ({ precio, unidad }) => {
            document.getElementById(`precio-${idx}`).value       = precio.toFixed(2);
            document.getElementById(`unidad-${idx}`).textContent = unidad || 'u.';
            recalcularLinea(idx);
        },
        () => {
            document.getElementById(`precio-${idx}`).value       = '0.00';
            document.getElementById(`unidad-${idx}`).textContent = 'u.';
            recalcularLinea(idx);
        },
        'precio_compra',
        true  // ingresos: permitir seleccionar productos aunque tengan stock 0
    );

    actualizarUI();
    div.querySelector('.prod-ac-input').focus();
}

function recalcularLinea(idx) {
    const cant   = parseFloat(document.getElementById(`cant-${idx}`)?.value)  || 0;
    const precio = parseFloat(document.getElementById(`precio-${idx}`)?.value) || 0;
    const sub    = document.getElementById(`sub-${idx}`);
    if (sub) sub.textContent = '$' + (cant * precio).toFixed(2);
    recalcularTotal();
}

function recalcularTotal() {
    let total = 0;
    document.querySelectorAll('.linea-row').forEach(row => {
        const idx   = row.id.replace('linea-', '');
        const cant  = parseFloat(document.getElementById(`cant-${idx}`)?.value)  || 0;
        const prec  = parseFloat(document.getElementById(`precio-${idx}`)?.value) || 0;
        total += cant * prec;
    });
    document.getElementById('totalGeneral').textContent = '$' + total.toFixed(2);
}

function quitarLinea(idx) {
    document.getElementById(`linea-${idx}`)?.remove();
    recalcularTotal();
    actualizarUI();
}

function actualizarUI() {
    const n          = document.querySelectorAll('.linea-row').length;
    const msg        = document.getElementById('msgSinLineas');
    const total      = document.getElementById('totalWrapper');
    const header     = document.querySelector('.linea-header');
    const btnGuardar = document.getElementById('btnGuardar');

    msg.style.display    = n === 0 ? '' : 'none';
    total.style.display  = n > 0 ? '' : 'none';
    if (header) header.style.display = n > 0 ? '' : 'none';
    btnGuardar.disabled  = n === 0;
}

// Reset modal al abrir solo si es modo "nuevo" (id = 0)
document.getElementById('modalIngreso')?.addEventListener('show.bs.modal', () => {
    if (document.getElementById('inp-ingreso-id').value !== '0') return; // edición: ya fue preparado
    document.getElementById('lineasBody').innerHTML = '';
    document.getElementById('totalGeneral').textContent = '$0.00';
    const header = document.querySelector('.linea-header');
    if (header) header.style.display = 'none';
    lineaIdx = 0;
    actualizarUI();
    agregarLinea();
});

// ── EDITAR INGRESO ───────────────────────────────────

function editarIngreso(id, proveedor, notas) {
    // Preparar modal en modo edición
    document.getElementById('lineasBody').innerHTML = '';
    document.getElementById('totalGeneral').textContent = '$0.00';
    const header = document.querySelector('.linea-header');
    if (header) header.style.display = 'none';
    lineaIdx = 0;

    document.getElementById('inp-ingreso-id').value = id;
    document.getElementById('formIngreso').action   = `${BASE_URL}/ingresos/editar`;
    document.getElementById('modalIngresoTitulo').innerHTML =
        `<i class="bi bi-pencil-fill text-warning me-2"></i>Editar ingreso`;

    // Rellenar cabecera
    document.querySelector('[name="proveedor"]').value = proveedor;
    document.querySelector('[name="notas"]').value     = notas;

    // Cargar líneas existentes vía AJAX
    fetch(`${BASE_URL}/ingresos/detalle?id=${id}`)
        .then(r => r.json())
        .then(lineas => {
            lineas.forEach(l => {
                agregarLinea();
                const idx  = lineaIdx - 1;
                const prod = PRODUCTOS.find(p => p.id == l.producto_id);
                const ac   = document.querySelector(`#linea-${idx} #ac-${idx}`);
                if (ac && prod) {
                    ac.querySelector('.prod-ac-input').value = prod.nombre;
                    ac.querySelector('.prod-ac-value').value = prod.id;
                }
                document.getElementById(`unidad-${idx}`).textContent = l.unidad || 'u.';
                document.getElementById(`cant-${idx}`).value          = l.cantidad;
                document.getElementById(`precio-${idx}`).value        = parseFloat(l.precio_unitario).toFixed(2);
                document.getElementById(`lote-${idx}`).value          = l.lote || '';
                document.getElementById(`vence-${idx}`).value         = l.fecha_vencimiento || '';
                recalcularLinea(idx);
            });

            new bootstrap.Modal(document.getElementById('modalIngreso')).show();
        });
}

// Restaurar modal a modo "nuevo" al cerrar
document.getElementById('modalIngreso')?.addEventListener('hidden.bs.modal', () => {
    document.getElementById('inp-ingreso-id').value = '0';
    document.getElementById('formIngreso').action   = `${BASE_URL}/ingresos/registrar`;
    document.getElementById('modalIngresoTitulo').innerHTML =
        `<i class="bi bi-box-arrow-in-down-right text-success me-2"></i>Nuevo ingreso de productos`;
    document.querySelector('[name="proveedor"]').value = '';
    document.querySelector('[name="notas"]').value     = '';
});

// ── MODAL DETALLE ────────────────────────────────────

// Lote y vencimiento de una línea para el detalle (vencido en rojo, por vencer en ≤30 días en amarillo)
function loteVencimientoHtml(l) {
    const partes = [];
    if (l.lote) partes.push(`<span class="text-muted small">Lote: ${escHtml(l.lote)}</span>`);
    if (l.fecha_vencimiento) {
        const [a, m, d] = l.fecha_vencimiento.split('-').map(Number);
        const hoy = new Date(); hoy.setHours(0, 0, 0, 0);
        const dias = Math.round((new Date(a, m - 1, d) - hoy) / 86400000);
        const fecha = `${String(d).padStart(2, '0')}/${String(m).padStart(2, '0')}/${a}`;
        const clase = dias < 0 ? 'bg-danger' : (dias <= 30 ? 'bg-warning text-dark' : 'bg-light text-secondary border');
        const texto = dias < 0 ? `Venció ${fecha}` : `Vence ${fecha}`;
        partes.push(`<span class="badge ${clase}">${texto}</span>`);
    }
    return partes.length ? `<div class="d-flex flex-wrap align-items-center gap-2 mt-1">${partes.join('')}</div>` : '';
}

function verDetalle(id, proveedor) {
    document.getElementById('detalleProveedor').textContent = proveedor;
    document.getElementById('detalleBody').innerHTML =
        '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>';

    new bootstrap.Modal(document.getElementById('modalDetalle')).show();

    fetch(`${BASE_URL}/ingresos/detalle?id=${id}`)
        .then(r => r.json())
        .then(lineas => {
            if (!lineas.length) {
                document.getElementById('detalleBody').innerHTML =
                    '<p class="text-muted text-center py-3">Sin productos registrados.</p>';
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
                        ${loteVencimientoHtml(l)}
                    </td>
                    <td class="text-center">
                        <span class="badge bg-success-soft text-success">+${l.cantidad} ${escHtml(l.unidad)}</span>
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
                                <th class="text-end">P. Unitario</th>
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
        })
        .catch(() => {
            document.getElementById('detalleBody').innerHTML =
                '<p class="text-danger text-center py-3">Error al cargar el detalle.</p>';
        });
}
