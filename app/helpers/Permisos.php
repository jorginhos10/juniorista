<?php

/**
 * Permisos por módulo de cada usuario.
 *  - usuarios.permisos guarda la lista JSON de módulos habilitados; NULL = todos.
 *  - El admin siempre tiene acceso total (nadie puede quedar sin gestionar el sistema).
 *  - Dashboard y Configuración están siempre disponibles.
 *  - El router (index.php) bloquea las rutas de un módulo no permitido y el menú lo oculta.
 */
class Permisos {

    /** Módulos configurables: clave => [nombre, ícono, categoría] (mismas categorías que el menú) */
    public const MODULOS = [
        'citas'       => ['Citas',            'calendar2-check',         'Atención'],
        'clientes'    => ['Clientes',         'people-fill',             'Atención'],
        'mascotas'    => ['Mascotas',         'heart-fill',              'Atención'],
        'historial'   => ['Historial médico', 'clipboard2-pulse-fill',   'Atención'],
        'tratamiento' => ['Tratamiento',      'capsule',                 'Servicios'],
        'peluqueria'  => ['Peluquería',       'scissors',                'Servicios'],
        'laboratorio' => ['Laboratorios',     'eyedropper',              'Servicios'],
        'ventas'      => ['Ventas',           'bag-fill',                'Caja'],
        'facturacion' => ['Facturación',      'receipt-cutoff',          'Caja'],
        'gastos'      => ['Gastos',           'wallet2',                 'Caja'],
        'arqueo'      => ['Arqueo de caja',   'cash-stack',              'Caja'],
        'almacen'     => ['Almacén',          'archive-fill',            'Inventario'],
        'ingresos'    => ['Ingresos',         'box-arrow-in-down-right', 'Inventario'],
        'perdidas'    => ['Pérdidas',         'box-arrow-up-right',      'Inventario'],
        'descargos'   => ['Descargos',        'box-arrow-down',          'Inventario'],
        'proveedores' => ['Proveedores',      'truck',                   'Inventario'],
        // Cada reporte tiene su propio permiso (la clave es la misma del menú)
        'reporte-ventas'   => ['Reporte de ventas',   'bar-chart-fill',          'Reportes'],
        'reporte-unidades' => ['Ventas por unidad',   'boxes',                   'Reportes'],
        'reporte-ingresos' => ['Reporte de ingresos', 'box-arrow-in-down-right', 'Reportes'],
        'reporte-perdidas' => ['Reporte de pérdidas', 'graph-down-arrow',        'Reportes'],
    ];

    /** Segundo tramo de la ruta «reportes/…» → permiso del reporte */
    private const REPORTES = [
        'ventas' => 'reporte-ventas', 'unidades' => 'reporte-unidades', 'ingresos' => 'reporte-ingresos', 'perdidas' => 'reporte-perdidas',
    ];

    /** Rutas que también se permiten desde otro módulo (Facturación consulta e imprime abonos de tratamientos) */
    private const RUTAS_COMPARTIDAS = [
        'tratamiento/abonos'         => ['tratamiento', 'facturacion'],
        'tratamiento/abono/imprimir' => ['tratamiento', 'facturacion'],
    ];

    private static ?array $habilitados = null;   // caché por petición

    /** Módulo al que pertenece una ruta (null = no está sujeta a permisos) */
    public static function moduloDeRuta(string $ruta): ?string {
        $partes  = explode('/', $ruta);
        $primero = $partes[0];
        if ($primero === 'reportes') {
            return self::REPORTES[$partes[1] ?? ''] ?? null;        // 'reportes/ventas/imprimir' → 'reporte-ventas'
        }
        return isset(self::MODULOS[$primero]) ? $primero : null;   // 'ventas/historial' → 'ventas'
    }

    public static function puede(string $modulo): bool {
        if (!isset(self::MODULOS[$modulo])) return true;                     // dashboard, configuración, etc.
        if (($_SESSION['usuario_rol'] ?? '') === 'admin') return true;
        return in_array($modulo, self::habilitados(), true);
    }

    /** Clave del menú → módulo (coinciden; cada reporte tiene su propio permiso) */
    public static function puedeMenu(string $claveMenu): bool {
        return self::puede($claveMenu);
    }

    /** Bloquea la petición si el usuario no tiene permiso para el módulo de la ruta */
    public static function verificar(string $ruta): void {
        if (!isset($_SESSION['usuario_id'])) return;

        $modulos = self::RUTAS_COMPARTIDAS[$ruta] ?? [self::moduloDeRuta($ruta)];
        foreach ($modulos as $m) {
            if ($m === null || self::puede($m)) return;
        }

        $nombre = self::MODULOS[$modulos[0]][0];
        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') || !empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => "No tienes permiso para el módulo $nombre."]);
            exit;
        }
        $_SESSION['flash_error'] = "No tienes permiso para el módulo «{$nombre}». Pídele acceso al administrador.";
        header('Location: ' . BASE_URL . '/dashboard');
        exit;
    }

    /** Normaliza lo enviado por el formulario: solo claves válidas; si están todas, NULL (= acceso total) */
    public static function normalizar(array $modulos): ?string {
        $validos = array_values(array_intersect(array_keys(self::MODULOS), $modulos));
        return count($validos) === count(self::MODULOS) ? null : json_encode($validos);
    }

    /** Lista de módulos habilitados a partir del valor guardado (NULL = todos) */
    public static function decodificar(?string $permisos): array {
        if ($permisos === null || $permisos === '') return array_keys(self::MODULOS);
        $lista = json_decode($permisos, true);
        if (!is_array($lista)) return [];
        // Permisos guardados cuando había un solo interruptor «Reportes»: equivale a todos los reportes
        if (in_array('reportes', $lista, true)) $lista = array_merge($lista, array_values(self::REPORTES));
        return array_values(array_intersect(array_keys(self::MODULOS), $lista));
    }

    // ── Sucursales asignadas ───────────────────────────────
    // usuarios.sucursales: lista JSON de ids (NULL = todas). Con una sola, el usuario queda fijo en ella;
    // con varias, puede cambiar solo entre esas.

    private static ?array $sucursales = null;   // caché por petición: ids de sucursales activas permitidas

    /** Ids de sucursales activas que el usuario en sesión puede usar */
    public static function sucursalesPermitidas(): array {
        if (self::$sucursales === null) {
            require_once ROOT . '/config/Database.php';
            $db  = Database::getInstance()->getConnection();
            $activas = array_map('intval', array_column($db->query('SELECT id FROM veterinarias WHERE activo = 1 ORDER BY id')->fetch_all(MYSQLI_ASSOC), 'id'));

            $stmt = $db->prepare('SELECT sucursales FROM usuarios WHERE id = ?');
            $id = (int)($_SESSION['usuario_id'] ?? 0);
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $valor = $stmt->get_result()->fetch_row()[0] ?? null;
            $stmt->close();

            $asignadas = self::decodificarSucursales($valor);
            // Si las asignadas ya no existen (sucursal eliminada), se cae a todas para no dejar al usuario sin acceso
            $permitidas = $asignadas ? array_values(array_intersect($activas, $asignadas)) : $activas;
            self::$sucursales = $permitidas ?: $activas;
        }
        return self::$sucursales;
    }

    public static function puedeSucursal(int $veterinaria_id): bool {
        return in_array($veterinaria_id, self::sucursalesPermitidas(), true);
    }

    /** Si la sucursal de la sesión no está permitida (o no hay), se usa la primera permitida */
    public static function asegurarSucursal(): void {
        if (!isset($_SESSION['usuario_id'])) return;
        $actual = (int)($_SESSION['veterinaria_id'] ?? 0);
        if ($actual > 0 && self::puedeSucursal($actual)) return;
        $_SESSION['veterinaria_id'] = self::sucursalesPermitidas()[0] ?? 0;
    }

    /** Lo enviado por el formulario → JSON de ids válidos; ninguna o todas = NULL (sin restricción) */
    public static function normalizarSucursales(array $ids, array $activas): ?string {
        $validos = array_values(array_intersect(array_map('intval', $activas), array_map('intval', $ids)));
        sort($validos);
        return ($validos === [] || count($validos) === count($activas)) ? null : json_encode($validos);
    }

    public static function decodificarSucursales(?string $valor): array {
        $lista = ($valor === null || $valor === '') ? [] : json_decode($valor, true);
        return is_array($lista) ? array_values(array_map('intval', $lista)) : [];
    }

    /** Módulos habilitados del usuario en sesión (se leen de la base en cada petición: los cambios aplican al instante) */
    private static function habilitados(): array {
        if (self::$habilitados === null) {
            require_once ROOT . '/config/Database.php';
            $stmt = Database::getInstance()->getConnection()->prepare('SELECT permisos FROM usuarios WHERE id = ?');
            $id = (int)$_SESSION['usuario_id'];
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $valor = $stmt->get_result()->fetch_row()[0] ?? null;
            $stmt->close();
            self::$habilitados = self::decodificar($valor);
        }
        return self::$habilitados;
    }
}
