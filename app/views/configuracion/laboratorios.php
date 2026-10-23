<?php
$money = fn($n) => '$' . number_format((float)$n, 2);
$jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetControl &mdash; Laboratorios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .lab-card { max-width: 860px; }
        .monto { font-variant-numeric: tabular-nums; font-weight: 700; white-space: nowrap; }
        .sigla { font-family: ui-monospace, Consolas, monospace; font-weight: 700; color: #7c3aed; background: #f5f3ff; padding: .1rem .5rem; border-radius: 6px; }
    </style>
</head>
<body class="dashboard-body">

<div class="app-layout">

    <?php require ROOT . '/app/views/layouts/sidebar.php'; ?>

    <div class="main-wrapper">

        <header class="topbar">
            <div class="topbar-left">
                <h5 class="topbar-title mb-0">Laboratorios</h5>
                <p class="topbar-date mb-0">
                    <a href="<?= BASE_URL ?>/configuracion" class="text-decoration-none text-muted">Configuración</a>
                    <i class="bi bi-chevron-right mx-1" style="font-size:.7rem"></i> Exámenes y su costo
                </p>
            </div>
            <div class="topbar-right">
                <?php require ROOT . '/app/views/layouts/vet_selector.php'; ?>
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

            <div class="card border-0 shadow-sm lab-card">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap mb-1">
                        <h6 class="fw-bold mb-0">Exámenes de laboratorio</h6>
                        <?php if ($esAdmin): ?>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-primary-custom btn-sm" onclick="nuevoExamen(false)">
                                <i class="bi bi-plus-lg me-1"></i>Agregar examen
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="nuevoExamen(true)">
                                <i class="bi bi-collection me-1"></i>Agregar combo
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                    <p class="text-muted small mb-3">
                        Sucursal: <strong><?= htmlspecialchars($sucursal['nombre'] ?? '') ?></strong>.
                        El <strong>costo</strong> es lo que te cobra el laboratorio por cada examen. Al registrar un laboratorio,
                        el doctor elige los exámenes y fija el valor libremente, pero <strong>nunca por debajo de la suma de sus costos</strong>;
                        la diferencia es la ganancia. Un <strong>combo (perfil)</strong> tiene su propio costo, que puede ser menor que la suma de sus exámenes.
                    </p>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Examen</th>
                                    <th style="width:110px">Sigla</th>
                                    <th>Tipo de muestra</th>
                                    <th style="width:140px">Tiempo de entrega</th>
                                    <th class="text-end" style="width:120px">Valor</th>
                                    <?php if ($esAdmin): ?><th class="text-center" style="width:100px">Acciones</th><?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($examenes as $x): ?>
                                <tr>
                                    <td class="text-uppercase">
                                        <?= htmlspecialchars($x['nombre']) ?><?php if ($x['es_combo']): ?> <span class="badge bg-primary-subtle text-primary">Combo</span><?php endif; ?>
                                        <?php if ($x['incluye_texto']): ?><div class="small text-muted text-capitalize">Incluye: <?= htmlspecialchars($x['incluye_texto']) ?></div><?php endif; ?>
                                    </td>
                                    <td><span class="sigla"><?= htmlspecialchars($x['codigo']) ?></span></td>
                                    <td class="small"><?= htmlspecialchars($x['tipo_muestra'] ?? '') ?: '<span class="text-muted">—</span>' ?></td>
                                    <td class="small"><?= htmlspecialchars($x['tiempo_entrega'] ?? '') ?></td>
                                    <td class="text-end monto"><?= $money($x['costo']) ?></td>
                                    <?php if ($esAdmin): ?>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            <button class="btn btn-sm btn-outline-primary btn-accion" title="Editar"
                                                    onclick='editarExamen(<?= json_encode($x, $jsonFlags) ?>)'><i class="bi bi-pencil-fill"></i></button>
                                            <button class="btn btn-sm btn-outline-danger btn-accion" title="Quitar"
                                                    onclick='quitarExamen(<?= (int)$x['id'] ?>, <?= json_encode($x['codigo'], $jsonFlags) ?>)'><i class="bi bi-trash-fill"></i></button>
                                        </div>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (!$examenes): ?>
                                <tr>
                                    <td colspan="<?= $esAdmin ? 6 : 5 ?>" class="text-center text-muted py-5">
                                        <i class="bi bi-eyedropper fs-2 d-block mb-2 opacity-25"></i>
                                        Aún no hay exámenes en el catálogo<?= $esAdmin ? '. Agrega el primero con su costo.' : '.' ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="alert alert-info py-2 small mt-3 mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        El costo se guarda en cada examen al registrarlo: si lo cambias aquí, los exámenes ya registrados conservan el costo con el que se hicieron.
                    </div>

                    <div class="d-flex gap-2">
                        <a href="<?= BASE_URL ?>/configuracion" class="btn btn-light">Volver</a>
                        <?php if (!$esAdmin): ?>
                        <span class="small text-muted align-self-center"><i class="bi bi-lock me-1"></i>Solo un administrador puede cambiar los costos.</span>
                        <?php endif; ?>
                        <a href="<?= BASE_URL ?>/laboratorio" class="btn btn-outline-secondary ms-auto"><i class="bi bi-eyedropper me-1"></i>Ir a Laboratorios</a>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<?php if ($esAdmin): ?>
<!-- Agregar / editar examen -->
<div class="modal fade" id="modalExamen" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= BASE_URL ?>/configuracion/laboratorios/guardar" autocomplete="off">
                <input type="hidden" name="id" id="x-id" value="0">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-eyedropper text-primary me-2"></i><span id="x-titulo">Agregar examen</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="row g-3">
                        <div class="col-4">
                            <label class="form-label fw-semibold" for="x-codigo">Sigla <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-uppercase" name="codigo" id="x-codigo" maxlength="30" required placeholder="HH">
                        </div>
                        <div class="col-8">
                            <label class="form-label fw-semibold" for="x-nombre">Examen <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nombre" id="x-nombre" maxlength="100" required placeholder="Hemograma">
                        </div>
                        <div class="col-12" id="x-combo-box">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="es_combo" value="1" id="x-es-combo">
                                <label class="form-check-label fw-semibold" for="x-es-combo">Es un combo (perfil)</label>
                            </div>
                            <div id="x-incluye" class="border rounded-3 p-2" style="max-height:170px;overflow-y:auto">
                                <div class="small text-muted mb-1">Exámenes del catálogo que incluye:</div>
                                <?php foreach ($examenes as $x): if ($x['es_combo']) continue; ?>
                                <label class="d-block small"><input type="checkbox" class="form-check-input me-2 js-incluye" name="incluye[]" value="<?= (int)$x['id'] ?>">
                                    <strong><?= htmlspecialchars($x['codigo']) ?></strong> · <?= htmlspecialchars($x['nombre']) ?></label>
                                <?php endforeach; ?>
                                <?php if (!array_filter($examenes, fn($x) => !$x['es_combo'])): ?>
                                <div class="small text-muted">Aún no hay exámenes sueltos en el catálogo.</div>
                                <?php endif; ?>
                            </div>
                            <label class="form-label small fw-semibold mt-2 mb-1" for="x-incluye_extra">Otros exámenes que incluye <span class="text-muted fw-normal">(escritos, separados por coma)</span></label>
                            <input type="text" class="form-control form-control-sm" name="incluye_extra" id="x-incluye_extra" maxlength="255" placeholder="Urea, BUN, Creatinina, ALT, AST">
                            <div class="form-text">Mínimo dos exámenes entre el catálogo y los escritos.</div>
                        </div>
                        <div class="col-7">
                            <label class="form-label fw-semibold" for="x-tipo_muestra">Tipo de muestra</label>
                            <input type="text" class="form-control" name="tipo_muestra" id="x-tipo_muestra" maxlength="100" list="muestras" placeholder="Tubo tapa lila (EDTA)">
                            <datalist id="muestras">
                                <option value="Tubo tapa lila (EDTA)"><option value="Tubo tapa azul (citrato)"><option value="Tubo seco">
                                <option value="Heces"><option value="Orina"><option value="Pelos, costras y piel"><option value="Hisopado de oído">
                                <option value="Lisopado vaginal, recipiente estéril">
                            </datalist>
                        </div>
                        <div class="col-5">
                            <label class="form-label fw-semibold" for="x-tiempo_entrega">Tiempo de entrega</label>
                            <input type="text" class="form-control" name="tiempo_entrega" id="x-tiempo_entrega" maxlength="50" value="Mismo día" placeholder="Mismo día">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" for="x-costo">Valor (costo) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" name="costo" id="x-costo" step="0.01" min="0" required placeholder="0.00">
                            </div>
                            <div class="form-text" id="x-costo-ayuda">Lo que te cobra el laboratorio. El doctor no podrá cobrar menos que la suma de los costos.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-custom"><i class="bi bi-save me-1"></i>Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<form method="POST" action="<?= BASE_URL ?>/configuracion/laboratorios/eliminar" id="formQuitar" class="d-none">
    <input type="hidden" name="id">
</form>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if ($esAdmin): ?>
<script>
const $id = id => document.getElementById(id);
const modalExamen = () => bootstrap.Modal.getOrCreateInstance($id('modalExamen'));

function ponerCombo(si, incluye) {
    $id('x-es-combo').checked = si;
    $id('x-incluye').style.display = si ? '' : 'none';
    document.querySelectorAll('.js-incluye').forEach(c => c.checked = si && incluye.includes(Number(c.value)));
    if (!si) $id('x-incluye_extra').value = '';
    $id('x-costo-ayuda').textContent = si ? 'Costo propio del combo (puede ser menor que la suma de sus exámenes).'
        : 'Lo que te cobra el laboratorio. El doctor no podrá cobrar menos que la suma de los costos.';
}
$id('x-es-combo').addEventListener('change', e => ponerCombo(e.target.checked, []));

function nuevoExamen(combo) {
    $id('x-id').value = 0;
    ['codigo', 'nombre', 'costo', 'tipo_muestra'].forEach(c => $id('x-' + c).value = '');
    $id('x-tiempo_entrega').value = 'Mismo día';
    $id('x-incluye_extra').value = '';
    ponerCombo(!!combo, []);
    $id('x-titulo').textContent = combo ? 'Agregar combo' : 'Agregar examen';
    modalExamen().show();
    setTimeout(() => $id('x-codigo').focus(), 300);
}

function editarExamen(x) {
    $id('x-id').value = x.id;
    ['codigo', 'nombre', 'costo', 'tipo_muestra', 'tiempo_entrega'].forEach(c => $id('x-' + c).value = x[c] ?? '');
    ponerCombo(!!x.es_combo, x.incluye_ids || []);
    $id('x-incluye_extra').value = x.incluye_extra || '';
    $id('x-titulo').textContent = x.es_combo ? 'Editar combo' : 'Editar examen';
    modalExamen().show();
}

function quitarExamen(id, codigo) {
    if (!confirm('¿Quitar «' + codigo + '» del catálogo? Los exámenes ya registrados conservan su costo.')) return;
    const f = $id('formQuitar');
    f.id.value = id;
    f.submit();
}
</script>
<?php endif; ?>
</body>
</html>
