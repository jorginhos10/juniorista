<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetControl &mdash; Pérdidas</title>
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
                <h5 class="topbar-title mb-0">Pérdidas</h5>
                <p class="topbar-date mb-0">Descarga y salidas de productos del inventario</p>
            </div>
            <div class="topbar-right">
                <!-- Veterinaria -->
                <?php require ROOT . '/app/views/layouts/vet_selector.php'; ?>

                <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalPerdida">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Registrar pérdida
                </button>

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
                        <div class="stat-icon bg-danger-soft"><i class="bi bi-file-minus-fill text-danger"></i></div>
                        <div>
                            <div class="stat-value"><?= number_format($totales['total_perdidas'] ?? 0) ?></div>
                            <div class="stat-label">Registros totales</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-warning-soft"><i class="bi bi-boxes text-warning"></i></div>
                        <div>
                            <div class="stat-value"><?= number_format($totales['total_unidades'] ?? 0) ?></div>
                            <div class="stat-label">Unidades retiradas</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-secondary-soft"><i class="bi bi-currency-dollar text-secondary"></i></div>
                        <div>
                            <div class="stat-value">$<?= number_format($totales['valor_total'] ?? 0, 2) ?></div>
                            <div class="stat-label">Valor total retirado</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-info-soft"><i class="bi bi-calendar-month text-info"></i></div>
                        <div>
                            <div class="stat-value">$<?= number_format($totales['valor_mes'] ?? 0, 2) ?></div>
                            <div class="stat-label">Este mes</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 d-flex align-items-center justify-content-between pt-3 pb-2 gap-2 flex-wrap">
                    <h6 class="card-title mb-0 fw-semibold">Historial de pérdidas</h6>
                    <input type="text" class="form-control form-control-sm" id="buscador"
                        placeholder="Buscar motivo, responsable…" style="width:210px;">
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="tablaPerdidas">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">#</th>
                                    <th>Motivo</th>
                                    <th>Responsable</th>
                                    <th class="text-center">Productos</th>
                                    <th class="text-center">Unidades</th>
                                    <th class="text-end">Total</th>
                                    <th>Notas</th>
                                    <th class="text-center">Fecha</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($perdidas)): ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-5">
                                        <i class="bi bi-inbox fs-2 d-block mb-2 opacity-25"></i>
                                        No hay pérdidas registradas.
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($perdidas as $i => $p): ?>
                                <?php $m = $motivos[$p['motivo']] ?? $motivos['perdida']; ?>
                                <tr>
                                    <td class="ps-3 text-muted small"><?= $i + 1 ?></td>
                                    <td>
                                        <span class="badge bg-<?= $m['color'] ?>-soft text-<?= $m['color'] ?> d-inline-flex align-items-center gap-1 px-2 py-1">
                                            <i class="bi bi-<?= $m['icon'] ?>"></i>
                                            <?= $m['label'] ?>
                                        </span>
                                    </td>
                                    <td class="small"><?= htmlspecialchars($p['responsable'] ?: '—') ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-danger-soft text-danger fw-semibold">
                                            <?= $p['total_lineas'] ?> prod.
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-warning-soft text-warning fw-semibold">
                                            -<?= number_format($p['total_unidades'] ?? 0) ?> u.
                                        </span>
                                    </td>
                                    <td class="text-end fw-bold">$<?= number_format($p['total'], 2) ?></td>
                                    <td class="text-muted small" style="max-width:140px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                        <?= htmlspecialchars($p['notas'] ?: '—') ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="text-muted small"><?= date('d/m/Y', strtotime($p['created_at'])) ?></div>
                                        <div class="text-muted" style="font-size:.7rem;"><?= date('H:i', strtotime($p['created_at'])) ?></div>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            <button class="btn btn-sm btn-outline-primary btn-accion" title="Ver detalle"
                                                onclick="verDetalle(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($m['label'])) ?>')">
                                                <i class="bi bi-eye-fill"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-secondary btn-accion" title="Editar"
                                                onclick="editarPerdida(<?= $p['id'] ?>, '<?= $p['motivo'] ?>', '<?= htmlspecialchars(addslashes($p['responsable'] ?? '')) ?>', '<?= htmlspecialchars(addslashes($p['notas'] ?? '')) ?>')">
                                                <i class="bi bi-pencil-fill"></i>
                                            </button>
                                            <a href="<?= BASE_URL ?>/perdidas/eliminar?id=<?= $p['id'] ?>&vet=<?= $veterinaria_id ?>"
                                               class="btn btn-sm btn-outline-danger btn-accion" title="Eliminar (restaura stock)"
                                               onclick="return confirm('¿Eliminar? Se restaurará el stock de todos los productos.')">
                                                <i class="bi bi-trash-fill"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php if (!empty($perdidas)): ?>
                <div class="card-footer bg-white border-0 text-muted small d-flex justify-content-between">
                    <span>Total de registros: <?= count($perdidas) ?></span>
                    <span class="fw-semibold text-body">Valor total: $<?= number_format(array_sum(array_column($perdidas, 'total')), 2) ?></span>
                </div>
                <?php endif; ?>
            </div>

        </main>
    </div>
</div>

<!-- ── MODAL REGISTRAR / EDITAR PÉRDIDA ─────────────── -->
<div class="modal fade" id="modalPerdida" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= BASE_URL ?>/perdidas/registrar" id="formPerdida">
                <input type="hidden" name="veterinaria_id" value="<?= $veterinaria_id ?>">
                <input type="hidden" name="perdida_id" id="inp-perdida-id" value="0">

                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="modalPerdidaTitulo">
                        <i class="bi bi-box-arrow-up-right text-danger me-2"></i>
                        Registrar pérdida de productos
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body pt-2">

                    <!-- Cabecera -->
                    <div class="row g-3 mb-3 pb-3 border-bottom">

                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Motivo <span class="text-danger">*</span></label>
                            <select class="form-select" name="motivo" id="inp-motivo" required onchange="actualizarColorMotivo(this)">
                                <?php foreach ($motivos as $key => $m): ?>
                                <option value="<?= $key ?>">
                                    <?= $m['label'] ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Responsable</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control" name="responsable" id="inp-responsable"
                                    placeholder="Nombre del responsable…">
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Notas</label>
                            <input type="text" class="form-control" name="notas" id="inp-notas-cab"
                                placeholder="Observaciones del registro…">
                        </div>

                        <!-- Badge de motivo -->
                        <div class="col-12" id="motivoBadgeWrapper">
                            <div class="motivo-banner motivo-perdida" id="motivoBanner">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <span id="motivoBannerTexto">Los productos serán descontados del stock por Pérdida</span>
                            </div>
                        </div>

                    </div>

                    <!-- Líneas -->
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="fw-semibold small text-muted text-uppercase" style="letter-spacing:.06em;">
                            Productos a descontar
                        </span>
                        <button type="button" class="btn btn-danger btn-sm" onclick="agregarLineaP()">
                            <i class="bi bi-plus-lg me-1"></i> Agregar producto
                        </button>
                    </div>

                    <div class="linea-header d-none d-md-grid">
                        <span>Producto</span>
                        <span class="text-center">Cantidad</span>
                        <span class="text-center">P. Referencia</span>
                        <span class="text-end">Subtotal</span>
                        <span></span>
                    </div>

                    <div id="lineasBodyP" class="lineas-scroll"></div>

                    <div id="msgSinLineasP" class="linea-empty">
                        <i class="bi bi-box-seam"></i>
                        <span>Haz clic en <strong>Agregar producto</strong> para comenzar.</span>
                    </div>

                    <div class="linea-total linea-total-danger" id="totalWrapperP" style="display:none;">
                        <span>Total descontado</span>
                        <span class="linea-total-valor" id="totalGeneralP">$0.00</span>
                    </div>

                </div>

                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger" id="btnGuardarP" disabled>
                        <i class="bi bi-save me-1"></i> <span id="btnGuardarPTexto">Registrar pérdida</span>
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
                    <i class="bi bi-file-minus-fill text-danger me-2"></i>
                    Detalle — <span id="detalleMotivo"></span>
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
const PRODUCTOS_P = <?= json_encode($productos) ?>;
const MOTIVOS_P   = <?= json_encode($motivos) ?>;
const BASE_URL    = '<?= BASE_URL ?>';
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/autocomplete.js"></script>
<script src="<?= BASE_URL ?>/assets/js/perdidas.js"></script>
</body>
</html>
