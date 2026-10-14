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
                <h5 class="topbar-title mb-0">Punto de Venta</h5>
                <p class="topbar-date mb-0"><?= date('l, d \d\e F \d\e Y') ?></p>
            </div>
            <div class="topbar-right">
                <!-- Veterinaria -->
                <?php require ROOT . '/app/views/layouts/vet_selector.php'; ?>

                <!-- Historial -->
                <a href="<?= BASE_URL ?>/ventas/historial" class="btn btn-outline-secondary btn-sm">
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
                <div class="pos-search-bar">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" class="form-control border-start-0" id="buscarProducto"
                            placeholder="Buscar producto por nombre o código…"
                            oninput="filtrarProductos()">
                    </div>
                </div>

                <!-- Filtros por categoría -->
                <div class="pos-categorias" id="categoriasContainer">
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
                         onclick="<?= $sinStock ? '' : 'agregarAlCarrito(this)' ?>">

                        <div class="pos-card-stock <?= $sinStock ? 'sin-stock' : 'con-stock' ?>">
                            <?= $sinStock ? 'Sin stock' : $p['stock'] . ' ' . htmlspecialchars($p['unidad']) ?>
                        </div>

                        <div class="pos-card-icono">
                            <i class="bi bi-box-seam-fill"></i>
                        </div>

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
                        <span class="fw-bold fs-6">Factura</span>
                        <span class="text-muted small"><?= date('d/m/Y H:i') ?></span>
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
                    const _fClienteId   = document.getElementById('fClienteId');

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
                    <div class="d-flex align-items-center justify-content-between mb-2 gap-2">
                        <label class="small text-muted mb-0">Descuento ($)</label>
                        <div class="input-group input-group-sm" style="width:130px;">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control text-end" id="descuentoInput"
                                min="0" step="0.01" value="0" oninput="actualizarTotales()">
                        </div>
                    </div>

                    <div class="pos-totales">
                        <div class="pos-total-row">
                            <span>Subtotal</span>
                            <span id="totalSubtotal">$0.00</span>
                        </div>
                        <div class="pos-total-row text-danger">
                            <span>Descuento</span>
                            <span id="totalDescuento">-$0.00</span>
                        </div>
                        <div class="pos-total-row pos-total-final">
                            <span>TOTAL</span>
                            <span id="totalFinal">$0.00</span>
                        </div>
                    </div>

                    <!-- Notas -->
                    <input type="text" class="form-control form-control-sm mb-3" id="notasVenta"
                        placeholder="Notas u observaciones…">

                    <!-- Botones -->
                    <button class="btn btn-success w-100 fw-bold py-2" id="btnConfirmar" onclick="confirmarVenta()" disabled>
                        <i class="bi bi-bag-check-fill me-2"></i> Confirmar Venta
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
    <input type="hidden" name="cliente_id"     id="fClienteId">
    <input type="hidden" name="notas"           id="fNotas">
    <input type="hidden" name="metodo_pago"     id="fMetodoPago">
    <input type="hidden" name="pago_efectivo"      id="fPagoEfectivo">
    <input type="hidden" name="pago_transferencia" id="fPagoTransferencia">
    <input type="hidden" name="pago_datafono"      id="fPagoDatafono">
    <input type="hidden" name="descuento_global" id="fDescuento">
    <div id="fLineas"></div>
</form>


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
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success fw-bold px-4" id="btnRegistrarVenta" onclick="registrarVenta()" disabled>
                    <i class="bi bi-bag-check-fill me-2"></i>Registrar venta
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
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/ventas.js"></script>
</body>
</html>
