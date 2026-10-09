<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

ob_start();
session_start();
if (!isset($_SESSION['nombre'])) {
    header("location: login");
} else {
    //echo $_SESSION['nombre'];
    require "header.php";
    require "sidebar.php";

    if ($_SESSION['dashboard'] == 1) {
        $fecha = date("Y-m-d");
        //datos de la empresa
        //require_once "../Models/Company.php";
        require_once(dirname(__FILE__, 3) . '/Models/Consult.php');
        $consulta = new Consult();


        require_once(dirname(__FILE__, 3) . '/Models/Company.php');
        $cnegocio = new Company();
        $id_negocio = 1;
        $rsptan = $cnegocio->mostrar($id_negocio);
        //$regn=$rsptan->fetch_object();
        $empresa = $rsptan['nombre'];
        $ndocumento = $rsptan['ndocumento'];
        $documento = $rsptan['documento'];
        $direccion = $rsptan['direccion'];
        $telefono = $rsptan['telefono'];
        $email = $rsptan['email'];
        $pais = $rsptan['pais'];
        $ciudad = $rsptan['ciudad'];
        $nombre_impuesto = $rsptan['nombre_impuesto'];
        $monto_impuesto = $rsptan['monto_impuesto'];
        $moneda = $rsptan['moneda'];
        $simbolo = $rsptan['simbolo'];
        $new_simbolo = '';
        $sim_euro = '€';
        $sim_yen = '¥';
        $sim_libra = '£';
        if ($simbolo == $sim_euro) {
            $new_simbolo = EURO;
        } elseif ($simbolo == $sim_yen) {
            $new_simbolo = JPY;
        } elseif ($simbolo == $sim_libra) {
            $new_simbolo = GBP;
        } else {
            $new_simbolo = $simbolo;
        }

?>
        <!-- Main Content -->
        <div class="main-content">
            <section class="section">
                <div class="section-body">
                    <!-- add content here -->
                    <div class="row">
                        <!--COMPRAS-->
                        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                            <div class="card card-statistic-1">
                                <div class="card-icon l-bg-green">
                                    <i class="fas fa-cart-plus"></i>
                                </div>
                                <div class="card-wrap">
                                    <div class="padding-20">
                                        <div class="text-right">
                                            <h3 class="font-light mb-0">
                                                <i class="ti-arrow-up text-success"></i><span id="tcomprahoy">
                                                    <?php echo $new_simbolo ?></span>
                                            </h3>
                                            <span class="text-muted">Compras</span>
                                        </div>
                                    </div>
                                </div>
                                <a href="buy">
                                    <div class="l-bg-green">
                                        Compras
                                        <i class="fas fa-arrow-circle-right"></i>
                                    </div>
                                </a>
                            </div>
                        </div>

                        <!--VENTAS-->
                        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                            <div class="card card-statistic-1">
                                <div class="card-icon l-bg-cyan">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <div class="card-wrap">
                                    <div class="padding-20">
                                        <div class="text-right">
                                            <h3 class="font-light mb-0">
                                                <i class="ti-arrow-up text-success"></i><span id="tventahoy"></span>
                                            </h3>
                                            <span class="text-muted">Ventas</span>
                                        </div>
                                    </div>
                                </div>
                                <a href="listsales">
                                    <div class="l-bg-cyan">
                                        Ventas
                                        <i class="fas fa-arrow-circle-right"></i>
                                    </div>
                                </a>
                            </div>
                        </div>

                        <!--CLIENTES-->
                        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                            <div class="card card-statistic-1">
                                <div class="card-icon l-bg-orange">
                                    <i class="fas fa-user-plus"></i>
                                </div>
                                <div class="card-wrap">
                                    <div class="padding-20">
                                        <div class="text-right">
                                            <h3 class="font-light mb-0">
                                                <i class="ti-arrow-up text-success"></i><span id="tclientes"></span>
                                            </h3>
                                            <span class="text-muted">Clientes</span>
                                        </div>
                                    </div>
                                </div>
                                <a href="customer">
                                    <div class="l-bg-orange">
                                        Clientes
                                        <i class="fas fa-arrow-circle-right"></i>
                                    </div>
                                </a>
                            </div>
                        </div>
                        <!--PROVEEDORES-->
                        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                            <div class="small-box card card-statistic-1">
                                <div class="card-icon l-bg-red">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="card-wrap">
                                    <div class="padding-20">
                                        <div class="text-right">
                                            <h3 class="font-light mb-0">
                                                <i class="ti-arrow-up text-success"></i><span id="tproveedores"></span>
                                            </h3>
                                            <span class="text-muted">Proveedores</span>
                                        </div>
                                    </div>
                                </div>
                                <a href="supplier">
                                    <div class="l-bg-red">
                                        Proveedores
                                        <i class="fas fa-arrow-circle-right"></i>
                                    </div>
                                </a>
                            </div>
                        </div>

                        <!--ARTICULOS MAS VENDIDOS-->
                        <div class="col-lg-8 col-md-12 col-sm-12 col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4>Artículos mas vendidos</h4>
                                </div>
                                <div class="card-body">

                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <tr>
                                                <th style="width:35%;">Artículo</th>
                                                <th>Stock</th>
                                                <th style="width:35%;">Estado</th>
                                                <th>Cantidad</th>
                                                <th style="width:25%;">Ventas</th>
                                            </tr>

                                            <?php
                                            $rspta = $consulta->articuloMasVendidos();
                                            $item = 0;
                                            $tventa = 0;
                                            $porcent = 0;
                                            $entrada = array("bg-orange", "bg-indigo", "bg-purple", "bg-cyan", "bg-danger");
                                            //$claves_aleatorias = array_rand($entrada, 5);
                                            //$entrada[$claves_aleatorias[$item]]

                                            //TOTAL DE VENTAS
                                            foreach ($rspta as $reg) {
                                                $tventa += (int)$reg['cantidad_vendidos'];
                                            }

                                            foreach ($rspta as $reg) {

                                                echo '<tr>
                                                    <td>
                                                        <div class="media">
                                                            <img alt="image" class="mr-3 rounded-circle" width="40"
                                                                src="Assets/img/products/' . $reg['imagen'] . '">
                                                            <div class="media-body">
                                                                <div class="media-title">' . $reg['nombre'] . '</div>
                                                                <div class="text-job text-muted">' . $reg['codigo'] . '</div>
                                                            </div>
                                                        </div>
                                                    </td>

                                                    <td>' . $reg['stock'] . '</td>

                                                    <td class="align-middle">
                                                        <div class="progress-text">' . round(((100 * $reg['cantidad_vendidos']) / $tventa), 2) . '%</div>
                                                        <div class="progress" data-height="6">
                                                            <div class="progress-bar ' . $entrada[$item] . '" data-width="' . round(((100 * $reg['cantidad_vendidos']) / $tventa), 2) . '%"></div>
                                                        </div>
                                                    </td>

                                                    <td>' . $reg['cantidad_vendidos'] . '</td>

                                                    <td>' . $new_simbolo . ' ' . number_format($reg['precio_venta'], 2, '.', ',') . '</td>
                                                </tr>';
                                                $item++;
                                            }
                                            ?>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>



                        <!--CATEGORIAS-->
                        <div class="col-lg-4 col-md-12 col-sm-12 col-12">

                            <div class="card l-bg-purple-dark">
                                <div class="card-statistic-3">
                                    <div class="card-icon card-icon-large"><i class="fa fa-globe"></i></div>
                                    <div class="card-content">
                                        <h4 class="card-title">Ventas hoy</h4>
                                        <?php

                                        // Obtener las ventas de ayer y hoy utilizando el método ventasDia de la clase $consulta
                                        $vayer = $consulta->ventasDia('Ayer', $fecha);
                                        $vhoy = $consulta->ventasDia('Hoy', $fecha);

                                        // Calcular el total de ventas del día sumando las ventas de hoy y las de ayer
                                        $tVentaDia = $vhoy['total_ventas'] + $vayer['total_ventas'];

                                        // Calcular el porcentaje de ventas de hoy con respecto al total de ventas del día
                                        // Verificar si $tVentaDia es igual a cero para evitar división por cero
                                        $dif = 0; // Valor por defecto en caso de que $tVentaDia sea igual a cero
                                        if ($tVentaDia != 0) {
                                            $dif = (100 * $vhoy['total_ventas']) / $tVentaDia;
                                        }

                                        // Definir la clase del ícono basado en si las ventas de hoy son mayores o menores que las de ayer
                                        $ind = 'fas fa-exchange-alt';
                                        if ($vhoy['total_ventas'] < $vayer['total_ventas']) {
                                            $ind = 'fas fa-arrow-down'; // Ventas bajaron hoy
                                        } else {
                                            $ind = 'fas fa-arrow-up'; // Ventas subieron hoy o son iguales a las de ayer
                                        }


                                        ?>
                                        <span><?php echo  $new_simbolo . ' ' . number_format($vhoy['total_ventas'], 2, '.', ','); ?></span>
                                        <div class="progress mt-1 mb-1" data-height="8">
                                            <div class="progress-bar l-bg-cyan" role="progressbar" data-width="<?php echo round($dif, 2); ?>%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <p class="mb-0 text-sm">

                                            <span class="mr-2"><i class="<?php echo $ind; ?>"></i>
                                                <?php echo round($dif, 2); ?> %</span>
                                            <span class="text-nowrap">Desde ayer</span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!--ALMACEN-->

                            <div class="card l-bg-cyan-dark">
                                <div class="card-statistic-3">
                                    <div class="card-icon card-icon-large"><i class="fa fa-briefcase"></i></div>
                                    <div class="card-content">
                                        <h4 class="card-title">Ventas semana</h4>
                                        <?php
                                        $vspasado = $consulta->ventasSemana('Ayer', $fecha);
                                        $vspresente = $consulta->ventasSemana('Hoy', $fecha);

                                        $tVentaSemana = $vspasado['total_ventas'] + $vspresente['total_ventas'];
                                        $dif = 0; // Valor por defecto en caso de que $tVentaDia sea igual a cero
                                        if ($tVentaSemana != 0) {
                                            $dif = (100 * $vspresente['total_ventas']) / $tVentaSemana;
                                        }
                                        $inds = 'fas fa-exchange-alt';
                                        if ($vspresente['total_ventas'] < $vspasado['total_ventas']) {
                                            $inds = 'fas fa-arrow-down';
                                        } else {
                                            $inds = 'fa fa-arrow-up';
                                        }

                                        ?>
                                        <span><?php echo $new_simbolo . ' ' . number_format($vspresente['total_ventas'], 2, '.', ','); ?></span>
                                        <div class="progress mt-1 mb-1" data-height="8">
                                            <div class="progress-bar l-bg-orange" role="progressbar" data-width="<?php echo round($dif, 2); ?>%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <p class="mb-0 text-sm">
                                            <span class="mr-2"><i class="<?php echo $inds; ?>"></i>
                                                <?php echo round($dif, 2); ?>%</span>
                                            <span class="text-nowrap">Desde la semana pasada</span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="card l-bg-green-dark">
                                <div class="card-statistic-3">
                                    <div class="card-icon card-icon-large"><i class="fa fa-award"></i></div>
                                    <div class="card-content">
                                        <h4 class="card-title">Ventas mes</h4>
                                        <?php
                                        $vmpasado = $consulta->ventasMes('Ayer', $fecha);
                                        $vmpresente = $consulta->ventasMes('Hoy', $fecha);
                                        $tVentaMes = $vmpasado['total_ventas'] + $vmpresente['total_ventas'];
                                        $dif = 0; // Valor por defecto en caso de que $tVentaDia sea igual a cero
                                        if ($tVentaMes != 0) {
                                            $dif = (100 * $vspresente['total_ventas']) / $tVentaMes;
                                        }
                                        $indm = 'fas fa-exchange-alt';
                                        if ($vmpresente['total_ventas'] < $vmpasado['total_ventas']) {
                                            $indm = 'fas fa-arrow-down';
                                        } else {
                                            $indm = 'fa fa-arrow-up';
                                        }

                                        ?>
                                        <span><?php echo $new_simbolo . ' ' . number_format($vmpresente['total_ventas'], 2, '.', ',') ?></span>
                                        <div class="progress mt-1 mb-1" data-height="8">
                                            <div class="progress-bar l-bg-purple" role="progressbar" data-width="<?php echo round($dif, 2); ?>%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <p class="mb-0 text-sm">
                                            <span class="mr-2"><i class="<?php echo $indm; ?>"></i>
                                                <?php echo round($dif, 2); ?>%</span>
                                            <span class="text-nowrap">Desde el mes pasado</span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!--BARRAS COMPRAS 10 ULTIMOS DIAS-->
                        <div class="col-12 col-md-6 col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4>Compra de los ultimos 10 dias</h4>
                                </div>
                                <div class="card-body">
                                    <canvas id="compra10dias"></canvas>
                                </div>
                            </div>
                        </div>
                        <!--BARRAS VENTAS 12 ULTIMOS MESES-->
                        <div class="col-12 col-md-6 col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4>Venta en los ultimos 12 meses</h4>
                                </div>
                                <div class="card-body">
                                    <canvas id="venta12meses"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    <?php
    } else {
        require "access.php";
    }
    require "footer.php";
    ?>
    <!-- JS Libraies -->
    <script src="Assets/bundles/chartjs/chart.min.js"></script>
    <script src="Views/modules/scripts/dashboard.js"></script>

<?php
}
ob_end_flush();
?>