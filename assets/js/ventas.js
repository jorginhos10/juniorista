// carrito: { id: { nombre, precio, unidad, stock, cantidad } }
const carrito = {};

let categoriaActiva = '';

// Modo cotización: no vende ni descuenta inventario; permite servicios y productos sin stock
let modoCotizacion = false;
let contadorServicios = 0;
// Cantidad máxima de una línea: sin límite en cotización y en servicios
const maxCantidad = item => (modoCotizacion || item.servicio) ? Infinity : item.stock;

// ── FILTROS ───────────────────────────────────────────

function filtrarProductos() {
    const q    = document.getElementById('buscarProducto').value.trim().toLowerCase();
    const cards = [...document.querySelectorAll('.pos-card')];
    const coinciden = new Set();

    cards.forEach(card => {
        const nombre   = card.dataset.nombre.toLowerCase();
        const codigo   = (card.dataset.codigo || '').toLowerCase();
        const cat      = (card.dataset.categoria || '').toLowerCase();
        const matchQ   = !q || nombre.includes(q) || codigo.includes(q);
        const matchCat = !categoriaActiva || cat === categoriaActiva.toLowerCase();
        if (matchQ && matchCat) coinciden.add(card.dataset.id);
    });

    // Al buscar, los productos relacionados con lo encontrado (hacen lo mismo) se muestran aparte, al final
    const relacionados = new Set();
    if (q && typeof RELACIONADOS !== 'undefined') {
        coinciden.forEach(id => (RELACIONADOS[id] || []).forEach(r => {
            if (!coinciden.has(String(r))) relacionados.add(String(r));
        }));
    }

    let hayRelacionados = false;
    cards.forEach(card => {
        const id    = card.dataset.id;
        const esRel = relacionados.has(id);
        card.style.display = coinciden.has(id) || esRel ? '' : 'none';
        card.style.order   = esRel ? 2 : 0;
        card.classList.toggle('pos-card-relacionado', esRel);
        if (esRel) hayRelacionados = true;
    });

    const sep = document.getElementById('posRelacionadosSep');
    if (sep) sep.classList.toggle('d-none', !hayRelacionados);
    document.getElementById('sinResultados').classList.toggle('d-none', coinciden.size > 0);
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

    if (!modoCotizacion && stock <= 0) {
        mostrarToastError('Producto sin stock (en modo cotización sí se puede agregar)');
        return;
    }
    if (carrito[id]) {
        if (carrito[id].cantidad >= maxCantidad(carrito[id])) {
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
    if (nueva > maxCantidad(carrito[id])) {
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
    } else if (nueva > maxCantidad(carrito[id])) {
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
                <span class="fw-semibold">${escHtml(item.nombre)}${item.servicio ? '<span class="pos-ci-servicio">SERVICIO</span>' : ''}</span>
                <span class="text-muted small">$${item.precio.toFixed(2)} / ${escHtml(item.unidad)}</span>
            </div>
            <div class="pos-ci-controles">
                <button class="pos-ci-btn" onclick="cambiarCantidad('${id}', -1)">
                    <i class="bi bi-dash"></i>
                </button>
                <input type="number" class="pos-ci-cant" value="${item.cantidad}" min="1" ${isFinite(maxCantidad(item)) ? `max="${item.stock}"` : ''} step="1"
                       inputmode="numeric" aria-label="Cantidad" title="Escribe la cantidad"
                       onfocus="this.select()"
                       onkeydown="if (event.key === 'Enter') { event.preventDefault(); this.blur(); }"
                       onchange="fijarCantidad('${id}', this)">
                <button class="pos-ci-btn" onclick="cambiarCantidad('${id}', 1)"
                        ${item.cantidad >= maxCantidad(item) ? 'disabled' : ''}>
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

// "Confirmar Venta" abre primero el pop up para elegir el método de pago
let metodoPago = '';
let totalACobrar = 0;
let fotosComprobante = [];   // data URL de las fotos tomadas al cobrar por transferencia
const MAX_FOTOS_COMPROBANTE = 5;

function pintarFotosComprobante() {
    document.getElementById('pagoFotosLista').innerHTML = fotosComprobante.map((f, i) => `
        <div class="pos-pago-foto">
            <img src="${f}" alt="Comprobante ${i + 1}">
            <button type="button" onclick="quitarFotoComprobante(${i})" title="Quitar foto" aria-label="Quitar foto ${i + 1}">&times;</button>
        </div>`).join('');
}

function quitarFotoComprobante(i) {
    fotosComprobante.splice(i, 1);
    pintarFotosComprobante();
}

async function agregarFotoComprobante(origen) {
    if (fotosComprobante.length >= MAX_FOTOS_COMPROBANTE) {
        mostrarToastError('Máximo ' + MAX_FOTOS_COMPROBANTE + ' fotos por venta');
        return;
    }
    const foto = await (origen === 'camara' ? Camara.tomar() : Camara.elegirArchivo());
    if (foto) { fotosComprobante.push(foto); pintarFotosComprobante(); }
}

function confirmarVenta() {
    if (!Object.keys(carrito).length) return;
    if (modoCotizacion) { cotizar(); return; }

    const subtotal  = Object.values(carrito).reduce((s, i) => s + i.precio * i.cantidad, 0);
    const descuento = Math.min(parseFloat(document.getElementById('descuentoInput').value) || 0, subtotal);
    totalACobrar = Math.round(Math.max(0, subtotal - descuento) * 100) / 100;
    document.getElementById('pagoTotal').textContent = '$' + totalACobrar.toFixed(2);

    document.querySelectorAll('.pos-pago-monto').forEach(i => { i.value = ''; });
    fotosComprobante = [];
    pintarFotosComprobante();
    // Al editar se propone el método que ya tenía la venta (en mixto hay que repartir de nuevo los montos)
    seleccionarMetodoPago(typeof VENTA_EDITAR !== 'undefined' && VENTA_EDITAR ? (VENTA_EDITAR.metodo_pago || '') : '');
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalMetodoPago')).show();
}

function seleccionarMetodoPago(metodo) {
    metodoPago = metodo;
    document.querySelectorAll('.pos-pago-btn').forEach(b => {
        const on = b.dataset.metodo === metodo;
        b.classList.toggle('activo', on);
        b.setAttribute('aria-checked', on ? 'true' : 'false');
    });
    document.getElementById('pagoMixto').classList.toggle('d-none', metodo !== 'mixto');
    document.getElementById('pagoFotos').classList.toggle('d-none', metodo !== 'transferencia' && metodo !== 'mixto');
    validarPago();
}

// Montos del pago mixto: { efectivo, transferencia, datafono } solo con los mayores que 0
function montosMixto() {
    const montos = {};
    document.querySelectorAll('.pos-pago-monto').forEach(i => {
        const v = Math.round((parseFloat(i.value) || 0) * 100) / 100;
        if (v > 0) montos[i.dataset.metodo] = v;
    });
    return montos;
}

// Habilita "Registrar venta": método elegido y, si es mixto, al menos dos montos que sumen el total
function validarPago() {
    const btn = document.getElementById('btnRegistrarVenta');
    if (metodoPago !== 'mixto') { btn.disabled = !metodoPago; return; }

    const montos = montosMixto();
    const suma   = Object.values(montos).reduce((s, v) => s + v, 0);
    const resto  = Math.round((totalACobrar - suma) * 100) / 100;
    const info   = document.getElementById('pagoMixtoResto');
    const dos    = Object.keys(montos).length >= 2;

    info.className = 'pos-pago-mixto-resto ' + (resto === 0 && dos ? 'ok' : 'falta');
    info.textContent = resto > 0 ? 'Falta por repartir: $' + resto.toFixed(2)
                     : resto < 0 ? 'Sobran $' + Math.abs(resto).toFixed(2) + ' respecto al total'
                     : dos ? 'Los montos suman el total.'
                     : 'Un pago mixto usa al menos dos métodos.';
    btn.disabled = !(resto === 0 && dos);
}

document.querySelectorAll('.pos-pago-btn').forEach(b => {
    b.addEventListener('click', () => {
        seleccionarMetodoPago(b.dataset.metodo);
        if (b.dataset.metodo === 'mixto') {
            document.getElementById('mixto-efectivo').focus();
        } else {
            document.getElementById('btnRegistrarVenta').focus();   // Enter registra la venta
        }
    });
});

document.querySelectorAll('.pos-pago-monto').forEach(inp => {
    inp.addEventListener('input', validarPago);
    // Al entrar a un campo vacío con un solo monto pendiente, propone lo que falta
    inp.addEventListener('focus', () => {
        if (inp.value !== '') { inp.select(); return; }
        const suma  = Object.values(montosMixto()).reduce((s, v) => s + v, 0);
        const resto = Math.round((totalACobrar - suma) * 100) / 100;
        if (suma > 0 && resto > 0) { inp.value = resto.toFixed(2); inp.select(); validarPago(); }
    });
    inp.addEventListener('keydown', e => {
        if (e.key === 'Enter') {
            e.preventDefault();
            const btn = document.getElementById('btnRegistrarVenta');
            if (!btn.disabled) registrarVenta();
        }
    });
});

// Registra la venta con el método elegido
function registrarVenta() {
    const items = Object.entries(carrito);
    if (!items.length || !metodoPago) return;

    const btn = document.getElementById('btnRegistrarVenta');
    if (btn.dataset.enviando) return;      // evita registrar dos veces con doble clic
    btn.dataset.enviando = '1';
    btn.disabled = true;

    const subtotal  = Object.values(carrito).reduce((s, i) => s + i.precio * i.cantidad, 0);
    const descuento = parseFloat(document.getElementById('descuentoInput').value) || 0;
    const total     = Math.max(0, subtotal - descuento);

    document.getElementById('fMetodoPago').value  = metodoPago;
    const montos = metodoPago === 'mixto' ? montosMixto() : {};
    document.getElementById('fPagoEfectivo').value      = montos.efectivo || '';
    document.getElementById('fPagoTransferencia').value = montos.transferencia || '';
    document.getElementById('fPagoDatafono').value      = montos.datafono || '';

    // Fecha: solo se envía si el usuario la cambió; si no, el servidor usa la hora del registro
    const inpFecha = document.getElementById('fechaVenta');
    document.getElementById('fFecha').value = inpFecha && inpFecha.value && inpFecha.value !== inpFecha.dataset.inicial
        ? inpFecha.value : '';

    // fClienteId ya fue llenado por el autocomplete
    document.getElementById('fNotas').value       = document.getElementById('notasVenta').value;
    document.getElementById('fDescuento').value   = descuento;

    const fLineas = document.getElementById('fLineas');
    fLineas.innerHTML = '';
    // Fotos del comprobante: solo si la venta lleva transferencia (en mixto, si hay monto por transferencia)
    const llevaTransferencia = metodoPago === 'transferencia' || (metodoPago === 'mixto' && montos.transferencia > 0);
    (llevaTransferencia ? fotosComprobante : []).forEach(foto => {
        const inp = document.createElement('input');
        inp.type = 'hidden'; inp.name = 'comprobante[]'; inp.value = foto;
        document.getElementById('formVentaOculto').appendChild(inp);
    });
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

// ── MODO COTIZACIÓN ───────────────────────────────────

function aplicarModoCotizacion(activo) {
    modoCotizacion = activo;
    document.body.classList.toggle('modo-cotizacion', activo);
    document.getElementById('btnServicio').classList.toggle('d-none', !activo);
    document.getElementById('btnConfirmarTexto').textContent = activo ? 'Cotizar' : 'Confirmar Venta';
    document.getElementById('tituloFactura').textContent = activo ? 'Cotización' : 'Factura';
    const btn = document.getElementById('btnConfirmar');
    btn.classList.toggle('btn-success', !activo);
    btn.classList.toggle('btn-primary', activo);
    btn.querySelector('i').className = (activo ? 'bi bi-file-earmark-text-fill' : 'bi bi-bag-check-fill') + ' me-2';

    if (!activo) {
        // Al volver a vender: fuera los servicios y lo que no alcanza el stock
        let quitados = 0;
        Object.entries(carrito).forEach(([id, item]) => {
            if (item.servicio || item.stock <= 0) { delete carrito[id]; quitados++; actualizarBadge(id); }
            else if (item.cantidad > item.stock) { item.cantidad = item.stock; quitados++; actualizarBadge(id); }
        });
        if (quitados) mostrarToastError('Se quitaron del carrito los servicios y lo que no tiene stock suficiente');
    }
    renderCarrito();
    try { localStorage.setItem('pc_modo_cotizacion', activo ? '1' : '0'); } catch (e) {}
}

function abrirServicio() {
    document.getElementById('formServicio').reset();
    document.getElementById('srvCantidad').value = 1;
    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalServicio'));
    modal.show();
    setTimeout(() => document.getElementById('srvTexto').focus(), 300);
}

const formServicio = document.getElementById('formServicio');
if (formServicio) {
    formServicio.addEventListener('submit', e => {
        e.preventDefault();
        const texto  = document.getElementById('srvTexto').value.trim();
        const precio = Math.round((parseFloat(document.getElementById('srvPrecio').value) || 0) * 100) / 100;
        const cant   = Math.max(1, parseInt(document.getElementById('srvCantidad').value, 10) || 1);
        if (!texto) return;
        carrito['srv-' + (++contadorServicios)] = { nombre: texto, precio, unidad: 'servicio', stock: Infinity, cantidad: cant, servicio: true };
        renderCarrito();
        bootstrap.Modal.getInstance(document.getElementById('modalServicio')).hide();
    });
}

// «Cotizar»: imprime el formato de cotización en otra pestaña (no registra venta ni toca el inventario)
function cotizar() {
    const items = Object.entries(carrito);
    if (!items.length) return;
    const subtotal  = items.reduce((s, [, i]) => s + i.precio * i.cantidad, 0);
    const inpDesc   = document.getElementById('descuentoInput');
    const descuento = inpDesc.disabled ? 0 : Math.min(parseFloat(inpDesc.value) || 0, subtotal);

    document.getElementById('cClienteId').value = document.getElementById('fClienteId').value;
    document.getElementById('cNotas').value     = document.getElementById('notasVenta').value;
    document.getElementById('cDescuento').value = descuento;
    const lineas = document.getElementById('cLineas');
    lineas.innerHTML = '';
    items.forEach(([id, item]) => {
        const campos = item.servicio
            ? { tipo: 'servicio', producto_id: '', descripcion: item.nombre, precio: item.precio }
            : { tipo: 'producto', producto_id: id, descripcion: '', precio: '' };
        campos.cantidad = item.cantidad;
        Object.entries(campos).forEach(([k, v]) => {
            const inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = k + '[]'; inp.value = v;
            lineas.appendChild(inp);
        });
    });
    document.getElementById('formCotizacion').submit();
}

const switchCotizacion = document.getElementById('modoCotizacion');
if (switchCotizacion) {
    switchCotizacion.addEventListener('change', () => aplicarModoCotizacion(switchCotizacion.checked));
    let guardado = '0';
    try { guardado = localStorage.getItem('pc_modo_cotizacion') || '0'; } catch (e) {}
    if (guardado === '1') { switchCotizacion.checked = true; aplicarModoCotizacion(true); }
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

// ── EDICIÓN DE UNA VENTA ──────────────────────────────
// Desde Facturación (?editar=ID): se cargan sus productos (con el precio con que se vendieron) y su cliente
if (typeof VENTA_EDITAR !== 'undefined' && VENTA_EDITAR) {
    VENTA_EDITAR.lineas.forEach(l => {
        const id = String(l.producto_id);
        carrito[id] = {
            nombre:   l.nombre,
            precio:   l.precio,
            unidad:   l.unidad,
            stock:    Math.max(parseInt(STOCK_MAP[id] ?? 0), l.cantidad),
            cantidad: l.cantidad,
        };
        actualizarBadge(id);
    });
    renderCarrito();

    if (VENTA_EDITAR.cliente_id) {
        document.getElementById('fClienteId').value = VENTA_EDITAR.cliente_id;
        document.getElementById('inputCliente').value = VENTA_EDITAR.cliente_nombre || '';
        document.getElementById('btnLimpiarCliente').style.display = '';
    }
}
