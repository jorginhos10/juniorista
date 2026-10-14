<?php

ob_start();
session_start();
if (!isset($_SESSION['nombre'])) {
    header("location: login");
} else {
    //echo $_SESSION['nombre'];
    require "header.php";
    require "sidebar.php";

    if ($_SESSION['ventas'] == 1) {
?>





<?php
// Configuración de la base de datos
$db_host = "localhost";
$db_port = "3306";
$db_name = "jorginho_juniorista";
$db_user = "jorginho_juniorista";
$db_pass = "jorginho10.";

// Variables simples
$searchTerm = isset($_POST['search']) ? $_POST['search'] : '';
$idarticulo = isset($_POST['idarticulo']) ? intval($_POST['idarticulo']) : 0;

// Fechas por defecto (hoy)
$fecha_inicio = isset($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : date('Y-m-d');
$fecha_fin = isset($_POST['fecha_fin']) ? $_POST['fecha_fin'] : date('Y-m-d');
?>

<div class="main-content">
<head>
    <meta charset="UTF-8">
    <title>Sistema de Consulta de Ventas</title>
    <style>
        /* Estilos tipo Bootstrap con paleta grisácea */
        :root {
            --slate-50: #f8fafc;
            --slate-100: #f1f5f9;
            --slate-200: #e2e8f0;
            --slate-300: #cbd5e1;
            --slate-400: #94a3b8;
            --slate-500: #64748b;
            --slate-600: #475569;
            --slate-700: #334155;
            --slate-800: #1e293b;
            --slate-900: #0f172a;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --blue-500: #3b82f6;
            --green-500: #10b981;
            --red-500: #ef4444;
            --yellow-500: #f59e0b;
            --indigo-500: #6366f1;
            --border-radius: 0.5rem;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        

        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        

        

        
        /* Search Form */
        .search-form {
            background: var(--white);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-label {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--gray-700);
            margin-bottom: 0.375rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-control {
            padding: 0.625rem 0.75rem;
            font-size: 0.875rem;
            line-height: 1.25;
            color: var(--gray-900);
            background-color: var(--white);
            border: 1px solid var(--gray-300);
            border-radius: var(--border-radius);
            transition: all 0.15s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--slate-500);
            box-shadow: 0 0 0 3px rgba(100, 116, 139, 0.1);
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            font-size: 0.875rem;
            font-weight: 500;
            line-height: 1.25;
            border-radius: var(--border-radius);
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.15s ease;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--slate-600), var(--slate-700));
            color: white;
            border-color: var(--slate-700);
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--slate-700), var(--slate-800));
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--green-500), #0da271);
            color: white;
            border-color: #0da271;
        }
        
        .btn-success:hover {
            background: linear-gradient(135deg, #0da271, #0c8a62);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, var(--red-500), #dc2626);
            color: white;
            border-color: #dc2626;
        }
        
        .btn-danger:hover {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, var(--gray-400), var(--gray-500));
            color: white;
            border-color: var(--gray-500);
        }
        
        .btn-secondary:hover {
            background: linear-gradient(135deg, var(--gray-500), var(--gray-600));
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }
        
        /* Article Header */
        .article-header {
            background: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
            border-left: 4px solid var(--slate-600);
        }
        
        .article-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--slate-800);
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .article-meta {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--gray-600);
            font-size: 0.875rem;
        }
        
        .stock-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
        }
        
        .stock-high { background-color: #d1fae5; color: #065f46; }
        .stock-medium { background-color: #fef3c7; color: #92400e; }
        .stock-low { background-color: #fee2e2; color: #991b1b; }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .stat-card {
            background: white;
            padding: 1rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
            text-align: center;
        }
        
        .stat-label {
            font-size: 0.75rem;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--slate-800);
        }
        
        /* Table */
        .table-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table thead {
            background: linear-gradient(135deg, var(--slate-700), var(--slate-800));
        }
        
        .table th {
            padding: 0.75rem 1rem;
            text-align: left;
            font-size: 0.75rem;
            font-weight: 600;
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid var(--slate-700);
        }
        
        .table td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--gray-200);
            color: var(--gray-700);
        }
        
        .table tbody tr:hover {
            background-color: var(--slate-50);
        }
        
        .table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        
        /* Vendedor Badge */
        .vendedor-badge {
            background: linear-gradient(135deg, var(--slate-100), var(--slate-200));
            color: var(--slate-700);
            padding: 0.25rem 0.5rem;
            border-radius: var(--border-radius);
            font-size: 0.75rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        /* Export Buttons */
        .export-buttons {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }
        
        /* Instructions */
        .instructions {
            background: linear-gradient(135deg, var(--slate-50), var(--gray-100));
            padding: 1.5rem;
            border-radius: var(--border-radius);
            border: 1px solid var(--gray-200);
            margin-top: 2rem;
        }
        
        .instructions h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--slate-800);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        /* Results Grid */
        .results-grid {
            display: grid;
            gap: 0.75rem;
            margin-top: 1rem;
        }
        
        .result-card {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--border-radius);
            padding: 1rem;
            cursor: pointer;
            transition: all 0.15s ease;
            box-shadow: var(--shadow-sm);
        }
        
        .result-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            border-color: var(--slate-400);
        }
        
        .result-card-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .result-info h4 {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--slate-800);
            margin-bottom: 0.25rem;
        }
        
        .result-meta {
            font-size: 0.75rem;
            color: var(--gray-500);
        }
        
        /* Alert */
        .alert {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
            border: 1px solid transparent;
        }
        
        .alert-info {
            background-color: #eff6ff;
            border-color: #bfdbfe;
            color: #1e40af;
        }
        
        .alert-warning {
            background-color: #fffbeb;
            border-color: #fde68a;
            color: #92400e;
        }
        
        /* Utilities */
        .mb-2 { margin-bottom: 0.5rem; }
        .mb-3 { margin-bottom: 1rem; }
        .mb-4 { margin-bottom: 1.5rem; }
        .mt-2 { margin-top: 0.5rem; }
        .mt-3 { margin-top: 1rem; }
        .mt-4 { margin-top: 1.5rem; }
        .gap-2 { gap: 0.5rem; }
        .gap-3 { gap: 1rem; }
        .gap-4 { gap: 1.5rem; }
        .d-flex { display: flex; }
        .align-items-center { align-items: center; }
        .justify-content-between { justify-content: space-between; }
        .w-100 { width: 100%; }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <!-- Header -->

        
        <!-- Search Form -->
        <div class="search-form">
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label"><i class="far fa-calendar"></i> Fecha Inicio</label>
                        <input type="date" name="fecha_inicio" class="form-control" value="<?php echo $fecha_inicio; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label"><i class="far fa-calendar"></i> Fecha Fin</label>
                        <input type="date" name="fecha_fin" class="form-control" value="<?php echo $fecha_fin; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-search"></i> Buscar Artículo</label>
                        <input type="text" name="search" class="form-control" 
                               value="<?php echo htmlspecialchars($searchTerm); ?>" 
                               placeholder="Escribe el nombre del artículo..." autocomplete="off">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" style="visibility: hidden;">Buscar</label>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <?php
        // Solo procesar si hay conexión
        if ($searchTerm || $idarticulo) {
            $conn = @mysqli_connect($db_host, $db_user, $db_pass, $db_name, $db_port);
            
            if ($conn) {
                mysqli_set_charset($conn, "utf8");
                
                if ($idarticulo > 0) {
                    // Obtener información completa del artículo incluyendo STOCK
                    $sql_articulo = "SELECT idarticulo, nombre, codigo, stock FROM articulo WHERE idarticulo = $idarticulo";
                    $result_nombre = mysqli_query($conn, $sql_articulo);
                    $articulo_info = [];
                    $stock = 0;
                    
                    if ($result_nombre && $row_nombre = mysqli_fetch_assoc($result_nombre)) {
                        $articulo_info = $row_nombre;
                        $stock = intval($row_nombre['stock'] ?? 0);
                    }
                    
                    // Determinar clase CSS para el stock
                    $stock_class = 'stock-high';
                    if ($stock <= 0) {
                        $stock_class = 'stock-low';
                    } elseif ($stock <= 10) {
                        $stock_class = 'stock-medium';
                    }
                    
                    // Mostrar información del artículo con STOCK
                    echo "<div class='article-header'>";
                    echo "<div class='d-flex justify-content-between align-items-center mb-3'>";
                    echo "<div>";
                    echo "<h2 class='article-title'>";
                    echo "<i class='fas fa-box'></i>";
                    echo htmlspecialchars($articulo_info['nombre'] ?? "Artículo ID: $idarticulo");
                    echo "</h2>";
                    echo "<div class='article-meta'>";
                    echo "<span class='meta-item'><i class='fas fa-hashtag'></i> ID: $idarticulo</span>";
                    if (!empty($articulo_info['codigo'])) {
                        echo "<span class='meta-item'><i class='fas fa-barcode'></i> Código: " . htmlspecialchars($articulo_info['codigo']) . "</span>";
                    }
                    echo "</div>";
                    echo "</div>";
                    echo "<div>";
                    echo "<span class='stock-badge $stock_class'>";
                    echo "<i class='fas fa-boxes'></i>";
                    echo "Stock: " . number_format($stock, 0);
                    echo "</span>";
                    echo "</div>";
                    echo "</div>";
                    
                    // Tarjetas de estadísticas
                    echo "<div class='stats-grid'>";
                    echo "<div class='stat-card'>";
                    echo "<div class='stat-label'>ID Artículo</div>";
                    echo "<div class='stat-value'>$idarticulo</div>";
                    echo "</div>";
                    
                    if (!empty($articulo_info['codigo'])) {
                        echo "<div class='stat-card'>";
                        echo "<div class='stat-label'>Código</div>";
                        echo "<div class='stat-value'>" . htmlspecialchars($articulo_info['codigo']) . "</div>";
                        echo "</div>";
                    }
                    
                    echo "<div class='stat-card'>";
                    echo "<div class='stat-label'>Stock Actual</div>";
                    echo "<div class='stat-value'>" . number_format($stock, 0) . "</div>";
                    echo "</div>";
                    echo "</div>";
                    echo "</div>";
                    
                    // Período consultado
                    echo "<div class='alert alert-info'>";
                    echo "<i class='fas fa-calendar-alt'></i> ";
                    echo "<strong>Período consultado:</strong> " . date('d/m/Y', strtotime($fecha_inicio)) . " al " . date('d/m/Y', strtotime($fecha_fin));
                    echo "</div>";
                    
                    // Botones de exportación
                    echo "<div class='export-buttons'>";
                    echo "<button class='btn btn-danger' onclick='exportToPDF()'>";
                    echo "<i class='fas fa-file-pdf'></i> Exportar a PDF";
                    echo "</button>";
                    
                    echo "<button class='btn btn-success' onclick='exportToExcel()'>";
                    echo "<i class='fas fa-file-excel'></i> Exportar a Excel";
                    echo "</button>";
                    
                    echo "<a href='?' class='btn btn-secondary'>";
                    echo "<i class='fas fa-arrow-left'></i> Volver al buscador";
                    echo "</a>";
                    echo "</div>";
                    
                    // Consulta para obtener ventas con filtro de fechas
                    $where_fecha = "";
                    if (!empty($fecha_inicio) && !empty($fecha_fin)) {
                        $where_fecha = "AND DATE(v.fecha_hora) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
                    }
                    
                    $sql = "SELECT 
                                dv.cantidad,
                                dv.precio_venta,
                                v.fecha_hora,
                                v.num_comprobante,
                                v.idusuario,
                                u.nombre as nombre_vendedor,
                                (dv.cantidad * dv.precio_venta) as total
                            FROM detalle_venta dv
                            INNER JOIN venta v ON dv.idventa = v.idventa
                            LEFT JOIN usuario u ON v.idusuario = u.idusuario
                            WHERE dv.idarticulo = $idarticulo
                            $where_fecha
                            ORDER BY v.fecha_hora DESC
                            LIMIT 100";
                    
                    $result = mysqli_query($conn, $sql);
                    
                    if ($result && mysqli_num_rows($result) > 0) {
                        $total_registros = mysqli_num_rows($result);
                        $total_cantidad = 0;
                        $total_ventas = 0;
                        $vendedores = [];
                        
                        // Calcular totales primero
                        $temp_result = mysqli_query($conn, $sql);
                        while ($row = mysqli_fetch_assoc($temp_result)) {
                            $total_cantidad += $row['cantidad'];
                            $total_ventas += $row['total'];
                            $idusuario = $row['idusuario'];
                            $vendedor_nombre = $row['nombre_vendedor'] ?? 'Sin vendedor';
                            
                            if ($idusuario && !isset($vendedores[$idusuario])) {
                                $vendedores[$idusuario] = $vendedor_nombre;
                            }
                        }
                        
                        echo "<div class='table-container'>";
                        echo "<table class='table'>";
                        echo "<thead>";
                        echo "<tr>";
                        echo "<th>Fecha y Hora</th>";
                        echo "<th>Vendedor</th>";
                        echo "<th>Comprobante</th>";
                        echo "<th class='text-right'>Cantidad</th>";
                        echo "<th class='text-right'>Total</th>";
                        echo "</tr>";
                        echo "</thead>";
                        echo "<tbody>";
                        
                        mysqli_data_seek($result, 0); // Resetear puntero del resultado
                        
                        while ($row = mysqli_fetch_assoc($result)) {
                            $fecha_hora = $row['fecha_hora'];
                            $comprobante = $row['num_comprobante'];
                            $vendedor_nombre = $row['nombre_vendedor'] ?? 'Sin vendedor';
                            
                            // Formatear fecha y hora
                            $fecha_formateada = 'N/A';
                            if (!empty($fecha_hora) && $fecha_hora != '0000-00-00 00:00:00') {
                                $fecha_formateada = date('d/m/Y H:i', strtotime($fecha_hora));
                            }
                            
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($fecha_formateada) . "</td>";
                            echo "<td><span class='vendedor-badge'>" . htmlspecialchars($vendedor_nombre) . "</span></td>";
                            echo "<td>" . htmlspecialchars($comprobante) . "</td>";
                            echo "<td class='text-right'>" . number_format($row['cantidad'], 0) . "</td>";
                            echo "<td class='text-right'><strong>$ " . number_format($row['total'], 2) . "</strong></td>";
                            echo "</tr>";
                        }
                        
                        // Fila de totales
                        echo "<tr style='background-color: var(--slate-50);'>";
                        echo "<td colspan='3'><strong>TOTALES</strong></td>";
                        echo "<td class='text-right'><strong>" . number_format($total_cantidad, 0) . "</strong></td>";
                        echo "<td class='text-right'><strong>$ " . number_format($total_ventas, 2) . "</strong></td>";
                        echo "</tr>";
                        
                        echo "</tbody>";
                        echo "</table>";
                        echo "</div>";
                        
                        // Estadísticas
                        echo "<div class='stats-grid mb-4'>";
                        echo "<div class='stat-card'>";
                        echo "<div class='stat-label'>Ventas en período</div>";
                        echo "<div class='stat-value'>" . number_format($total_registros) . "</div>";
                        echo "</div>";
                        
                        echo "<div class='stat-card'>";
                        echo "<div class='stat-label'>Unidades vendidas</div>";
                        echo "<div class='stat-value'>" . number_format($total_cantidad, 0) . "</div>";
                        echo "</div>";
                        
                        echo "<div class='stat-card'>";
                        echo "<div class='stat-label'>Monto total</div>";
                        echo "<div class='stat-value'>$ " . number_format($total_ventas, 2) . "</div>";
                        echo "</div>";
                        
                        echo "<div class='stat-card'>";
                        echo "<div class='stat-label'>Vendedores</div>";
                        echo "<div class='stat-value'>" . count($vendedores) . "</div>";
                        echo "</div>";
                        echo "</div>";
                        
                    } else {
                        echo "<div class='alert alert-warning'>";
                        echo "<i class='fas fa-exclamation-triangle'></i> ";
                        echo "Este artículo no tiene ventas registradas entre " . date('d/m/Y', strtotime($fecha_inicio)) . " y " . date('d/m/Y', strtotime($fecha_fin)) . ".";
                        echo "</div>";
                    }
                    
                } elseif ($searchTerm) {
                    // Buscar artículos con información de stock
                    echo "<div class='d-flex justify-content-between align-items-center mb-3'>";
                    echo "<h3><i class='fas fa-search'></i> Resultados para: \"" . htmlspecialchars($searchTerm) . "\"</h3>";
                    echo "<div class='alert alert-info' style='margin: 0; padding: 0.5rem 1rem;'>";
                    echo "<i class='fas fa-calendar-alt'></i> " . date('d/m/Y', strtotime($fecha_inicio)) . " - " . date('d/m/Y', strtotime($fecha_fin));
                    echo "</div>";
                    echo "</div>";
                    
                    $sql = "SELECT idarticulo, nombre, codigo, stock FROM articulo 
                            WHERE nombre LIKE '%" . mysqli_real_escape_string($conn, $searchTerm) . "%' 
                            ORDER BY nombre ASC 
                            LIMIT 50";
                    
                    $result = mysqli_query($conn, $sql);
                    
                    if ($result && mysqli_num_rows($result) > 0) {
                        $total_resultados = mysqli_num_rows($result);
                        echo "<p class='mb-3'>Se encontraron <strong>$total_resultados</strong> artículo(s):</p>";
                        
                        echo "<div class='results-grid'>";
                        while ($row = mysqli_fetch_assoc($result)) {
                            $nombre = htmlspecialchars($row['nombre']);
                            $id = $row['idarticulo'];
                            $codigo = !empty($row['codigo']) ? htmlspecialchars($row['codigo']) : '';
                            $stock = intval($row['stock'] ?? 0);
                            
                            // Determinar color del stock
                            $stock_class = 'stock-high';
                            if ($stock <= 0) {
                                $stock_class = 'stock-low';
                            } elseif ($stock <= 10) {
                                $stock_class = 'stock-medium';
                            }
                            
                            echo "<form method='POST'>";
                            echo "<input type='hidden' name='idarticulo' value='$id'>";
                            echo "<input type='hidden' name='fecha_inicio' value='$fecha_inicio'>";
                            echo "<input type='hidden' name='fecha_fin' value='$fecha_fin'>";
                            echo "<input type='hidden' name='search' value='" . htmlspecialchars($searchTerm) . "'>";
                            echo "<button type='submit' class='result-card' style='width: 100%; text-align: left; background: none; border: none;'>";
                            echo "<div class='result-card-content'>";
                            echo "<div class='result-info'>";
                            echo "<h4>$nombre</h4>";
                            echo "<div class='result-meta'>";
                            echo "ID: $id";
                            if ($codigo) {
                                echo " • Código: $codigo";
                            }
                            echo "</div>";
                            echo "</div>";
                            echo "<div>";
                            echo "<span class='stock-badge $stock_class'>";
                            echo "<i class='fas fa-boxes'></i>";
                            echo number_format($stock, 0) . " unidades";
                            echo "</span>";
                            echo "</div>";
                            echo "</div>";
                            echo "</button>";
                            echo "</form>";
                        }
                        echo "</div>";
                        
                    } else {
                        echo "<div class='alert alert-warning'>";
                        echo "<i class='fas fa-exclamation-triangle'></i> ";
                        echo "No se encontraron artículos que coincidan con \"" . htmlspecialchars($searchTerm) . "\"";
                        echo "</div>";
                    }
                }
                
                mysqli_close($conn);
            } else {
                echo "<div class='alert alert-warning'>";
                echo "<i class='fas fa-exclamation-circle'></i> ";
                echo "Error de conexión a la base de datos";
                echo "</div>";
            }
        } else {
            // Instrucciones iniciales
            echo "<div class='instructions'>";
            echo "<h3><i class='fas fa-info-circle'></i> Instrucciones de uso</h3>";
            echo "<div class='stats-grid'>";
            echo "<div class='stat-card'>";
            echo "<div class='stat-label'><i class='far fa-calendar'></i> Paso 1</div>";
            echo "<div class='stat-value'>Selecciona fechas</div>";
            echo "<p style='font-size: 0.75rem; color: var(--gray-500); margin-top: 0.5rem;'>Define el período a consultar</p>";
            echo "</div>";
            
            echo "<div class='stat-card'>";
            echo "<div class='stat-label'><i class='fas fa-search'></i> Paso 2</div>";
            echo "<div class='stat-value'>Busca artículo</div>";
            echo "<p style='font-size: 0.75rem; color: var(--gray-500); margin-top: 0.5rem;'>Escribe el nombre o parte de él</p>";
            echo "</div>";
            
            echo "<div class='stat-card'>";
            echo "<div class='stat-label'><i class='fas fa-chart-bar'></i> Paso 3</div>";
            echo "<div class='stat-value'>Consulta ventas</div>";
            echo "<p style='font-size: 0.75rem; color: var(--gray-500); margin-top: 0.5rem;'>Revisa stock e historial</p>";
            echo "</div>";
            
            echo "<div class='stat-card'>";
            echo "<div class='stat-label'><i class='fas fa-download'></i> Paso 4</div>";
            echo "<div class='stat-value'>Exporta reportes</div>";
            echo "<p style='font-size: 0.75rem; color: var(--gray-500); margin-top: 0.5rem;'>Descarga en PDF o Excel</p>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
        }
        ?>
    </div>
    
    <script>
        // Funciones para exportar (placeholder - implementar según necesidades)
        function exportToPDF() {
            alert('Funcionalidad de exportación a PDF - Para implementar según requerimientos específicos');
            // Aquí iría el código para generar PDF
            // Podrías usar: window.print(), jsPDF, o hacer una petición al servidor
        }
        
        function exportToExcel() {
            alert('Funcionalidad de exportación a Excel - Para implementar según requerimientos específicos');
            // Aquí iría el código para generar Excel
            // Podrías usar: SheetJS, TableExport, o hacer una petición al servidor
        }
        
        // Enfocar el campo de búsqueda al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            var searchInput = document.querySelector('input[name="search"]');
            if (searchInput && !<?php echo $idarticulo > 0 ? 'true' : 'false'; ?>) {
                searchInput.focus();
            }
            
            // Agregar funcionalidad de búsqueda con Enter
            if (searchInput) {
                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        this.form.submit();
                    }
                });
            }
            
            // Validar que fecha fin no sea menor que fecha inicio
            var fechaInicio = document.querySelector('input[name="fecha_inicio"]');
            var fechaFin = document.querySelector('input[name="fecha_fin"]');
            
            if (fechaInicio && fechaFin) {
                fechaInicio.addEventListener('change', function() {
                    if (fechaFin.value && this.value > fechaFin.value) {
                        fechaFin.value = this.value;
                    }
                });
                
                fechaFin.addEventListener('change', function() {
                    if (fechaInicio.value && this.value < fechaInicio.value) {
                        alert('La fecha fin no puede ser menor que la fecha inicio');
                        this.value = fechaInicio.value;
                    }
                });
            }
        });
    </script>
</body>
</div>

<?php
    } else {
        require "access.php";
    }
    require "footer.php";
    ?>
<script src="Views/modules/scripts/salesproduct.js?v=<?php echo time(); ?>"></script>
<?php
}
ob_end_flush();
?>