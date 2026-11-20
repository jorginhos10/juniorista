<?php
// Variables esperadas en scope: $veterinarias (array), $veterinaria_id (int)
// Se muestran solo las sucursales asignadas al usuario: con una sola queda fija; con varias puede cambiar entre ellas.
require_once ROOT . '/app/helpers/Permisos.php';
$_permitidas = Permisos::sucursalesPermitidas();
$_opciones   = array_values(array_filter($veterinarias, fn($v) => in_array((int)$v['id'], $_permitidas, true)));
$_nomVet     = '';
$_logoVet    = '';
foreach ($_opciones as $_v) {
    if ((int)$_v['id'] === (int)$veterinaria_id) { $_nomVet = $_v['nombre']; $_logoVet = $_v['logo'] ?? ''; break; }
}
// Logo de la sucursal (o el icono por defecto si no tiene)
$_logoImg = function (?string $logo, string $nombre): string {
    if (!empty($logo)) {
        return '<img class="vet-logo" src="' . BASE_URL . '/' . htmlspecialchars($logo) . '" alt="' . htmlspecialchars($nombre) . '">';
    }
    return '<i class="bi bi-building text-primary vet-logo-ico"></i>';
};
?>
<?php if (count($_opciones) > 1): ?>
<div class="dropdown vet-selector" id="vet-selector" data-current="<?= (int)$veterinaria_id ?>">
    <button class="btn vet-selector-btn d-flex align-items-center gap-2 w-100" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <?= $_logoImg($_logoVet, $_nomVet) ?>
        <span class="text-truncate fw-semibold flex-grow-1 text-start"><?= htmlspecialchars($_nomVet) ?></span>
        <i class="bi bi-chevron-down" style="font-size:.75rem;"></i>
    </button>
    <ul class="dropdown-menu w-100 shadow-sm">
        <?php foreach ($_opciones as $_v): ?>
        <li>
            <a class="dropdown-item d-flex align-items-center gap-2 vet-item <?= (int)$veterinaria_id === (int)$_v['id'] ? 'active' : '' ?>"
               href="#" data-id="<?= (int)$_v['id'] ?>" data-nombre="<?= htmlspecialchars($_v['nombre']) ?>">
                <?= $_logoImg($_v['logo'] ?? '', $_v['nombre']) ?>
                <span class="text-truncate"><?= htmlspecialchars($_v['nombre']) ?></span>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php else: ?>
<div class="vet-fija d-flex align-items-center gap-1 px-3 border fw-semibold text-truncate"
     style="background:#f0f4ff; border-color:#c7d4f5 !important; cursor:default;"
     title="Sucursal asignada — no modificable">
    <?= $_logoImg($_logoVet, $_nomVet) ?>
    <span class="text-truncate" style="color:#3730a3;"><?= htmlspecialchars($_nomVet) ?></span>
    <i class="bi bi-lock-fill ms-1" style="font-size:.7rem; color:#6b7280; flex-shrink:0;"></i>
</div>
<?php endif; ?>
