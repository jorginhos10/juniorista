<?php
$jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE;
$e = fn($v) => htmlspecialchars((string)$v);
$fmt = fn($f) => $f ? date('d/m/Y H:i', strtotime($f)) : '—';
// Días de estancia: el día de ingreso cuenta como día 1
$dias = fn($h) => max(1, (int)(new DateTime(date('Y-m-d', strtotime($h['fecha_ingreso']))))->diff(new DateTime(date('Y-m-d', strtotime($h['fecha_salida'] ?: 'now'))))->days + 1);
$icono = fn($esp) => strtolower((string)$esp) === 'gato' ? '🐱' : '🐶';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetControl &mdash; Hospitalización</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .tablero { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1rem; }
        .paciente { background: #fff; border: 1px solid #e5e7eb; border-left: 4px solid #f59e0b; border-radius: .75rem; padding: 1rem; display: flex; flex-direction: column; gap: .6rem;
                    text-decoration: none; color: inherit; transition: box-shadow .15s, transform .15s; }
        .paciente:hover { box-shadow: 0 6px 20px rgba(15,23,42,.1); transform: translateY(-2px); color: inherit; }
        .paciente-ico { width: 44px; height: 44px; border-radius: 12px; background: #fef3c7; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0; }
        .paciente-dias { margin-left: auto; text-align: center; background: #fff7ed; color: #c2410c; border-radius: .6rem; padding: .15rem .6rem; line-height: 1.1; }
        .paciente-dias b { display: block; font-size: 1.15rem; }
        .paciente-dias small { font-size: .65rem; text-transform: uppercase; letter-spacing: .05em; }
        .paciente-dx { font-size: .85rem; color: #374151; }
        .paciente-meta { display: flex; flex-wrap: wrap; gap: .35rem; font-size: .75rem; }
        .chip { background: #f1f5f9; color: #475569; border-radius: 999px; padding: .1rem .55rem; }
        .chip.hoy { background: #dcfce7; color: #166534; font-weight: 600; }
        .chip.sin { background: #fee2e2; color: #991b1b; font-weight: 600; }
        .paciente-ultima { font-size: .78rem; color: #6b7280; border-top: 1px dashed #e5e7eb; padding-top: .5rem; }
    </style>
</head>
<body class="dashboard-body">

<div class="app-layout">

    <?php require ROOT . '/app/views/layouts/sidebar.php'; ?>

    <div class="main-wrapper">

        <header class="topbar">
            <div class="topbar-left">
                <h5 class="topbar-title mb-0">Hospitalización</h5>
                <p class="topbar-date mb-0"><?= count($internados) ?> paciente<?= count($internados) === 1 ? '' : 's' ?> internado<?= count($internados) === 1 ? '' : 's' ?> · hoja ECOP de medicación</p>
            </div>
            <div class="topbar-right">
                <?php require ROOT . '/app/views/layouts/vet_selector.php'; ?>
                <button class="btn btn-primary-custom btn-sm" onclick="nuevaHospitalizacion()">
                    <i class="bi bi-plus-lg me-1"></i> Hospitalizar paciente
                </button>
                <div class="dropdown">
                    <button class="btn btn-link topbar-avatar-btn p-0" data-bs-toggle="dropdown">
                        <div class="avatar-md"><?= strtoupper(substr($usuario['nombre'], 0, 1)) ?></div>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                        <li>
                            <div class="dropdown-header">
                                <div class="fw-semibold"><?= $e($usuario['nombre']) ?></div>
                                <div class="text-muted small"><?= $e($usuario['email']) ?></div>
                                <span class="badge badge-rol mt-1"><?= $e(ucfirst($usuario['rol'])) ?></span>
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
            <div class="alert alert-success py-2 mb-3"><i class="bi bi-check-circle-fill me-2"></i><?= $e($success) ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2 mb-3"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= $e($error) ?></div>
            <?php endif; ?>

            <!-- Tablero de internados -->
            <?php if ($internados): ?>
            <div class="tablero mb-4">
                <?php foreach ($internados as $h): [$ult_med, $ult_hora, $ult_dia] = array_pad(explode('|', (string)$h['ultima_aplicacion']), 3, ''); ?>
                <a class="paciente" href="<?= BASE_URL ?>/hospitalizacion/ver?id=<?= (int)$h['id'] ?>">
                    <div class="d-flex align-items-center gap-2">
                        <span class="paciente-ico"><?= $icono($h['especie']) ?></span>
                        <div>
                            <div class="fw-bold fs-6"><?= $e($h['mascota_nombre']) ?></div>
                            <div class="small text-muted"><?= $e(trim($h['especie'] . ($h['raza'] ? ' · ' . $h['raza'] : ''))) ?></div>
                        </div>
                        <div class="paciente-dias"><b><?= $dias($h) ?></b><small>día</small></div>
                    </div>
                    <div class="paciente-dx"><i class="bi bi-clipboard2-pulse me-1 text-muted"></i><?= $e($h['diagnostico']) ?></div>
                    <div class="paciente-meta">
                        <span class="chip"><i class="bi bi-calendar-event me-1"></i>Ingreso <?= date('d/m H:i', strtotime($h['fecha_ingreso'])) ?></span>
                        <?php if ($h['cliente_nombre']): ?><span class="chip"><i class="bi bi-person me-1"></i><?= $e($h['cliente_nombre']) ?></span><?php endif; ?>
                        <span class="chip <?= (int)$h['aplicaciones_hoy'] > 0 ? 'hoy' : 'sin' ?>"><?= (int)$h['aplicaciones_hoy'] ?> aplicación<?= (int)$h['aplicaciones_hoy'] === 1 ? '' : 'es' ?> hoy</span>
                    </div>
                    <div class="paciente-ultima">
                        <?= $ult_med !== '' ? 'Última: <b>' . $e($ult_med) . '</b> · ' . $e($ult_dia) . ' ' . $e($ult_hora) : 'Aún sin medicación registrada' ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body text-center text-muted py-5">
                    <i class="bi bi-hospital fs-1 d-block mb-2 opacity-25"></i>
                    No hay pacientes internados.
                    <div class="mt-3"><button class="btn btn-primary-custom btn-sm" onclick="nuevaHospitalizacion()"><i class="bi bi-plus-lg me-1"></i>Hospitalizar paciente</button></div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Historial de egresos -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center gap-2 flex-wrap">
                    <h6 class="fw-bold mb-0">Egresos recientes <span class="text-muted fw-normal small">(<?= count($egresos) ?>)</span></h6>
                    <input type="text" class="form-control form-control-sm" id="buscador" placeholder="Buscar mascota o diagnóstico…" style="width:260px">
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="tabla">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Mascota</th>
                                    <th>Diagnóstico</th>
                                    <th>Ingreso</th>
                                    <th>Salida</th>
                                    <th class="text-center">Días</th>
                                    <th class="text-center">Aplicaciones</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center" style="width:100px">ECOP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($egresos as $h): [$etq, $color] = $estados[$h['estado']] ?? [$h['estado'], 'secondary']; ?>
                                <tr>
                                    <td class="ps-3 fw-semibold"><?= $icono($h['especie']) ?> <?= $e($h['mascota_nombre']) ?></td>
                                    <td class="small"><?= $e($h['diagnostico']) ?></td>
                                    <td class="small text-nowrap"><?= $fmt($h['fecha_ingreso']) ?></td>
                                    <td class="small text-nowrap"><?= $fmt($h['fecha_salida']) ?></td>
                                    <td class="text-center"><?= $dias($h) ?></td>
                                    <td class="text-center"><?= (int)$h['total_aplicaciones'] ?></td>
                                    <td class="text-center"><span class="badge bg-<?= $color ?>-subtle text-<?= $color ?>-emphasis"><?= $etq ?></span></td>
                                    <td class="text-center text-nowrap">
                                        <a class="btn btn-sm btn-outline-primary btn-accion" title="Ver hoja ECOP" href="<?= BASE_URL ?>/hospitalizacion/ver?id=<?= (int)$h['id'] ?>"><i class="bi bi-eye"></i></a>
                                        <a class="btn btn-sm btn-outline-secondary btn-accion" title="Imprimir ECOP" target="_blank" href="<?= BASE_URL ?>/hospitalizacion/imprimir?id=<?= (int)$h['id'] ?>"><i class="bi bi-printer"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <tr id="filaVacia" style="<?= $egresos ? 'display:none' : '' ?>">
                                    <td colspan="8" class="text-center text-muted py-4"><span id="textoVacio">Aún no hay egresos.</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- ── MODAL HOSPITALIZAR ────────────────────────────── -->
<div class="modal fade" id="modalHosp" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= BASE_URL ?>/hospitalizacion/guardar" autocomplete="off" id="formHosp">
                <input type="hidden" name="mascota_id" id="h-mascota_id" value="0">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-hospital text-primary me-2"></i>Hospitalizar paciente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Mascota <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="h-nombre" required list="h-mascotas" placeholder="Busca la mascota por nombre o dueño">
                            <datalist id="h-mascotas">
                                <?php foreach ($mascotas as $m): ?>
                                <option value="<?= $e($m['nombre']) ?><?= $m['cliente_nombre'] ? ' — ' . $e($m['cliente_nombre']) : '' ?>">
                                <?php endforeach; ?>
                            </datalist>
                            <div class="form-text" id="h-info">Elige una mascota registrada de la lista.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Diagnóstico / motivo <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="diagnostico" id="h-dx" maxlength="255" required placeholder="Ej: Gastroenteritis hemorrágica">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Fecha y hora de ingreso</label>
                            <input type="datetime-local" class="form-control" name="fecha_ingreso" value="<?= $ahora ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Peso (kg)</label>
                            <input type="number" class="form-control" name="peso" step="0.01" min="0" id="h-peso">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Veterinario responsable</label>
                            <?php $miVet = current(array_filter($veterinarios, fn($v) => (int)$v['id'] === (int)$_SESSION['usuario_id'])) ?: null; ?>
                            <input type="text" class="form-control" id="h-vet" list="h-vets" value="<?= $e($miVet['nombre'] ?? '') ?>" placeholder="Busca el veterinario">
                            <input type="hidden" name="veterinario_id" id="h-vet-id" value="<?= (int)($miVet['id'] ?? 0) ?>">
                            <datalist id="h-vets"><?php foreach ($veterinarios as $v): ?><option value="<?= $e($v['nombre']) ?>"><?php endforeach; ?></datalist>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Notas <span class="text-muted fw-normal">(opcional)</span></label>
                            <input type="text" class="form-control" name="notas" maxlength="255" placeholder="Jaula, dieta, cuidados especiales…">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-custom"><i class="bi bi-clipboard2-plus me-1"></i>Ingresar y abrir hoja ECOP</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const $id = id => document.getElementById(id);
const MASCOTAS = <?= json_encode(array_map(fn($m) => [
    'id' => (int)$m['id'], 'nombre' => $m['nombre'], 'cliente' => (string)($m['cliente_nombre'] ?? ''),
    'etiqueta' => $m['nombre'] . ($m['cliente_nombre'] ? ' — ' . $m['cliente_nombre'] : ''),
    'especie' => (string)$m['especie'], 'raza' => (string)($m['raza'] ?? ''),
], $mascotas), $jsonFlags) ?>;
const VETERINARIOS = <?= json_encode(array_map(fn($v) => ['id' => (int)$v['id'], 'nombre' => $v['nombre']], $veterinarios), $jsonFlags) ?>;
const INTERNADOS = <?= json_encode(array_map(fn($h) => (int)$h['mascota_id'], $internados)) ?>;

function nuevaHospitalizacion(mascota) {
    $id('formHosp').reset();
    $id('h-mascota_id').value = 0;
    $id('h-info').textContent = 'Elige una mascota registrada de la lista.';
    if (mascota) { $id('h-nombre').value = mascota.etiqueta; elegirMascota(); }
    bootstrap.Modal.getOrCreateInstance($id('modalHosp')).show();
    setTimeout(() => (mascota ? $id('h-dx') : $id('h-nombre')).focus(), 300);
}

// El campo guarda «Nombre — Dueño»; se enlaza a la mascota solo si coincide con una de la lista
function elegirMascota() {
    const t = $id('h-nombre').value.trim().toLowerCase();
    const mismoNombre = MASCOTAS.filter(x => x.nombre.toLowerCase() === t);
    const m = MASCOTAS.find(x => x.etiqueta.toLowerCase() === t) || (mismoNombre.length === 1 ? mismoNombre[0] : null);
    $id('h-mascota_id').value = m ? m.id : 0;
    $id('h-info').innerHTML = !m ? 'Elige una mascota registrada de la lista.'
        : INTERNADOS.includes(m.id) ? '<span class="text-danger">Esta mascota ya está internada.</span>'
        : `${m.especie}${m.raza ? ' · ' + m.raza : ''}${m.cliente ? ' · Dueño: ' + m.cliente : ''}`;
}
$id('h-nombre').addEventListener('input', elegirMascota);
$id('formHosp').addEventListener('submit', e => {
    if (!Number($id('h-mascota_id').value)) { e.preventDefault(); $id('h-info').innerHTML = '<span class="text-danger">Elige una mascota registrada de la lista.</span>'; $id('h-nombre').focus(); }
});

$id('h-vet').addEventListener('input', () => {
    const v = VETERINARIOS.find(x => x.nombre.toLowerCase() === $id('h-vet').value.trim().toLowerCase());
    $id('h-vet-id').value = v ? v.id : 0;
});

$id('buscador').addEventListener('input', function () {
    const q = this.value.toLowerCase();
    let n = 0;
    document.querySelectorAll('#tabla tbody tr:not(#filaVacia)').forEach(tr => {
        const ok = tr.textContent.toLowerCase().includes(q);
        tr.style.display = ok ? '' : 'none';
        if (ok) n++;
    });
    $id('filaVacia').style.display = n ? 'none' : '';
    $id('textoVacio').textContent = q ? 'Ningún egreso coincide con la búsqueda.' : 'Aún no hay egresos.';
});

// Llegó desde la historia médica con una mascota: abre el ingreso ya rellenado
const MASCOTA_INICIAL = <?= (int)$mascotaInicial ?>;
if (MASCOTA_INICIAL) {
    const m = MASCOTAS.find(x => x.id === MASCOTA_INICIAL);
    if (m) nuevaHospitalizacion(m);
}
</script>
</body>
</html>
