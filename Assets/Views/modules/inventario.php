<h2>Inventario Actual</h2>
<table border="1" cellpadding="5" cellspacing="0">
    <thead>
        <tr>
            <th>ID</th>
            <th>Categoría</th>
            <th>Código</th>
            <th>Nombre</th>
            <th>Stock</th>
            <th>Descripción</th>
            <th>Precio Compra</th>
            <th>Precio Venta</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?= $item['idarticulo'] ?></td>
                <td><?= $item['categoria'] ?></td>
                <td><?= $item['codigo'] ?></td>
                <td><?= $item['nombre'] ?></td>
                <td><?= $item['stock'] ?></td>
                <td><?= $item['descripcion'] ?></td>
                <td>$<?= number_format($item['precio_compra'], 2) ?></td>
                <td>$<?= number_format($item['precio_venta'], 2) ?></td>
                <td><?= $item['condicion'] == 1 ? 'Activo' : 'Inactivo' ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
