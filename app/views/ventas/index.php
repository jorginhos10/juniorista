<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetControl &mdash; Ventas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/ventas.css" rel="stylesheet">
</head>
<body class="dashboard-body">
<div class="app-layout">

    <?php require ROOT . '/app/views/layouts/sidebar.php'; ?>

    <div class="main-wrapper" style="overflow:hidden;">

        <!-- Topbar -->
        <header class="topbar">
            <div class="topbar-left">
                <h5 class="topbar-title mb-0"><?= $editar ? 'Editar venta' : 'Punto de Venta' ?></h5>
                <p class="topbar-date mb-0"><?= date('l, d \d\e F \d\e Y') ?></p>
            </div>
            <div class="topbar-right">
                <!-- Veterinaria -->
                <?php require ROOT . '/app/views/layouts/vet_selector.php'; ?>

                <?php if ($editar): ?>
                <a href="<?= BASE_URL ?>/facturacion" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-x-lg me-1"></i> Cancelar edición
                </a>
                <?php endif; ?>

                <!-- Historial -->
                <?php if ($cotizacion): ?>
                <a href="<?= BASE_URL ?>/ventas/cotizaciones" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-x-lg me-1"></i> Cancelar edición
                </a>
                <?php endif; ?>
                <!-- En modo cotización lleva al historial de cotizaciones -->
                <a href="<?= BASE_URL ?>/ventas/historial" class="btn btn-outline-secondary btn-sm" id="btnHistorial"
                   data-ventas="<?= BASE_URL ?>/ventas/historial" data-cotizaciones="<?= BASE_URL ?>/ventas/cotizaciones">
                    <i class="bi bi-clock-history me-1"></i> Historial
                </a>

                <div class="dropdown">
                    <button class="btn btn-link topbar-avatar-btn p-0" data-bs-toggle="dropdown">
                        <div class="avatar-md"><?= strtoupper(substr($usuario['nombre'], 0, 1)) ?></div>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                        <li><div class="dropdown-header">
                            <div class="fw-semibold"><?= htmlspecialchars($usuario['nombre']) ?></div>
                            <div class="text-muted small"><?= htmlspecialchars($usuario['email']) ?></div>
                        </div></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout"
                               onclick="return confirm('¿Cerrar sesión?')">
                            <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
                        </a></li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Flash toast -->
        <?php if (!empty($success) || !empty($error)): ?>
        <div class="pos-toast <?= !empty($success) ? 'pos-toast-ok' : 'pos-toast-err' ?>" id="posToast">
            <i class="bi bi-<?= !empty($success) ? 'check-circle-fill' : 'exclamation-triangle-fill' ?> me-2"></i>
            <?= htmlspecialchars($success ?: $error) ?>
        </div>
        <?php endif; ?>

        <!-- ── LAYOUT POS ─────────────────────────────── -->
        <div class="pos-layout">

            <!-- IZQUIERDA: Mosaico de productos -->
            <div class="pos-productos">

                <!-- Barra de búsqueda y filtros -->
                <div class="pos-search-bar d-flex align-items-center gap-3">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" class="form-control border-start-0" id="buscarProducto"
                            placeholder="Buscar producto por nombre o código…"
                            oninput="filtrarProductos()">
                    </div>
                    <?php if (!$editar): ?>
                    <!-- Modo cotización: interruptor tipo iOS -->
                    <label class="pos-switch" title="Cotizar sin vender: no descuenta inventario">
                        <input type="checkbox" id="modoCotizacion" role="switch">
                        <span class="pos-switch-pista"><span class="pos-switch-bola"></span></span>
                        <span class="pos-switch-texto">Modo cotización</span>
                    </label>
                    <?php endif; ?>
                </div>

                <!-- Filtros por categoría -->
                <div class="pos-categorias" id="categoriasContainer">
                    <button type="button" class="pos-cat-btn pos-servicio-btn d-none" id="btnServicio" onclick="abrirServicio()">
                        <i class="bi bi-plus-lg me-1"></i>Servicio
                    </button>
                    <button class="pos-cat-btn active" data-cat="" onclick="filtrarCategoria(this, '')">
                        Todos
                    </button>
                    <?php
                    $cats = array_unique(array_filter(array_column($productos, 'categoria')));
                    sort($cats);
                    foreach ($cats as $cat):
                    ?>
                    <button class="pos-cat-btn" data-cat="<?= htmlspecialchars($cat) ?>"
                            onclick="filtrarCategoria(this, '<?= htmlspecialchars(addslashes($cat)) ?>')">
                        <?= htmlspecialchars($cat) ?>
                    </button>
                    <?php endforeach; ?>
                </div>

                <!-- Grid de productos -->
                <div class="pos-grid" id="posGrid">
                    <?php foreach ($productos as $p): ?>
                    <?php $sinStock = (int)$p['stock'] <= 0; ?>
                    <div class="pos-card <?= $sinStock ? 'pos-card-sin-stock' : '' ?>"
                         data-id="<?= $p['id'] ?>"
                         data-nombre="<?= htmlspecialchars($p['nombre']) ?>"
                         data-codigo="<?= htmlspecialchars($p['codigo'] ?? '') ?>"
                         data-precio="<?= $p['precio_venta'] ?>"
                         data-stock="<?= $p['stock'] ?>"
                         data-unidad="<?= htmlspecialchars($p['unidad']) ?>"
                         data-categoria="<?= htmlspecialchars($p['categoria'] ?? '') ?>"
                         onclick="agregarAlCarrito(this)">

                        <div class="pos-card-stock <?= $sinStock ? 'sin-stock' : 'con-stock' ?>">
                            <?= $sinStock ? 'Sin stock' : $p['stock'] . ' ' . htmlspecialchars($p['unidad']) ?>
                        </div>

                        <?php if (!empty($p['foto'])): ?>
                        <div class="pos-card-foto">
                            <img src="<?= BASE_URL ?>/<?= htmlspecialchars($p['foto']) ?>" alt="" loading="lazy">
                        </div>
                        <?php else: ?>
                        <div class="pos-card-icono">
                            <i class="bi bi-box-seam-fill"></i>
                        </div>
                        <?php endif; ?>

                        <div class="pos-card-nombre"><?= htmlspecialchars($p['nombre']) ?></div>

                        <?php if ($p['codigo']): ?>
                        <div class="pos-card-codigo"><?= htmlspecialchars($p['codigo']) ?></div>
                        <?php endif; ?>

                        <div class="pos-card-precio">$<?= number_format((float)$p['precio_venta'], 2) ?></div>

                        <div class="pos-card-badge" id="badge-<?= $p['id'] ?>" style="display:none;">
                            <i class="bi bi-check-lg"></i> <span>0</span>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <div id="posRelacionadosSep" class="pos-relacionados-sep d-none">
                        <i class="bi bi-arrow-left-right me-1"></i> Relacionados <span>hacen lo mismo que lo que buscaste</span>
                    </div>

                    <div id="sinResultados" class="pos-sin-resultados d-none">
                        <i class="bi bi-search fs-2 d-block mb-2 opacity-25"></i>
                        Sin productos que coincidan.
                    </div>
                </div>

            </div>

            <!-- DERECHA: Panel de facturación -->
            <div class="pos-factura">

                <!-- Encabezado factura -->
                <div class="pos-factura-header">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="fw-bold fs-6" id="tituloFactura"><?= $editar ? 'Editando factura' : 'Factura' ?></span>
                        <!-- Fecha de la venta: por defecto ahora; se puede poner una fecha pasada -->
                        <input type="datetime-local" class="form-control form-control-sm text-muted" id="fechaVenta"
                               style="width:auto;" title="Fecha de la venta"
                               <?php $_fechaIni = $editar ? date('Y-m-d\TH:i', strtotime($editar['created_at'])) : date('Y-m-d\TH:i'); ?>
                               value="<?= $_fechaIni ?>" data-inicial="<?= $_fechaIni ?>"
                               max="<?= date('Y-m-d') ?>T23:59">
                    </div>

                    <!-- Cliente -->
                    <div class="mb-2 position-relative">
                        <label class="form-label fw-semibold small mb-1">Cliente</label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control form-control-sm" id="inputCliente"
                                   placeholder="Buscar cliente…" autocomplete="off">
                            <button type="button" class="btn btn-outline-secondary btn-sm px-2"
                                    id="btnLimpiarCliente" title="Cliente general" style="display:none;"
                                    onclick="limpiarCliente()">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                        <div id="clienteSugerencias"
                             class="position-absolute bg-white border rounded shadow-sm w-100"
                             style="z-index:1050;max-height:180px;overflow-y:auto;display:none;top:100%;left:0;">
                        </div>
                    </div>
                    <script>
                    const _clientes = <?= json_encode(array_map(fn($c) => ['id'=>$c['id'],'nombre'=>$c['nombre_completo']], $clientes)) ?>;
                    const _inpCliente   = document.getElementById('inputCliente');
                    const _sugerencias  = document.getElementById('clienteSugerencias');
                    const _btnLimpiar   = document.getElementById('btnLimpiarCliente');
                    // El campo oculto está en el formulario de más abajo: se busca al usarlo, no al cargar este script
                    const _fClienteId   = { set value(v) { document.getElementById('fClienteId').value = v; } };

                    function renderSugerencias(lista) {
                        _sugerencias.innerHTML = '';
                        const base = [{id:'',nombre:'— Cliente general —'}].concat(lista);
                        base.forEach(c => {
                            const div = document.createElement('div');
                            div.textContent = c.nombre;
                            div.className   = 'px-3 py-2 small cursor-pointer sugerencia-item';
                            div.style.cursor = 'pointer';
                            div.addEventListener('mousedown', e => {
                                e.preventDefault();
                                _fClienteId.value  = c.id;
                                _inpCliente.value  = c.id ? c.nombre : '';
                                _inpCliente.placeholder = c.id ? '' : 'Buscar cliente…';
                                _btnLimpiar.style.display = c.id ? '' : 'none';
                                _sugerencias.style.display = 'none';
                            });
                            div.addEventListener('mouseover', () => div.style.background = '#f0f4ff');
                            div.addEventListener('mouseout',  () => div.style.background = '');
                            _sugerencias.appendChild(div);
                        });
                        _sugerencias.style.display = 'block';
                    }

                    _inpCliente.addEventListener('input', function() {
                        const q = this.value.toLowerCase().trim();
                        renderSugerencias(q ? _clientes.filter(c => c.nombre.toLowerCase().includes(q)) : _clientes);
                    });

                    _inpCliente.addEventListener('focus', function() {
                        const q = this.value.toLowerCase().trim();
                        renderSugerencias(q ? _clientes.filter(c => c.nombre.toLowerCase().includes(q)) : _clientes);
                    });

                    _inpCliente.addEventListener('blur', () => {
                        setTimeout(() => { _sugerencias.style.display = 'none'; }, 150);
                    });

                    function limpiarCliente() {
                        _fClienteId.value = '';
                        _inpCliente.value = '';
                        _inpCliente.placeholder = 'Buscar cliente…';
                        _btnLimpiar.style.display = 'none';
                    }
                    </script>
                </div>

                <!-- Líneas del carrito -->
                <div class="pos-carrito" id="posCarrito">
                    <div class="pos-carrito-vacio" id="carritoVacio">
                        <i class="bi bi-bag"></i>
                        <span>Selecciona productos del catálogo</span>
                    </div>
                </div>

                <!-- Totales y confirmar -->
                <div class="pos-factura-footer">

                    <!-- Descuento -->
                    <?php if (!empty($descuentos)): ?>
                    <div class="d-flex align-items-center justify-content-between mb-2 gap-2">
                        <label class="small text-muted mb-0">Descuento ($)
                            <?php if (empty($esAdmin)): ?>
                            <span class="badge bg-success-subtle text-success ms-1" title="El administrador autorizó un descuento. Vale para una sola venta.">Autorizado · 1 venta</span>
                            <?php endif; ?>
                        </label>
                        <div class="input-group input-group-sm" style="width:130px;">
                            <span class="input-group-text">$</span>
                            <input type="text" class="form-control text-end" id="descuentoInput" inputmode="decimal" autocomplete="off"
                                title="Valor fijo (5000) o porcentaje (10%)" placeholder="0 ó 10%"
                                value="<?= $editar ? (float)$editar['descuento'] : 0 ?>" oninput="descuentoGlobalCambio()">
                        </div>
                    </div>
                    <?php else: ?>
                    <!-- Descuento desactivado: el campo se ve pero no deja escribir hasta que el administrador lo active desde la app -->
                    <div class="d-flex align-items-center justify-content-between mb-2 gap-2" title="Descuento desactivado. El administrador lo activa desde la app para una venta.">
                        <label class="small text-muted mb-0">Descuento ($)</label>
                        <div class="input-group input-group-sm" style="width:130px;">
                            <span class="input-group-text">$</span>
                            <input type="text" class="form-control text-end" id="descuentoInput" inputmode="decimal" autocomplete="off" data-bloqueado="1"
                                   placeholder="0 ó 10%" oninput="descuentoGlobalCambio()" value="<?= $editar ? (float)$editar['descuento'] : 0 ?>" disabled>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="pos-totales">
                        <div class="pos-total-row">
                            <span>Subtotal</span>
                            <span id="totalSubtotal">$0.00</span>
                        </div>
                        <div class="pos-total-row text-danger">
                            <span>Descuento<span id="totalDescuentoPct"></span></span>
                            <span id="totalDescuento">-$0.00</span>
                        </div>
                        <div class="pos-total-row pos-total-final">
                            <span>TOTAL</span>
                            <span id="totalFinal">$0.00</span>
                        </div>
                    </div>

                    <!-- Notas -->
                    <input type="text" class="form-control form-control-sm mb-3" id="notasVenta"
                        placeholder="Notas u observaciones…" value="<?= htmlspecialchars($editar['notas'] ?? '') ?>">

                    <!-- Botones -->
                    <button class="btn btn-success w-100 fw-bold py-2" id="btnConfirmar" onclick="confirmarVenta()" disabled>
                        <i class="bi bi-bag-check-fill me-2"></i> <span id="btnConfirmarTexto"><?= $editar ? 'Guardar cambios' : 'Confirmar Venta' ?></span>
                    </button>
                    <button class="btn btn-outline-danger btn-sm w-100 mt-2" onclick="limpiarCarrito()">
                        <i class="bi bi-trash me-1"></i> Limpiar carrito
                    </button>

                </div>

            </div>
        </div>

    </div>
</div>

<!-- ── FORM OCULTO PARA SUBMIT ───────────────────────── -->
<form method="POST" action="<?= BASE_URL ?>/ventas/registrar" id="formVentaOculto">
    <input type="hidden" name="veterinaria_id" value="<?= $veterinaria_id ?>">
    <input type="hidden" name="venta_id"       value="<?= $editar ? (int)$editar['id'] : '' ?>">
    <input type="hidden" name="cliente_id"     id="fClienteId">
    <input type="hidden" name="fecha"           id="fFecha">
    <input type="hidden" name="notas"           id="fNotas">
    <input type="hidden" name="metodo_pago"     id="fMetodoPago">
    <input type="hidden" name="pago_efectivo"      id="fPagoEfectivo">
    <input type="hidden" name="pago_transferencia" id="fPagoTransferencia">
    <input type="hidden" name="pago_datafono"      id="fPagoDatafono">
    <input type="hidden" name="descuento_global" id="fDescuento">
    <div id="fLineas"></div>
</form>


<!-- ── FORM DE LA COTIZACIÓN (se imprime en otra pestaña) ── -->
<form method="POST" action="<?= BASE_URL ?>/ventas/cotizacion" id="formCotizacion" target="_blank">
    <input type="hidden" name="cotizacion_id" id="cCotizacionId" value="<?= $cotizacion ? (int)$cotizacion['id'] : '' ?>">
    <input type="hidden" name="cliente_id" id="cClienteId">
    <input type="hidden" name="notas"      id="cNotas">
    <input type="hidden" name="descuento"  id="cDescuento">
    <input type="hidden" name="descuento_pct" id="cDescuentoPct">
    <div id="cLineas"></div>
</form>

<!-- ── MODAL SERVICIO (modo cotización) ───────────────── -->
<div class="modal fade" id="modalServicio" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 shadow" id="formServicio" autocomplete="off">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-tools text-success me-2"></i>Agregar servicio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="srvTexto">Descripción <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="srvTexto" maxlength="200" required
                           placeholder="Ej: Consulta general, baño y corte, cirugía de esterilización…">
                </div>
                <div class="row g-3">
                    <div class="col-7">
                        <label class="form-label fw-semibold" for="srvPrecio">Precio <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control text-end" id="srvPrecio" min="0" step="0.01" required inputmode="decimal">
                        </div>
                    </div>
                    <div class="col-5">
                        <label class="form-label fw-semibold" for="srvCantidad">Cantidad</label>
                        <input type="number" class="form-control text-center" id="srvCantidad" min="1" step="1" value="1">
                    </div>
                </div>
                <div class="form-text mt-2">Los servicios no tienen stock; solo aparecen en la cotización.</div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success fw-bold"><i class="bi bi-plus-lg me-1"></i>Agregar</button>
            </div>
        </form>
    </div>
</div>

<!-- ── MODAL MÉTODO DE PAGO (al confirmar la venta) ──── -->
<div class="modal fade" id="modalMetodoPago" tabindex="-1" aria-labelledby="modalMetodoPagoTitulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalMetodoPagoTitulo">
                    <i class="bi bi-wallet2 text-success me-2"></i>Método de pago
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="pos-pago-total">
                    <span>Total a cobrar</span>
                    <strong id="pagoTotal">$0.00</strong>
                </div>
                <div class="pos-pago-opciones" role="radiogroup" aria-label="Método de pago">
                    <button type="button" class="pos-pago-btn" data-metodo="efectivo" role="radio" aria-checked="false">
                        <i class="bi bi-cash-coin"></i><span>Efectivo</span>
                    </button>
                    <button type="button" class="pos-pago-btn" data-metodo="transferencia" role="radio" aria-checked="false">
                        <i class="bi bi-bank"></i><span>Transferencia</span>
                    </button>
                    <button type="button" class="pos-pago-btn" data-metodo="datafono" role="radio" aria-checked="false">
                        <i class="bi bi-credit-card-2-front"></i><span>Datáfono</span>
                    </button>
                    <button type="button" class="pos-pago-btn" data-metodo="mixto" role="radio" aria-checked="false">
                        <i class="bi bi-shuffle"></i><span>Mixto</span>
                    </button>
                </div>

                <!-- Pago mixto: cuánto se paga con cada método -->
                <div class="pos-pago-mixto d-none" id="pagoMixto">
                    <div class="pos-pago-mixto-titulo">¿Cuánto paga con cada método?</div>
                    <?php foreach (['efectivo' => 'Efectivo', 'transferencia' => 'Transferencia', 'datafono' => 'Datáfono'] as $_k => $_t): ?>
                    <div class="pos-pago-mixto-fila">
                        <label for="mixto-<?= $_k ?>"><?= $_t ?></label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control text-end pos-pago-monto" id="mixto-<?= $_k ?>" data-metodo="<?= $_k ?>"
                                   min="0" step="0.01" placeholder="0.00" inputmode="decimal">
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <div class="pos-pago-mixto-resto" id="pagoMixtoResto" aria-live="polite"></div>
                </div>

                <!-- Foto del comprobante: solo cuando hay pago por transferencia -->
                <div class="pos-pago-fotos d-none" id="pagoFotos">
                    <div class="pos-pago-mixto-titulo"><i class="bi bi-camera me-1"></i>Foto del comprobante de la transferencia</div>
                    <div class="pos-pago-fotos-lista" id="pagoFotosLista"></div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="agregarFotoComprobante('camara')">
                            <i class="bi bi-camera-fill me-1"></i>Tomar foto
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="agregarFotoComprobante('archivo')">
                            <i class="bi bi-image me-1"></i>Subir imagen
                        </button>
                    </div>
                    <div class="form-text">Opcional, hasta 5 fotos. El administrador las revisa.</div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success fw-bold px-4" id="btnRegistrarVenta" onclick="registrarVenta()" disabled>
                    <i class="bi bi-bag-check-fill me-2"></i><?= $editar ? 'Guardar cambios' : 'Registrar venta' ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ── MODAL DETALLE ─────────────────────────────────── -->
<div class="modal fade" id="modalDetalle" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-receipt-cutoff text-success me-2"></i>
                    Venta — <span id="detalleCliente"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detalleBody"></div>
            <div class="modal-footer border-0">
                <button class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
const STOCK_MAP = <?= json_encode(array_column($productos, 'stock', 'id')) ?>;
const RELACIONADOS = <?= json_encode((object)($relacionados ?? [])) ?>;
const BASE_URL  = '<?= BASE_URL ?>';
// Cotización que se está editando (null = ninguna): ventas.js la carga en modo cotización
const COTIZACION_EDITAR = <?= json_encode($cotizacion ? [
    'id'             => (int)$cotizacion['id'],
    'numero'         => $cotizacion['numero'],
    'cliente_id'     => (int)$cotizacion['cliente_id'],
    'cliente_nombre' => trim($cotizacion['cliente_nombre'] . ' ' . $cotizacion['cliente_apellido']),
    'descuento'      => $cotizacion['descuento_pct'] !== null ? rtrim(rtrim(number_format((float)$cotizacion['descuento_pct'], 2, '.', ''), '0'), '.') . '%' : (float)$cotizacion['descuento'],
    'notas'          => (string)$cotizacion['notas'],
    'lineas'         => array_map(fn($l) => ['producto_id' => $l['producto_id'] !== null ? (int)$l['producto_id'] : null, 'concepto' => $l['concepto'],
                                             'cantidad' => (int)$l['cantidad'], 'precio' => (float)$l['precio']], $cotizacion['lineas']),
] : null, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
// Venta que se está editando (null = venta nueva): ventas.js la carga en el carrito
const VENTA_EDITAR = <?= json_encode($editar ? [
    'cliente_id'     => (int)$editar['cliente_id'],
    'cliente_nombre' => $editar['cliente_nombre'],
    'metodo_pago'    => $editar['metodo_pago'],
    'lineas'         => $editar['lineas'],
] : null) ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/camara.js"></script>
<script src="<?= BASE_URL ?>/assets/js/ventas.js"></script>
</body>
</html>
