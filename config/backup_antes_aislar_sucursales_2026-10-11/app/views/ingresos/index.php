<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetControl &mdash; Ingresos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="dashboard-body">

<div class="app-layout">

    <?php require ROOT . '/app/views/layouts/sidebar.php'; ?>

    <div class="main-wrapper">

        <!-- Topbar -->
        <header class="topbar">
            <div class="topbar-left">
                <h5 class="topbar-title mb-0">Ingresos</h5>
                <p class="topbar-date mb-0">Entradas de productos al inventario</p>
            </div>
            <div class="topbar-right">
                <!-- Veterinaria -->
                <?php require ROOT . '/app/views/layouts/vet_selector.php'; ?>

                <button class="btn btn-primary-custom btn-sm" data-bs-toggle="modal" data-bs-target="#modalIngreso">
                    <i class="bi bi-box-arrow-in-down-right me-1"></i> Nuevo ingreso
                </button>

                <div class="dropdown">
                    <button class="btn btn-link topbar-avatar-btn p-0" data-bs-toggle="dropdown">
                        <div class="avatar-md"><?= strtoupper(substr($usuario['nombre'], 0, 1)) ?></div>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                        <li>
                            <div class="dropdown-header">
                                <div class="fw-semibold"><?= htmlspecialchars($usuario['nombre']) ?></div>
                                <div class="text-muted small"><?= htmlspecialchars($usuario['email']) ?></div>
                                <span class="badge badge-rol mt-1"><?= htmlspecialchars(ucfirst($usuario['rol'])) ?></span>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout" onclick="return confirm('¿Cerrar sesión?')">
                                <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="main-content">

            <!-- Flash -->
            <?php if (!empty($success)): ?>
            <div class="alert alert-success d-flex align-items-center gap-2 py-2 mb-3">
                <i class="bi bi-check-circle-fill flex-shrink-0"></i>
                <span><?= htmlspecialchars($success) ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2 py-2 mb-3">
                <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary-soft">
                            <i class="bi bi-receipt text-primary"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?= number_format($totales['total_ingresos'] ?? 0) ?></div>
                            <div class="stat-label">Ingresos registrados</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-success-soft">
                            <i class="bi bi-boxes text-success"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?= number_format($totales['total_unidades'] ?? 0) ?></div>
                            <div class="stat-label">Unidades ingresadas</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-warning-soft">
                            <i class="bi bi-currency-dollar text-warning"></i>
                        </div>
                        <div>
                            <div class="stat-value">$<?= number_format($totales['valor_total'] ?? 0, 2) ?></div>
                            <div class="stat-label">Valor total</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-info-soft">
                            <i class="bi bi-calendar-month text-info"></i>
                        </div>
                        <div>
                            <div class="stat-value">$<?= number_format($totales['valor_mes'] ?? 0, 2) ?></div>
                            <div class="stat-label">Este mes</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de ingresos -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 d-flex align-items-center justify-content-between pt-3 pb-2 gap-2 flex-wrap">
                    <h6 class="card-title mb-0 fw-semibold">
                        Historial de ingresos
                        <?php if ($veterinaria_id > 0): ?>
                        <span class="badge categoria-badge ms-1">
                            <?= htmlspecialchars(collect_vet_name($veterinarias, $veterinaria_id)) ?>
                        </span>
                        <?php endif; ?>
                    </h6>
                    <input type="text" class="form-control form-control-sm" id="buscador"
                        placeholder="Buscar proveedor, fecha…" style="width:210px;">
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="tablaIngresos">
                            <?php
                                require_once ROOT . '/app/models/VeterinariaModel.php';
                                $sucNombreIng = '';
                                foreach ($veterinarias as $_sv) {
                                    if ((int)$_sv['id'] === (int)$veterinaria_id) { $sucNombreIng = $_sv['nombre']; break; }
                                }
                            ?>
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Radicado</th>
                                    <th>Proveedor</th>
                                    <th class="text-center">Productos</th>
                                    <th class="text-center">Unidades</th>
                                    <th class="text-end">Total</th>
                                    <th>Notas</th>
                                    <th class="text-center">Fecha</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($ingresos)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-5">
                                        <i class="bi bi-inbox fs-2 d-block mb-2 opacity-25"></i>
                                        No hay ingresos registrados para esta veterinaria.
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($ingresos as $i => $ing): ?>
                                <?php
                                    $tipo = $ing['tipo'] ?? 'compra';
                                    $esSalida  = $tipo === 'transferencia_salida';
                                    $esEntrada = $tipo === 'transferencia_entrada';
                                    $esTransfer = $esSalida || $esEntrada;
                                    $rowBg = $esSalida ? 'style="background:#fff5f5;"'
                                           : ($esEntrada ? 'style="background:#f0fff4;"' : '');
                                ?>
                                <tr <?= $rowBg ?>>
                                    <td class="ps-3">
                                        <span style="font-family:monospace;font-size:.72rem;font-weight:700;
                                                     background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;
                                                     border-radius:.3rem;padding:.1rem .4rem;white-space:nowrap;">
                                            <?= VeterinariaModel::serialIngreso($sucNombreIng, $ing['id'], $ing['created_at']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($esTransfer): ?>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="ingreso-icon-prod <?= $esSalida ? 'bg-danger-soft' : 'bg-success-soft' ?>">
                                                <i class="bi bi-arrow-<?= $esSalida ? 'up-right text-danger' : 'down-left text-success' ?>"></i>
                                            </div>
                                            <div>
                                                <span class="fw-semibold <?= $esSalida ? 'text-danger' : 'text-success' ?>">
                                                    <?= $esSalida ? 'Transferencia enviada' : 'Transferencia recibida' ?>
                                                </span>
                                                <div class="text-muted" style="font-size:.72rem;">
                                                    <?= htmlspecialchars($ing['notas'] ?: '') ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php elseif ($ing['proveedor']): ?>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="ingreso-icon-prod">
                                                <i class="bi bi-truck-front-fill"></i>
                                            </div>
                                            <span class="fw-semibold"><?= htmlspecialchars($ing['proveedor']) ?></span>
                                        </div>
                                        <?php else: ?>
                                        <span class="text-muted small">Sin proveedor</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary-soft text-primary fw-semibold">
                                            <?= $ing['total_lineas'] ?> prod.
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge fw-semibold <?= $esSalida ? 'bg-danger-soft text-danger' : 'bg-success-soft text-success' ?>">
                                            <?= $esSalida ? '-' : '+' ?><?= number_format($ing['total_unidades'] ?? 0) ?> u.
                                        </span>
                                    </td>
                                    <td class="text-end fw-bold <?= $esSalida ? 'text-danger' : '' ?>">
                                        $<?= number_format($ing['total'], 2) ?>
                                    </td>
                                    <td class="text-muted small" style="max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                        <?= $esTransfer ? '—' : htmlspecialchars($ing['notas'] ?: '—') ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="text-muted small"><?= date('d/m/Y', strtotime($ing['created_at'])) ?></div>
                                        <div class="text-muted" style="font-size:.7rem;"><?= date('H:i', strtotime($ing['created_at'])) ?></div>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            <button class="btn btn-sm btn-outline-primary btn-accion"
                                                title="Ver detalle"
                                                onclick="verDetalle(<?= $ing['id'] ?>, '<?= $esTransfer ? ($esSalida ? 'Transferencia enviada' : 'Transferencia recibida') : htmlspecialchars(addslashes($ing['proveedor'] ?: 'Sin proveedor')) ?>')">
                                                <i class="bi bi-eye-fill"></i>
                                            </button>
                                            <?php if (!$esTransfer): ?>
                                            <button class="btn btn-sm btn-outline-secondary btn-accion"
                                                title="Editar"
                                                onclick="editarIngreso(<?= $ing['id'] ?>, '<?= htmlspecialchars(addslashes($ing['proveedor'] ?? '')) ?>', '<?= htmlspecialchars(addslashes($ing['notas'] ?? '')) ?>')">
                                                <i class="bi bi-pencil-fill"></i>
                                            </button>
                                            <a href="<?= BASE_URL ?>/ingresos/eliminar?id=<?= $ing['id'] ?>&vet=<?= $veterinaria_id ?>"
                                               class="btn btn-sm btn-outline-danger btn-accion"
                                               title="Eliminar (revierte stock)"
                                               onclick="return confirm('¿Eliminar este ingreso? Se revertirá el stock de todos sus productos.')">
                                                <i class="bi bi-trash-fill"></i>
                                            </a>
                                            <?php else: ?>
                                            <span class="badge <?= $esSalida ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' ?> border <?= $esSalida ? 'border-danger-subtle' : 'border-success-subtle' ?>" style="font-size:.68rem;">
                                                <i class="bi bi-arrow-<?= $esSalida ? 'up-right' : 'down-left' ?> me-1"></i>
                                                <?= $esSalida ? 'Salida' : 'Entrada' ?>
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php if (!empty($ingresos)): ?>
                <div class="card-footer bg-white border-0 text-muted small d-flex justify-content-between">
                    <span>Total de registros: <?= count($ingresos) ?></span>
                    <span class="fw-semibold text-body">
                        Valor total: $<?= number_format(array_sum(array_column($ingresos, 'total')), 2) ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>

        </main>
    </div>
</div>

<!-- ── MODAL NUEVO INGRESO ───────────────────────────── -->
<div class="modal fade" id="modalIngreso" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= BASE_URL ?>/ingresos/registrar" id="formIngreso">
                <input type="hidden" name="veterinaria_id" value="<?= $veterinaria_id ?>">
                <input type="hidden" name="ingreso_id" id="inp-ingreso-id" value="0">

                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="modalIngresoTitulo">
                        <i class="bi bi-box-arrow-in-down-right text-success me-2"></i>
                        Nuevo ingreso de productos
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body pt-2">

                    <!-- Cabecera del ingreso -->
                    <div class="row g-3 mb-3 pb-3 border-bottom">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Proveedor</label>
                            <div class="input-group position-relative">
                                <span class="input-group-text"><i class="bi bi-truck-front"></i></span>
                                <input type="text" class="form-control" id="inp-prov-txt"
                                       autocomplete="off" placeholder="Buscar proveedor…">
                                <input type="hidden" name="proveedor" id="inp-prov-val">
                                <div id="provSugerencias"
                                     style="position:absolute;top:100%;left:0;right:0;z-index:1060;
                                            background:#fff;border:1px solid #e8eaf0;border-radius:.5rem;
                                            box-shadow:0 8px 24px rgba(0,0,0,.1);
                                            max-height:200px;overflow-y:auto;display:none;">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Notas generales</label>
                            <input type="text" class="form-control" name="notas"
                                placeholder="Observaciones del ingreso, número de factura…">
                        </div>
                    </div>

                    <!-- Líneas de productos -->
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="fw-semibold small text-muted text-uppercase" style="letter-spacing:.06em;">
                            Productos del ingreso
                        </span>
                        <button type="button" class="btn btn-success btn-sm" onclick="agregarLinea()">
                            <i class="bi bi-plus-lg me-1"></i> Agregar producto
                        </button>
                    </div>

                    <!-- Cabecera fija de columnas -->
                    <div class="linea-header d-none d-md-grid">
                        <span>Producto</span>
                        <span class="text-center">Cantidad</span>
                        <span class="text-center">P. Unitario</span>
                        <span class="text-end">Subtotal</span>
                        <span></span>
                    </div>

                    <!-- Contenedor de líneas con scroll -->
                    <div id="lineasBody" class="lineas-scroll"></div>

                    <!-- Estado vacío -->
                    <div id="msgSinLineas" class="linea-empty">
                        <i class="bi bi-box-seam"></i>
                        <span>Haz clic en <strong>Agregar producto</strong> para comenzar.</span>
                    </div>

                    <!-- Total -->
                    <div class="linea-total" id="totalWrapper" style="display:none;">
                        <span>Total del ingreso</span>
                        <span class="linea-total-valor" id="totalGeneral">$0.00</span>
                    </div>

                </div>

                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-custom" id="btnGuardar" disabled>
                        <i class="bi bi-save me-1"></i> Guardar ingreso
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── MODAL DETALLE ─────────────────────────────────── -->
<div class="modal fade" id="modalDetalle" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-receipt text-primary me-2"></i>
                    <span id="detalleProveedor">Detalle del ingreso</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detalleBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Productos JSON para JS -->
<script>
const PRODUCTOS = <?= json_encode($productos) ?>;
const BASE_URL  = '<?= BASE_URL ?>';
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/autocomplete.js"></script>
<script src="<?= BASE_URL ?>/assets/js/ingresos.js"></script>
<script>
(function () {
    const _provs = <?= json_encode(array_map(fn($p) => [
        'id'     => $p['id'],
        'nombre' => $p['nombre'],
        'activo' => $p['activo'],
    ], array_filter($proveedores, fn($p) => $p['activo']))) ?>;

    const inpTxt  = document.getElementById('inp-prov-txt');
    const inpVal  = document.getElementById('inp-prov-val');
    const sugs    = document.getElementById('provSugerencias');

    function renderProvs(lista) {
        sugs.innerHTML = '';
        if (!lista.length) { sugs.style.display = 'none'; return; }
        lista.forEach(p => {
            const div = document.createElement('div');
            div.textContent = p.nombre;
            div.style.cssText = 'padding:.5rem 1rem;cursor:pointer;font-size:.83rem;border-bottom:1px solid #f3f4f6;';
            div.addEventListener('mouseover', () => div.style.background = '#fff7ed');
            div.addEventListener('mouseout',  () => div.style.background = '');
            div.addEventListener('mousedown', e => {
                e.preventDefault();
                inpTxt.value = p.nombre;
                inpVal.value = p.nombre;
                sugs.style.display = 'none';
            });
            sugs.appendChild(div);
        });
        sugs.style.display = 'block';
    }

    inpTxt.addEventListener('focus', function () {
        const q = this.value.toLowerCase();
        renderProvs(q ? _provs.filter(p => p.nombre.toLowerCase().includes(q)) : _provs);
    });

    inpTxt.addEventListener('input', function () {
        inpVal.value = this.value;
        const q = this.value.toLowerCase().trim();
        renderProvs(q ? _provs.filter(p => p.nombre.toLowerCase().includes(q)) : _provs);
    });

    inpTxt.addEventListener('blur', () => setTimeout(() => sugs.style.display = 'none', 150));

    // Limpiar al abrir modal nuevo ingreso
    document.getElementById('modalIngreso')?.addEventListener('show.bs.modal', function () {
        inpTxt.value = '';
        inpVal.value = '';
    });
}());
</script>
</body>
</html>

<?php
function collect_vet_name(array $vets, int $id): string {
    foreach ($vets as $v) {
        if ((int)$v['id'] === $id) return $v['nombre'];
    }
    return '';
}
?>
