// carrito: { id: { nombre, precio, unidad, stock, cantidad } }
const carrito = {};

let categoriaActiva = '';

// ── FILTROS ───────────────────────────────────────────

function filtrarProductos() {
    const q    = document.getElementById('buscarProducto').value.toLowerCase();
    const cards = document.querySelectorAll('.pos-card');
    let visibles = 0;

    cards.forEach(card => {
        const nombre   = card.dataset.nombre.toLowerCase();
        const codigo   = (card.dataset.codigo || '').toLowerCase();
        const cat      = (card.dataset.categoria || '').toLowerCase();
        const matchQ   = !q || nombre.includes(q) || codigo.includes(q);
        const matchCat = !categoriaActiva || cat === categoriaActiva.toLowerCase();

        card.style.display = matchQ && matchCat ? '' : 'none';
        if (matchQ && matchCat) visibles++;
    });

    document.getElementById('sinResultados').classList.toggle('d-none', visibles > 0);
}

function filtrarCategoria(btn, cat) {
    categoriaActiva = cat;
    document.querySelectorAll('.pos-cat-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    filtrarProductos();
}

// ── CARRITO ───────────────────────────────────────────

function agregarAlCarrito(card) {
    const id     = card.dataset.id;
    const stock  = parseInt(card.dataset.stock);
    const nombre = card.dataset.nombre;
    const precio = parseFloat(card.dataset.precio);
    const unidad = card.dataset.unidad;

    if (carrito[id]) {
        if (carrito[id].cantidad >= carrito[id].stock) {
            mostrarToastError(`Stock máximo alcanzado (${stock} ${unidad})`);
            return;
        }
        carrito[id].cantidad++;
    } else {
        carrito[id] = { nombre, precio, unidad, stock, cantidad: 1 };
    }

    renderCarrito();
    actualizarBadge(id);
    pulsar(card);
}

function cambiarCantidad(id, delta) {
    if (!carrito[id]) return;
    const nueva = carrito[id].cantidad + delta;

    if (nueva <= 0) {
        quitarDelCarrito(id);
        return;
    }
    if (nueva > carrito[id].stock) {
        mostrarToastError(`Máximo disponible: ${carrito[id].stock} ${carrito[id].unidad}`);
        return;
    }
    carrito[id].cantidad = nueva;
    renderCarrito();
    actualizarBadge(id);
}

// Cantidad escrita directamente en el carrito (más rápido que pulsar + varias veces)
function fijarCantidad(id, input) {
    if (!carrito[id]) return;
    let nueva = parseInt(input.value, 10);

    if (isNaN(nueva) || nueva < 1) {
        // Vacío, 0 o negativo: se conserva la cantidad anterior (para quitar el producto está la X)
        nueva = carrito[id].cantidad;
    } else if (nueva > carrito[id].stock) {
        mostrarToastError(`Máximo disponible: ${carrito[id].stock} ${carrito[id].unidad}`);
        nueva = carrito[id].stock;
    }

    carrito[id].cantidad = nueva;
    renderCarrito();
    actualizarBadge(id);
}

function quitarDelCarrito(id) {
    delete carrito[id];
    renderCarrito();
    actualizarBadge(id);
}

function limpiarCarrito() {
    Object.keys(carrito).forEach(id => delete carrito[id]);
    document.querySelectorAll('.pos-card').forEach(c => {
        c.classList.remove('pos-card-en-carrito');
        const b = document.getElementById('badge-' + c.dataset.id);
        if (b) b.style.display = 'none';
    });
    renderCarrito();
}

// ── RENDER ────────────────────────────────────────────

function renderCarrito() {
    const container = document.getElementById('posCarrito');
    const vacio     = document.getElementById('carritoVacio');
    const items     = Object.entries(carrito);

    if (!items.length) {
        container.innerHTML = '';
        container.appendChild(vacio);
        vacio.style.display = '';
        document.getElementById('btnConfirmar').disabled = true;
        actualizarTotales();
        return;
    }

    const html = items.map(([id, item]) => `
        <div class="pos-carrito-item">
            <div class="pos-ci-nombre">
                <span class="fw-semibold">${escHtml(item.nombre)}</span>
                <span class="text-muted small">$${item.precio.toFixed(2)} / ${escHtml(item.unidad)}</span>
            </div>
            <div class="pos-ci-controles">
                <button class="pos-ci-btn" onclick="cambiarCantidad('${id}', -1)">
                    <i class="bi bi-dash"></i>
                </button>
                <input type="number" class="pos-ci-cant" value="${item.cantidad}" min="1" max="${item.stock}" step="1"
                       inputmode="numeric" aria-label="Cantidad" title="Escribe la cantidad"
                       onfocus="this.select()"
                       onkeydown="if (event.key === 'Enter') { event.preventDefault(); this.blur(); }"
                       onchange="fijarCantidad('${id}', this)">
                <button class="pos-ci-btn" onclick="cambiarCantidad('${id}', 1)"
                        ${item.cantidad >= item.stock ? 'disabled' : ''}>
                    <i class="bi bi-plus"></i>
                </button>
            </div>
            <div class="pos-ci-subtotal">$${(item.precio * item.cantidad).toFixed(2)}</div>
            <button class="pos-ci-del" onclick="quitarDelCarrito('${id}')">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    `).join('');

    container.innerHTML = html;
    document.getElementById('btnConfirmar').disabled = false;
    actualizarTotales();
}

function actualizarTotales() {
    const subtotal  = Object.values(carrito).reduce((s, i) => s + i.precio * i.cantidad, 0);
    const descuento = Math.min(parseFloat(document.getElementById('descuentoInput').value) || 0, subtotal);
    const total     = Math.max(0, subtotal - descuento);

    document.getElementById('totalSubtotal').textContent = '$' + subtotal.toFixed(2);
    document.getElementById('totalDescuento').textContent = '-$' + descuento.toFixed(2);
    document.getElementById('totalFinal').textContent    = '$' + total.toFixed(2);
}

function actualizarBadge(id) {
    const badge = document.getElementById('badge-' + id);
    const card  = document.querySelector(`.pos-card[data-id="${id}"]`);
    if (!badge || !card) return;

    if (carrito[id]) {
        badge.querySelector('span').textContent = carrito[id].cantidad;
        badge.style.display = '';
        card.classList.add('pos-card-en-carrito');
    } else {
        badge.style.display = 'none';
        card.classList.remove('pos-card-en-carrito');
    }
}

function pulsar(card) {
    card.classList.add('pos-card-pulsar');
    setTimeout(() => card.classList.remove('pos-card-pulsar'), 300);
}

// ── CONFIRMAR VENTA ───────────────────────────────────

function confirmarVenta() {
    const items = Object.entries(carrito);
    if (!items.length) return;

    const subtotal  = Object.values(carrito).reduce((s, i) => s + i.precio * i.cantidad, 0);
    const descuento = parseFloat(document.getElementById('descuentoInput').value) || 0;
    const total     = Math.max(0, subtotal - descuento);

    // fClienteId ya fue llenado por el autocomplete
    document.getElementById('fNotas').value       = document.getElementById('notasVenta').value;
    document.getElementById('fDescuento').value   = descuento;

    const fLineas = document.getElementById('fLineas');
    fLineas.innerHTML = '';
    items.forEach(([id, item]) => {
        fLineas.innerHTML += `
            <input type="hidden" name="producto_id[]"      value="${id}">
            <input type="hidden" name="cantidad[]"          value="${item.cantidad}">
            <input type="hidden" name="precio_unitario[]"   value="${item.precio}">
            <input type="hidden" name="desc_linea[]"        value="0">
        `;
    });

    document.getElementById('formVentaOculto').submit();
}

// ── TOAST TEMPORAL ────────────────────────────────────

function mostrarToastError(msg) {
    let t = document.getElementById('toastTemp');
    if (!t) {
        t = document.createElement('div');
        t.id = 'toastTemp';
        t.className = 'pos-toast pos-toast-err';
        document.body.appendChild(t);
    }
    t.textContent = msg;
    t.style.display = '';
    clearTimeout(t._timer);
    t._timer = setTimeout(() => { t.style.display = 'none'; }, 2500);
}

// Auto-ocultar toast de flash
const flashToast = document.getElementById('posToast');
if (flashToast) setTimeout(() => { flashToast.style.opacity = '0'; setTimeout(() => flashToast.remove(), 400); }, 3000);

// ── DETALLE (HISTORIAL) ───────────────────────────────

function verDetalle(id, cliente) {
    document.getElementById('detalleCliente').textContent = cliente;
    document.getElementById('detalleBody').innerHTML =
        '<div class="text-center py-4"><div class="spinner-border text-success"></div></div>';
    new bootstrap.Modal(document.getElementById('modalDetalle')).show();

    fetch(`${BASE_URL}/ventas/detalle?id=${id}`)
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
                    <td class="text-center"><span class="badge bg-success-soft text-success">${l.cantidad} ${escHtml(l.unidad)}</span></td>
                    <td class="text-end text-muted small">$${parseFloat(l.precio_unitario).toFixed(2)}</td>
                    <td class="text-end fw-semibold">$${sub.toFixed(2)}</td>
                </tr>`;
            }).join('');

            document.getElementById('detalleBody').innerHTML = `
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light" style="font-size:.8rem;">
                            <tr><th class="ps-3">#</th><th>Producto</th>
                            <th class="text-center">Cantidad</th>
                            <th class="text-end">Precio</th>
                            <th class="text-end">Subtotal</th></tr>
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
