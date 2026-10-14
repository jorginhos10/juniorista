<?php
// Tratamiento: listado completo (no por día) con pestañas. El dinero se registra por ABONOS.
$money = fn($n) => '$' . number_format((float)$n, 2);
$R     = BASE_URL . '/' . $modulo['ruta'];

$pestanas = [
    'pendientes' => ['label' => 'Pendientes', 'icon' => 'hourglass-split', 'color' => 'warning'],
    'completos'  => ['label' => 'Completos',  'icon' => 'check2-circle',   'color' => 'success'],
    'cancelados' => ['label' => 'Cancelados', 'icon' => 'x-circle',        'color' => 'secondary'],
];
$conteo = array_fill_keys(array_keys($pestanas), 0);
foreach ($tratamientos as $t) {
    $conteo[$t['situacion']]++;
}
$saldoPendiente = array_sum(array_map(fn($t) => $t['situacion'] === 'pendientes' ? $t['saldo'] : 0, $tratamientos));
$jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetControl &mdash; Tratamientos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .tabs-trat { display: flex; gap: .25rem; border-bottom: 1px solid #e2e8f0; flex-wrap: wrap; }
        .tab-trat {
            border: none; background: none; padding: .65rem 1rem; font-weight: 600; font-size: .9rem;
            color: #64748b; border-bottom: 2px solid transparent; margin-bottom: -1px;
            display: inline-flex; align-items: center; gap: .45rem;
        }
        .tab-trat:hover { color: #1e293b; }
        .tab-trat.activo { color: var(--primary); border-bottom-color: var(--primary); }
        .tab-trat .cuenta { font-size: .72rem; font-weight: 700; padding: .1rem .5rem; border-radius: 999px; background: #f1f5f9; color: #64748b; }
        .tab-trat.activo .cuenta { background: #ede9fe; color: var(--primary); }

        .especie-ico {
            width: 34px; height: 34px; border-radius: 9px; flex-shrink: 0;
            display: inline-flex; align-items: center; justify-content: center; font-size: 1.1rem;
        }
        .especie-perro { background: #fef3c7; }
        .especie-gato  { background: #ede9fe; }
        .monto { font-variant-numeric: tabular-nums; font-weight: 700; white-space: nowrap; }

        .pago-barra { height: 6px; border-radius: 3px; background: #f1f5f9; overflow: hidden; min-width: 90px; }
        .pago-barra > div { height: 100%; border-radius: 3px; background: #16a34a; }
        .pago-barra.parcial > div { background: #f59e0b; }
        .pago-barra.cancelada > div { background: #94a3b8; }

        .resumen-pago { display: grid; grid-template-columns: repeat(3, 1fr); gap: .5rem; text-align: center; }
        .resumen-pago > div { background: #f8fafc; border-radius: 10px; padding: .6rem; }
        .resumen-pago .lbl { font-size: .68rem; text-transform: uppercase; letter-spacing: .05em; color: #94a3b8; font-weight: 600; }
        .resumen-pago .val { font-weight: 700; font-size: 1.05rem; font-variant-numeric: tabular-nums; }
        .abonos-tabla td { vertical-align: middle; }
        .abono-nuevo { animation: resaltar 2.5s ease-out; }
        @keyframes resaltar { from { background: #dcfce7; } to { background: transparent; } }
        .comprobante { font-family: ui-monospace, Consolas, monospace; font-size: .75rem; color: #64748b; }
    </style>
</head>
<body class="dashboard-body">

<div class="app-layout">

    <?php require ROOT . '/app/views/layouts/sidebar.php'; ?>

    <div class="main-wrapper">

        <header class="topbar">
            <div class="topbar-left">
                <h5 class="topbar-title mb-0">Tratamientos</h5>
                <p class="topbar-date mb-0">Tratamientos y sus abonos</p>
            </div>
            <div class="topbar-right">
                <?php require ROOT . '/app/views/layouts/vet_selector.php'; ?>
                <button class="btn btn-primary-custom btn-sm" onclick="nuevoTratamiento()">
                    <i class="bi bi-plus-lg me-1"></i> Nuevo tratamiento
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

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-3 pb-0">
                    <div class="d-flex justify-content-between align-items-end gap-2 flex-wrap">
                        <div class="tabs-trat" id="tabs">
                            <?php foreach ($pestanas as $k => $p): ?>
                            <button type="button" class="tab-trat <?= $pestana === $k ? 'activo' : '' ?>" data-tab="<?= $k ?>">
                                <i class="bi bi-<?= $p['icon'] ?> text-<?= $p['color'] ?>"></i><?= $p['label'] ?>
                                <span class="cuenta"><?= $conteo[$k] ?></span>
                            </button>
                            <?php endforeach; ?>
                        </div>
                        <div class="d-flex align-items-center gap-3 pb-2">
                            <?php if ($saldoPendiente > 0): ?>
                            <span class="small text-muted">Por cobrar: <span class="monto text-warning"><?= $money($saldoPendiente) ?></span></span>
                            <?php endif; ?>
                            <input type="text" class="form-control form-control-sm" id="buscador" placeholder="Buscar mascota o tratamiento…" style="width:230px">
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="tabla">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Fecha</th>
                                    <th>Mascota</th>
                                    <th>Descripción</th>
                                    <th class="text-end">Valor</th>
                                    <th style="width:170px">Pagado</th>
                                    <th class="text-end">Saldo</th>
                                    <th class="text-center" style="width:140px">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tratamientos as $t):
                                    $pct = (float)$t['valor'] > 0 ? min(100, (float)$t['abonado'] / (float)$t['valor'] * 100) : 100;
                                    $barra = $t['situacion'] === 'cancelados' ? 'cancelada' : ($t['saldo'] > 0 ? 'parcial' : ''); ?>
                                <tr data-situacion="<?= $t['situacion'] ?>" data-saldo="<?= $t['saldo'] ?>" data-valor="<?= (float)$t['valor'] ?>" data-abonado="<?= (float)$t['abonado'] ?>">
                                    <td class="ps-3 small text-nowrap"><?= date('d/m/Y', strtotime($t['fecha'])) ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="especie-ico especie-<?= $t['especie'] ?>"><?= $t['especie'] === 'gato' ? '🐱' : '🐶' ?></span>
                                            <div>
                                                <div class="fw-semibold"><?= htmlspecialchars($t['nombre']) ?></div>
                                                <div class="text-muted small"><?= $especies[$t['especie']] ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($t['descripcion']) ?>
                                        <?php if ($t['situacion'] === 'cancelados'): ?>
                                        <div class="text-muted small">
                                            <i class="bi bi-x-circle me-1"></i>Cancelado el <?= date('d/m/Y', strtotime($t['fecha_cancelacion'])) ?>
                                            <?= $t['motivo_cancelacion'] ? '· ' . htmlspecialchars($t['motivo_cancelacion']) : '' ?>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end monto"><?= $money($t['valor']) ?></td>
                                    <td>
                                        <div class="small mb-1">
                                            <?= $money($t['abonado']) ?>
                                            <span class="text-muted">· <?= (int)$t['total_abonos'] ?> abono<?= $t['total_abonos'] == 1 ? '' : 's' ?></span>
                                        </div>
                                        <div class="pago-barra <?= $barra ?>"><div style="width:<?= round($pct, 1) ?>%"></div></div>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($t['situacion'] === 'completos'): ?>
                                        <span class="badge bg-success-soft text-success"><i class="bi bi-check2-circle me-1"></i>Pagado</span>
                                        <?php elseif ($t['situacion'] === 'cancelados'): ?>
                                        <span class="badge bg-secondary-soft text-secondary">Cancelado</span>
                                        <?php else: ?>
                                        <span class="monto text-warning"><?= $money($t['saldo']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            <button class="btn btn-sm btn-outline-success btn-accion" title="Ver abonos" onclick="verAbonos(<?= (int)$t['id'] ?>)">
                                                <i class="bi bi-search"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-primary btn-accion" title="Editar"
                                                    onclick='editar(<?= json_encode($t, $jsonFlags) ?>)'>
                                                <i class="bi bi-pencil-fill"></i>
                                            </button>
                                            <?php if ($t['situacion'] === 'cancelados'): ?>
                                            <button class="btn btn-sm btn-outline-secondary btn-accion" title="Reactivar"
                                                    onclick="reactivar(<?= (int)$t['id'] ?>)"><i class="bi bi-arrow-counterclockwise"></i></button>
                                            <?php elseif ($t['situacion'] === 'pendientes'): ?>
                                            <button class="btn btn-sm btn-outline-danger btn-accion" title="Cancelar tratamiento"
                                                    onclick='cancelar(<?= (int)$t['id'] ?>, <?= json_encode($t['nombre'] . ' · ' . $t['descripcion'], $jsonFlags) ?>)'>
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <tr id="filaVacia" style="display:none">
                                    <td colspan="7" class="text-center text-muted py-5">
                                        <i class="bi bi-capsule fs-2 d-block mb-2 opacity-25"></i>
                                        <span id="textoVacio"></span>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot id="pieTabla">
                                <tr class="fw-bold">
                                    <td colspan="3" class="ps-3 text-end">Total <span class="text-muted fw-normal small">(<span id="pieCantidad">0</span>)</span></td>
                                    <td class="text-end monto" id="pieValor"></td>
                                    <td class="monto text-success" id="pieAbonado"></td>
                                    <td class="text-end monto text-warning" id="pieSaldo"></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- ── MODAL NUEVO TRATAMIENTO ───────────────────────── -->
<div class="modal fade" id="modalNuevo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= $R ?>/guardar" autocomplete="off" id="formNuevo">
                <input type="hidden" name="id" value="0">
                <input type="hidden" name="tab" value="pendientes">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-capsule text-primary me-2"></i>Nuevo tratamiento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="row g-3">
                        <div class="col-7">
                            <label class="form-label fw-semibold">Mascota <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nombre" id="n-nombre" maxlength="100" required placeholder="Nombre de la mascota">
                        </div>
                        <div class="col-5">
                            <label class="form-label fw-semibold">Especie <span class="text-danger">*</span></label>
                            <select class="form-select" name="especie" required>
                                <?php foreach ($especies as $k => $v): ?>
                                <option value="<?= $k ?>"><?= $v ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Descripción <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="descripcion" maxlength="255" required placeholder="<?= htmlspecialchars($modulo['placeholder']) ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Valor total <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" name="valor" id="n-valor" step="0.01" min="0" required placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Fecha</label>
                            <input type="date" class="form-control" name="fecha" id="n-fecha" max="<?= $hoy ?>" value="<?= $hoy ?>" required>
                        </div>
                        <div class="col-12">
                            <div class="border rounded-3 p-3 bg-light">
                                <div class="fw-semibold small mb-2"><i class="bi bi-cash-coin text-success me-1"></i>Primer abono <span class="text-muted fw-normal">(opcional)</span></div>
                                <div class="row g-2">
                                    <div class="col-5">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control" name="abono_inicial" id="n-abono" step="0.01" min="0" value="0">
                                        </div>
                                    </div>
                                    <div class="col-7">
                                        <input type="text" class="form-control form-control-sm" name="nota_abono" maxlength="150" placeholder="Descripción del abono (ej: efectivo)">
                                    </div>
                                </div>
                                <div class="small mt-2" id="n-hint"><span class="text-muted">Lo que no se pague ahora queda como saldo para abonar después.</span></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-custom"><i class="bi bi-save me-1"></i>Registrar tratamiento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── MODAL EDITAR ──────────────────────────────────── -->
<div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= $R ?>/guardar" autocomplete="off">
                <input type="hidden" name="id" id="e-id">
                <input type="hidden" name="tab" class="js-tab">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i>Editar tratamiento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="row g-3">
                        <div class="col-7">
                            <label class="form-label fw-semibold">Mascota <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nombre" id="e-nombre" maxlength="100" required>
                        </div>
                        <div class="col-5">
                            <label class="form-label fw-semibold">Especie <span class="text-danger">*</span></label>
                            <select class="form-select" name="especie" id="e-especie" required>
                                <?php foreach ($especies as $k => $v): ?>
                                <option value="<?= $k ?>"><?= $v ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Descripción <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="descripcion" id="e-descripcion" maxlength="255" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Valor total <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" name="valor" id="e-valor" step="0.01" min="0" required>
                            </div>
                            <div class="form-text" id="e-hint"></div>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Fecha</label>
                            <input type="date" class="form-control" name="fecha" id="e-fecha" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-custom"><i class="bi bi-save me-1"></i>Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── MODAL ABONOS (lupa) ───────────────────────────── -->
<div class="modal fade" id="modalAbonos" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold"><i class="bi bi-receipt text-success me-2"></i>Abonos del tratamiento</h5>
                    <div class="text-muted small" id="ab-titulo"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="resumen-pago mb-2">
                    <div><div class="lbl">Valor total</div><div class="val" id="ab-valor"></div></div>
                    <div><div class="lbl">Abonado</div><div class="val text-success" id="ab-abonado"></div></div>
                    <div><div class="lbl">Saldo</div><div class="val text-warning" id="ab-saldo"></div></div>
                </div>
                <div class="pago-barra mb-3"><div id="ab-barra" style="width:0%"></div></div>

                <div class="table-responsive mb-3">
                    <table class="table table-sm abonos-tabla mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>N°</th>
                                <th>Fecha</th>
                                <th>Descripción</th>
                                <th>Recibió</th>
                                <th class="text-end">Monto</th>
                                <th class="text-center" style="width:80px"></th>
                            </tr>
                        </thead>
                        <tbody id="ab-filas"></tbody>
                    </table>
                </div>

                <!-- Agregar abono -->
                <form method="POST" action="<?= $R ?>/abonar" id="formAbono" autocomplete="off">
                    <input type="hidden" name="tratamiento_id" id="ab-id">
                    <input type="hidden" name="tab" class="js-tab">
                    <div class="border rounded-3 p-3" id="ab-form-box">
                        <div class="fw-semibold mb-2"><i class="bi bi-plus-circle text-success me-1"></i>Agregar abono</div>
                        <div class="row g-2">
                            <div class="col-md-3">
                                <label class="form-label small mb-1">Monto <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" name="monto" id="ab-monto" step="0.01" min="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-1">Fecha</label>
                                <input type="date" class="form-control form-control-sm" name="fecha" id="ab-fecha" max="<?= $hoy ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small mb-1">Descripción</label>
                                <input type="text" class="form-control form-control-sm" name="nota" id="ab-nota" maxlength="150" placeholder="Ej: segundo abono, pago en efectivo…">
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-light btn-sm py-0" id="ab-total" style="font-size:.75rem">Saldo completo</button>
                                <button type="button" class="btn btn-light btn-sm py-0" id="ab-mitad" style="font-size:.75rem">Mitad</button>
                            </div>
                            <div class="small" id="ab-hint"></div>
                        </div>
                        <div class="text-end mt-2">
                            <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-check2 me-1"></i>Registrar abono</button>
                        </div>
                    </div>
                    <div class="alert py-2 small mb-0" id="ab-aviso" style="display:none"></div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ── MODAL CANCELAR ────────────────────────────────── -->
<div class="modal fade" id="modalCancelar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= $R ?>/cancelar">
                <input type="hidden" name="id" id="c-id">
                <input type="hidden" name="tab" class="js-tab">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-x-circle text-danger me-2"></i>Cancelar tratamiento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2" id="c-texto"></p>
                    <p class="small text-muted">No recibirá más abonos. Lo que ya se abonó sigue contando como cobrado en los cierres.</p>
                    <label class="form-label small fw-semibold">Motivo</label>
                    <input type="text" class="form-control" name="motivo" maxlength="200" placeholder="Ej: el dueño no continuó el tratamiento">
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Volver</button>
                    <button type="submit" class="btn btn-danger">Cancelar tratamiento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<form method="POST" action="<?= $R ?>/reactivar" id="formReactivar" class="d-none">
    <input type="hidden" name="id">
    <input type="hidden" name="tab" class="js-tab">
</form>
<form method="POST" action="<?= $R ?>/abono/eliminar" id="formAnularAbono" class="d-none">
    <input type="hidden" name="id">
    <input type="hidden" name="tratamiento_id">
    <input type="hidden" name="tab" class="js-tab">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const R        = '<?= $R ?>';
const HOY      = '<?= $hoy ?>';
const ES_ADMIN = <?= $esAdmin ? 'true' : 'false' ?>;
const ABRIR    = <?= (int)$abrir ?>;
const $id      = id => document.getElementById(id);
const money    = n => '$' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const esc      = s => String(s ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch]));
const fmtFecha = f => f.split('-').reverse().join('/');
const modal    = id => bootstrap.Modal.getOrCreateInstance($id(id));
let pestana    = '<?= $pestana ?>';

// ── Pestañas + buscador ───────────────────────────────
const VACIO = { pendientes: 'No hay tratamientos con saldo pendiente.', completos: 'Aún no hay tratamientos pagados por completo.', cancelados: 'No hay tratamientos cancelados.' };

function filtrar() {
    const q = $id('buscador').value.toLowerCase();
    let n = 0, valor = 0, abonado = 0, saldo = 0;
    document.querySelectorAll('#tabla tbody tr[data-situacion]').forEach(tr => {
        const ok = tr.dataset.situacion === pestana && tr.textContent.toLowerCase().includes(q);
        tr.style.display = ok ? '' : 'none';
        if (ok) {
            n++; valor += +tr.dataset.valor; abonado += +tr.dataset.abonado;
            if (pestana === 'pendientes') saldo += +tr.dataset.saldo;
        }
    });
    $id('filaVacia').style.display = n ? 'none' : '';
    $id('textoVacio').textContent  = q ? 'Ningún tratamiento coincide con la búsqueda.' : VACIO[pestana];
    $id('pieTabla').style.display  = n ? '' : 'none';
    $id('pieCantidad').textContent = n + (n === 1 ? ' tratamiento' : ' tratamientos');
    $id('pieValor').textContent    = money(valor);
    $id('pieAbonado').textContent  = money(abonado);
    $id('pieSaldo').textContent    = pestana === 'pendientes' ? money(saldo) : '';
    document.querySelectorAll('.js-tab').forEach(i => i.value = pestana);
}

$id('tabs').addEventListener('click', e => {
    const b = e.target.closest('.tab-trat');
    if (!b) return;
    pestana = b.dataset.tab;
    document.querySelectorAll('.tab-trat').forEach(x => x.classList.toggle('activo', x === b));
    history.replaceState(null, '', R + '?tab=' + pestana);
    filtrar();
});
$id('buscador').addEventListener('input', filtrar);
filtrar();

// ── Nuevo / editar / cancelar ─────────────────────────
function nuevoTratamiento() {
    $id('formNuevo').reset();
    $id('n-fecha').value = HOY;
    abonoTocado = false;
    actualizarHintNuevo();
    modal('modalNuevo').show();
    setTimeout(() => $id('n-nombre').focus(), 300);
}

let abonoTocado = false;
function actualizarHintNuevo() {
    const v = parseFloat($id('n-valor').value) || 0, a = parseFloat($id('n-abono').value) || 0;
    $id('n-abono').max = v || '';
    $id('n-hint').innerHTML = !v ? '<span class="text-muted">Lo que no se pague ahora queda como saldo para abonar después.</span>'
        : a > v ? '<span class="text-danger"><i class="bi bi-exclamation-circle-fill me-1"></i>El abono no puede superar el valor total.</span>'
        : a === 0 ? `<span class="text-warning fw-semibold">Sin abono: queda un saldo de ${money(v)}.</span>`
        : v - a > 0 ? `<span class="text-warning fw-semibold">Queda un saldo de ${money(v - a)} para abonar después.</span>`
        : '<span class="text-success fw-semibold"><i class="bi bi-check2-circle me-1"></i>Se paga completo.</span>';
}
$id('n-valor').addEventListener('input', actualizarHintNuevo);
$id('n-abono').addEventListener('input', () => { abonoTocado = true; actualizarHintNuevo(); });

function editar(t) {
    ['id', 'nombre', 'especie', 'descripcion', 'valor', 'fecha'].forEach(c => $id('e-' + c).value = t[c] ?? '');
    $id('e-valor').min = t.abonado;
    $id('e-hint').textContent = Number(t.abonado) > 0 ? 'Ya abonado: ' + money(t.abonado) + ' (el valor no puede ser menor).' : '';
    modal('modalEditar').show();
}

function cancelar(id, texto) {
    $id('c-id').value = id;
    $id('c-texto').textContent = '¿Cancelar «' + texto + '»?';
    modal('modalCancelar').show();
}

function reactivar(id) {
    if (!confirm('¿Reactivar este tratamiento? Volverá a recibir abonos.')) return;
    const f = $id('formReactivar');
    f.id.value = id;
    f.submit();
}

// ── Lupa: abonos del tratamiento ──────────────────────
let saldoActual = 0;

async function verAbonos(id) {
    $id('ab-filas').innerHTML = '<tr><td colspan="6" class="text-muted small">Cargando…</td></tr>';
    modal('modalAbonos').show();
    try {
        const r = await fetch(R + '/abonos?id=' + id);
        const data = await r.json();
        if (!r.ok) throw new Error(data.error || 'Error');
        const t = data.tratamiento, cancelado = t.estado === 'cancelado';
        saldoActual = Number(t.saldo);
        const pct = Number(t.valor) > 0 ? Math.min(100, Number(t.abonado) / Number(t.valor) * 100) : 100;

        $id('ab-id').value = t.id;
        $id('ab-titulo').textContent = `${t.especie === 'gato' ? '🐱' : '🐶'} ${t.nombre} · ${t.descripcion} · desde el ${fmtFecha(t.fecha)}`;
        $id('ab-valor').textContent   = money(t.valor);
        $id('ab-abonado').textContent = money(t.abonado);
        $id('ab-saldo').textContent   = money(saldoActual);
        $id('ab-barra').style.width   = pct + '%';
        $id('ab-barra').parentElement.className = 'pago-barra mb-3 ' + (cancelado ? 'cancelada' : (saldoActual > 0 ? 'parcial' : ''));

        $id('ab-filas').innerHTML = data.abonos.length ? data.abonos.map(a => `
            <tr class="${a.id == data.nuevo ? 'abono-nuevo' : ''}">
                <td class="comprobante">ABO-${String(a.id).padStart(5, '0')}</td>
                <td class="text-nowrap">${fmtFecha(a.fecha)}</td>
                <td>${esc(a.nota || 'Abono')}</td>
                <td class="text-muted small">${esc(a.usuario_nombre || '—')}</td>
                <td class="text-end fw-semibold text-success">${money(a.monto)}</td>
                <td class="text-center text-nowrap">
                    <a href="${R}/abono/imprimir?id=${a.id}" target="_blank" class="btn btn-sm btn-outline-secondary py-0 px-2" title="Imprimir factura de abono">
                        <i class="bi bi-printer"></i>
                    </a>
                    ${ES_ADMIN ? `<button type="button" class="btn btn-sm btn-link text-danger py-0 px-1" title="Anular abono" onclick="anularAbono(${a.id}, ${t.id}, '${money(a.monto)}')"><i class="bi bi-x-circle"></i></button>` : ''}
                </td>
            </tr>`).join('') + `
            <tr class="fw-bold"><td colspan="4" class="text-end">Total abonado</td><td class="text-end text-success">${money(t.abonado)}</td><td></td></tr>`
            : '<tr><td colspan="6" class="text-center text-muted small py-3">Aún no tiene abonos.</td></tr>';

        // Formulario de nuevo abono: solo si hay saldo y no está cancelado
        const puedeAbonar = !cancelado && saldoActual > 0;
        $id('ab-form-box').style.display = puedeAbonar ? '' : 'none';
        const aviso = $id('ab-aviso');
        aviso.style.display = puedeAbonar ? 'none' : '';
        aviso.className = 'alert py-2 small mb-0 ' + (cancelado ? 'alert-secondary' : 'alert-success');
        aviso.innerHTML = cancelado
            ? '<i class="bi bi-x-circle me-1"></i>Tratamiento cancelado' + (t.motivo_cancelacion ? ': ' + esc(t.motivo_cancelacion) : '') + '. No recibe más abonos.'
            : '<i class="bi bi-check2-circle me-1"></i>Tratamiento pagado por completo.';

        $id('ab-monto').max   = saldoActual;
        $id('ab-monto').value = saldoActual > 0 ? saldoActual.toFixed(2) : '';
        $id('ab-fecha').min   = t.fecha;
        $id('ab-fecha').value = HOY;
        $id('ab-nota').value  = '';
        actualizarHintAbono();

        // Abono recién registrado: ofrecer imprimir su factura
        if (data.nuevo) {
            setTimeout(() => {
                if (confirm('Abono registrado. ¿Imprimir la factura de abono?')) window.open(R + '/abono/imprimir?id=' + data.nuevo, '_blank');
            }, 400);
        }
    } catch (e) {
        $id('ab-filas').innerHTML = `<tr><td colspan="6" class="text-danger small">${esc(e.message)}</td></tr>`;
    }
}

function actualizarHintAbono() {
    const m = parseFloat($id('ab-monto').value) || 0, s = saldoActual - m;
    $id('ab-hint').innerHTML = m > saldoActual + 0.001 ? '<span class="text-danger"><i class="bi bi-exclamation-circle-fill me-1"></i>Supera el saldo pendiente.</span>'
        : m <= 0 ? ''
        : s > 0.004 ? `<span class="text-muted">Después de este abono quedará un saldo de <strong>${money(s)}</strong>.</span>`
        : '<span class="text-success fw-semibold"><i class="bi bi-check2-circle me-1"></i>Con este abono queda pagado.</span>';
}
$id('ab-monto').addEventListener('input', actualizarHintAbono);
$id('ab-total').addEventListener('click', () => { $id('ab-monto').value = saldoActual.toFixed(2); actualizarHintAbono(); });
$id('ab-mitad').addEventListener('click', () => { $id('ab-monto').value = (Math.round(saldoActual * 50) / 100).toFixed(2); actualizarHintAbono(); });
$id('formAbono').addEventListener('submit', e => {
    if ((parseFloat($id('ab-monto').value) || 0) > saldoActual + 0.001) { e.preventDefault(); $id('ab-monto').focus(); }
});

function anularAbono(id, tratamientoId, monto) {
    if (!confirm('¿Anular el abono de ' + monto + '? Dejará de contar en los cierres y el saldo volverá a quedar pendiente.')) return;
    const f = $id('formAnularAbono');
    f.id.value = id;
    f.tratamiento_id.value = tratamientoId;
    f.submit();
}

// Tras registrar un abono o un tratamiento con abono, se reabre su detalle
if (ABRIR) verAbonos(ABRIR);
</script>
</body>
</html>
