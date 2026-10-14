<?php
// Variables esperadas en scope: $veterinarias (array), $veterinaria_id (int)
// Se muestran solo las sucursales asignadas al usuario: con una sola queda fija; con varias puede cambiar entre ellas.
require_once ROOT . '/app/helpers/Permisos.php';
$_permitidas = Permisos::sucursalesPermitidas();
$_opciones   = array_values(array_filter($veterinarias, fn($v) => in_array((int)$v['id'], $_permitidas, true)));
$_nomVet     = '';
foreach ($_opciones as $_v) {
    if ((int)$_v['id'] === (int)$veterinaria_id) { $_nomVet = $_v['nombre']; break; }
}
?>
<?php if (count($_opciones) > 1): ?>
<div class="input-group vet-selector">
    <span class="input-group-text bg-white border-end-0">
        <i class="bi bi-building text-primary"></i>
    </span>
    <select id="vet-selector" class="form-select border-start-0 ps-0 fw-semibold"
            data-current="<?= (int)$veterinaria_id ?>">
        <?php foreach ($_opciones as $_v): ?>
        <option value="<?= (int)$_v['id'] ?>" <?= (int)$veterinaria_id === (int)$_v['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($_v['nombre']) ?>
        </option>
        <?php endforeach; ?>
    </select>
</div>
<?php else: ?>
<div class="vet-fija d-flex align-items-center gap-1 px-3 border fw-semibold text-truncate"
     style="background:#f0f4ff; border-color:#c7d4f5 !important; cursor:default;"
     title="Sucursal asignada — no modificable">
    <i class="bi bi-building text-primary" style="font-size:.85rem; flex-shrink:0;"></i>
    <span class="text-truncate" style="color:#3730a3;"><?= htmlspecialchars($_nomVet) ?></span>
    <i class="bi bi-lock-fill ms-1" style="font-size:.7rem; color:#6b7280; flex-shrink:0;"></i>
</div>
<?php endif; ?>
