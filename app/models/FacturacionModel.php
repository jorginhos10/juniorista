<?php

require_once ROOT . '/config/Database.php';

/** Vista unificada de todo lo cobrado: ventas, peluquería y laboratorios (recaudo directo) y abonos de tratamientos */
class FacturacionModel {

    public const TIPOS = [
        'venta'       => ['label' => 'Venta',             'icon' => 'bag-fill', 'color' => '#4f46e5', 'prefijo' => ''],
        'peluqueria'  => ['label' => 'Peluquería',        'icon' => 'scissors', 'color' => '#db2777', 'prefijo' => 'PEL'],
        'laboratorio' => ['label' => 'Laboratorio',       'icon' => 'eyedropper', 'color' => '#7c3aed', 'prefijo' => 'LAB'],
        'tratamiento' => ['label' => 'Abono tratamiento', 'icon' => 'capsule',  'color' => '#0891b2', 'prefijo' => 'ABO'],
    ];

    private mysqli $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Movimientos del período (incluye los anulados, marcados con anulada = 1).
     * Columnas: tipo, id, fecha, creado, detalle, cliente, mascota, especie, monto, anulada, usuario_nombre,
     *           ref_id (tratamiento del abono), nota (descripción del abono / servicio).
     */
    public function getMovimientos(int $veterinaria_id, string $desde, string $hasta, string $tipo = ''): array {
        $partes = [
            'venta' =>
                "SELECT 'venta' AS tipo, v.id, DATE(v.created_at) AS fecha, v.created_at AS creado,
                        COALESCE((SELECT GROUP_CONCAT(CONCAT(d.cantidad, ' × ', p.nombre) ORDER BY d.id SEPARATOR ', ')
                                    FROM venta_detalles d JOIN productos p ON p.id = d.producto_id
                                   WHERE d.venta_id = v.id), '') AS detalle,
                        CONCAT(c.nombre, ' ', c.apellido) AS cliente, NULL AS mascota, NULL AS especie,
                        v.total AS monto, v.estado = 'anulada' AS anulada, u.nombre AS usuario_nombre,
                        NULL AS ref_id, v.notas AS nota, 0 AS tratamiento_cancelado
                   FROM ventas v
              LEFT JOIN clientes c ON c.id = v.cliente_id
              LEFT JOIN usuarios u ON u.id = v.usuario_id
                  WHERE v.veterinaria_id = ? AND DATE(v.created_at) BETWEEN ? AND ?",
            // En peluquería, un registro desactivado (activo = 0) es un servicio anulado
            'peluqueria' =>
                "SELECT 'peluqueria', t.id, t.fecha, t.created_at, t.descripcion, NULL, t.nombre, t.especie,
                        t.valor, t.activo = 0, u.nombre, NULL, t.descripcion, 0
                   FROM peluquerias t LEFT JOIN usuarios u ON u.id = t.usuario_id
                  WHERE t.veterinaria_id = ? AND t.fecha BETWEEN ? AND ?",
            // Laboratorios: igual que peluquería (recaudo directo; desactivado = anulado)
            'laboratorio' =>
                "SELECT 'laboratorio', t.id, t.fecha, t.created_at, t.descripcion, NULL, t.nombre, t.especie,
                        t.valor, t.activo = 0, u.nombre, NULL, t.descripcion, 0
                   FROM laboratorios t LEFT JOIN usuarios u ON u.id = t.usuario_id
                  WHERE t.veterinaria_id = ? AND t.fecha BETWEEN ? AND ?",
            // Tratamientos: cada abono es un cobro en su propia fecha (no el valor del tratamiento).
            // Un abono eliminado deja de aparecer en los listados y reportes (no se muestra como anulado).
            'tratamiento' =>
                "SELECT 'tratamiento', a.id, a.fecha, a.created_at,
                        CONCAT(t.descripcion, ' · abono', IF(a.nota IS NULL, '', CONCAT(' (', a.nota, ')')),
                               ' · tratamiento del ', DATE_FORMAT(t.fecha, '%d/%m/%Y'), ' por $', FORMAT(t.valor, 2)),
                        NULL, t.nombre, t.especie, a.monto, a.activo = 0, u.nombre, a.tratamiento_id, a.nota,
                        t.estado = 'cancelado'
                   FROM tratamiento_abonos a
                   JOIN tratamientos t ON t.id = a.tratamiento_id AND t.activo = 1
              LEFT JOIN usuarios u ON u.id = a.usuario_id
                  WHERE a.activo = 1 AND a.veterinaria_id = ? AND a.fecha BETWEEN ? AND ?",
        ];
        if ($tipo !== '') {
            $partes = array_intersect_key($partes, [$tipo => true]);
        }

        $sql    = implode("\nUNION ALL\n", $partes) . "\nORDER BY fecha DESC, creado DESC";
        $params = [];
        foreach ($partes as $_) {
            array_push($params, $veterinaria_id, $desde, $hasta);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(str_repeat('iss', count($partes)), ...$params);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    /** Venta con cliente, usuario y detalle, para la factura */
    public function getVenta(int $id, int $veterinaria_id): ?array {
        $stmt = $this->db->prepare(
            "SELECT v.*, CONCAT(c.nombre, ' ', c.apellido) AS cliente, c.dni AS cliente_dni, c.telefono AS cliente_telefono,
                    u.nombre AS usuario_nombre
               FROM ventas v
          LEFT JOIN clientes c ON c.id = v.cliente_id
          LEFT JOIN usuarios u ON u.id = v.usuario_id
              WHERE v.id = ? AND v.veterinaria_id = ?"
        );
        $stmt->bind_param('ii', $id, $veterinaria_id);
        $stmt->execute();
        $venta = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$venta) return null;

        $stmt = $this->db->prepare(
            'SELECT d.cantidad, d.precio_unitario, d.descuento, d.subtotal, p.nombre, p.codigo, p.unidad
               FROM venta_detalles d JOIN productos p ON p.id = d.producto_id
              WHERE d.venta_id = ? ORDER BY d.id'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $venta['lineas'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $venta;
    }

    /** Servicio de recaudo directo (peluquería o laboratorio; incluye anulados) para la factura */
    public function getServicio(string $tipo, int $id, int $veterinaria_id): ?array {
        $tabla = ['peluqueria' => 'peluquerias', 'laboratorio' => 'laboratorios'][$tipo] ?? null;
        if ($tabla === null) return null;
        $stmt = $this->db->prepare(
            "SELECT t.*, u.nombre AS usuario_nombre
               FROM $tabla t LEFT JOIN usuarios u ON u.id = t.usuario_id
              WHERE t.id = ? AND t.veterinaria_id = ?"
        );
        $stmt->bind_param('ii', $id, $veterinaria_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function getSucursal(int $veterinaria_id): array {
        $stmt = $this->db->prepare('SELECT nombre, ruc, telefono, direccion, email, logo FROM veterinarias WHERE id = ?');
        $stmt->bind_param('i', $veterinaria_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: ['nombre' => 'PetControl', 'ruc' => null, 'telefono' => null, 'direccion' => null, 'email' => null, 'logo' => null];
    }
}
