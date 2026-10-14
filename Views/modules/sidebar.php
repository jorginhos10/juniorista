
           <div class="main-sidebar sidebar-style-2">
                <aside id="sidebar-wrapper">
                    <div class="sidebar-brand">
                        <a href="dashboard"> <img alt="image" src="Assets/img/company/<?php echo $_SESSION['logoEmrpesa'];?>" class="header-logo" /> <span class="logo-name">Bendey</span>
                        </a>
                    </div>
                    <?php
/*if ($_SESSION['almacen'] == 1) {
    echo "jorge = ";
    echo "<pre>";
    var_dump($_SESSION);
    echo "</pre>";
}*/
?>
                    <!--<div class="sidebar-user">
                        <div class="sidebar-user-picture">
                            <img alt="image" src="Assets/img/users/<?php //echo $_SESSION['imagen']; 
                                                                    ?>" class="mr-3 user-img-radious-style user-list-img">
                        </div>
                        <div class="sidebar-user-details">
                            <div class="user-name"><?php //echo $_SESSION['nombre']; 
                                                    ?></div>
                            <div class="user-role"><?php //echo $_SESSION['cargo'];
                                                    ?></div>
                        </div>
                    </div>-->

                    <ul class="sidebar-menu">
                        <li class="menu-header">Menú</li>
                        <?php if ($_SESSION['dashboard'] == 1) {
                            $cd = ($_GET['url'] == 'dashboard') ? 'active' : ''; ?>
                            <li class="<?php echo $cd; ?>"><a class="nav-link" href="dashboard">
                                    <i data-feather="monitor"></i>
                                    <span>Escritorio</span></a></li>
                        <?php }
                        if ($_SESSION['almacen'] == 1) {
                            $ca = ($_GET['url'] == 'category' || $_GET['url'] == 'product') ? 'active' : ''; ?>
                            <li class="dropdown <?php echo $ca; ?>">
                                <a href="#" class="nav-link has-dropdown"><i data-feather="layers"></i><span>Almacen</span></a>
                                <ul class="dropdown-menu">
                                    <li><a class="nav-link" href="product">Artículos</a></li>
                                    <li><a class="nav-link" href="category">Categorías</a></li>
                                </ul>
                            </li>
                        <?php }
                        if ($_SESSION['compras'] == 1) {
                            $cc = ($_GET['url'] == 'supplier' || $_GET['url'] == 'buy') ? 'active' : ''; ?>
                            <li class="dropdown <?php echo $cc; ?>">
                                <a href="#" class="nav-link has-dropdown"><i data-feather="shopping-bag"></i><span>Compras</span></a>
                                <ul class="dropdown-menu">
                                    <li><a class="nav-link" href="buy">Ingresos</a></li>
                                    <li><a class="nav-link" href="supplier">Proveedores</a></li>
                                </ul>
                            </li>
                        <?php }
                        if ($_SESSION['ventas'] == 1) {
                            $cv = ($_GET['url'] == 'customer' || $_GET['url'] == 'listsales') ? 'active' : ''; ?>
                            <li class="dropdown <?php echo $cv; ?>">
                                <a href="#" class="nav-link has-dropdown"><i data-feather="shopping-cart"></i><span>Ventas</span></a>
                                <ul class="dropdown-menu">
                                    <li><a class="nav-link" href="newsale" id="newsale">Nueva venta</a></li>
                                    <li><a class="nav-link" href="listsales">Ventas</a></li>
                                    <li><a class="nav-link" href="listsales">Vencimientos</a></li>
                                    <li><a class="nav-link" href="customer">Clientes</a></li>
                                </ul>
                            </li>
                        <?php }
                        if ($_SESSION['tratamientos'] == 1) {
                            $tm = ($_GET['url'] == 'customer' || $_GET['url'] == 'tratamientos') ? 'active' : ''; ?>
<!--- tratamientos -->
                                <li class="dropdown <?php echo $cv; ?>">
                                <a href="#" class="nav-link has-dropdown"><i data-feather="activity"></i><span>Tratamiento</span></a>
                                <ul class="dropdown-menu">
                                    <li><a class="nav-link" href="registrarTratamiento" id="newsale">Nuevo Tratamiento</a></li>
                                    <li><a class="nav-link" href="verTratamiento">Lista de Tratamientos</a></li>
                                    <li><a class="nav-link" href="#">Reportes de Tratamientos</a></li>
                                </ul>
                            </li>
                        <?php }
                           if ($_SESSION['doctor'] == 1) {
                            $dt = ($_GET['url'] == 'customer' || $_GET['url'] == 'doctor') ? 'active' : ''; ?>
<!--- doctor -->
                                <li class="dropdown <?php echo $cv; ?>">
                                <a href="#" class="nav-link has-dropdown"><i data-feather="github"></i><span>Doctor</span></a>
                                <ul class="dropdown-menu">
                                    
                                <li><a class="nav-link" href="doctorVirtual" id="newsale">Asistente virtual</a></li>
                                </ul>
                            </li>
                        <?php }                     
                        
                           if ($_SESSION['laboratorio'] == 1) {
                            $dt = ($_GET['url'] == 'customer' || $_GET['url'] == 'laboratorio') ? 'active' : ''; ?>
<!--- laboratorio -->
                                <li class="dropdown <?php echo $cv; ?>">
                                <a href="#" class="nav-link has-dropdown"><i data-feather="box"></i><span>Laboratorio</span></a>
                                <ul class="dropdown-menu">
                                    <li><a class="nav-link" href="#" id="newsale">Agregar laboratorios</a></li>
                                                                        <li><a class="nav-link" href="#" id="newsale">Listados de laboratorios</a></li>
                                </ul>
                            </li>
                        <?php }                     
                                                
                             if ($_SESSION['gastos'] == 1) {
                            $dt = ($_GET['url'] == 'customer' || $_GET['url'] == 'laboratorio') ? 'active' : ''; ?>
<!--- gastos -->
                                <li class="dropdown <?php echo $cv; ?>">
                                <a href="#" class="nav-link has-dropdown"><i data-feather="dollar-sign"></i><span>Gastos</span></a>
                                <ul class="dropdown-menu">
                                    <li><a class="nav-link" href="gastos" id="newsale">Ingreso de gastos</a></li>
                                </ul>
                            </li>
                            <?php }                     
                                                
                             if ($_SESSION['peluqueria'] == 1) {
                            $dt = ($_GET['url'] == 'customer' || $_GET['url'] == 'laboratorio') ? 'active' : ''; ?>
<!--- gastos -->
                                <li class="dropdown <?php echo $cv; ?>">
                                <a href="#" class="nav-link has-dropdown"><i data-feather="scissors"></i><span>Peluqueria</span></a>
                                <ul class="dropdown-menu">
                                    <li><a class="nav-link" href="#" id="newsale">ESPACIO PARA LA PELUQUERIA</a></li>
                                </ul>
                            </li>
                        <?php }                          
                            
                        if ($_SESSION['users'] == 1) {
                            $cu =  ($_GET['url'] == 'users' || $_GET['url'] == 'permissions') ? 'active' : ''; ?>
                            <li class="dropdown <?php echo $cu; ?>">
                                <a href="#" class="nav-link has-dropdown"><i data-feather="users"></i><span>Usuarios</span></a>
                                <ul class="dropdown-menu">
                                    <li><a class="nav-link" href="users">Usuarios</a></li>
                                </ul>
                            </li>
                        <?php }                          
                            
                        if ($_SESSION['users'] == 1) {
                            $cu =  ($_GET['url'] == 'users' || $_GET['url'] == 'permissions') ? 'active' : ''; ?>
                            <li class="dropdown <?php echo $cu; ?>">
                                <a href="#" class="nav-link has-dropdown"><i data-feather="users"></i><span>Veterinarias</span></a>
                                <ul class="dropdown-menu">
                                    <li><a class="nav-link" href="https://pradera.cloud-control.co/">Pradera</a></li>
                                                                        <li><a class="nav-link" href="https://murillo.cloud-control.co/">Murillo</a></li>
                                                                                                            <li><a class="nav-link" href="https://sanluis.cloud-control.co/">San luis</a></li>
                                                                                                                                                <li><a class="nav-link" href="https://juniorista.cloud-control.co/">Juniorista</a></li>
                                                                                                                                                                                    <li><a class="nav-link" href="">Cat dogs</a></li>
                                                                                                                                                                                                                        <li><a class="nav-link" href="https://7abril.cloud-control.co/">7 de abril</a></li>
                                </ul>
                            </li>
                        <?php }
                        if ($_SESSION['settings'] == 1) {
                            $cs = ($_GET['url'] == 'generalsetting' || $_GET['url'] == 'vouchersetting' || $_GET['url'] == 'paymentstype') ? 'active' : ''; ?>
                            <li class="dropdown <?php echo $cs; ?>">
                                <a href="#" class="nav-link has-dropdown"><i data-feather="settings"></i><span>Configuración</span></a>
                                <ul class="dropdown-menu">
                                    <li><a class="nav-link" href="generalsetting">Datos generales</a></li>
                                    <li><a class="nav-link" href="vouchersetting">Comprobantes</a></li>
                                                                        <li><a class="nav-link" href="vouchersetting">Configurar peluqueria</a></li>
                                </ul>
                            </li>
                        <?php } ?>
                        <li class="menu-header">Reportes</li>
                        <?php
                        $cg = ($_GET['url'] == 'graphics') ? 'active' : ''; ?>
                        <li class="<?php echo $cg; ?>"><a class="nav-link" href="graphics"><i data-feather="grid"></i><span>Gráficos</span></a></li>
                        <?php if ($_SESSION['datebuy'] == 1) {
                            $cdb = ($_GET['url'] == 'datebuy' || $_GET['url'] == 'purchaseproduct') ? 'active' : ''; ?>
                            <li class="dropdown <?php echo $cdb; ?>">
                                <a href="#" class="nav-link has-dropdown"><i data-feather="shopping-bag"></i><span>Consulta
                                        de compras</span></a>
                                <ul class="dropdown-menu">
                                    <li><a class="nav-link" href="datebuy">Compras por fechas</a></li>
                                    <li><a class="nav-link" href="purchaseproduct">Compras articulos</a></li>
                                </ul>
                            </li>
                        <?php } ?>
                        <?php if ($_SESSION['clientdatesales'] == 1) {
                            $ccds = ($_GET['url'] == 'clientdatesales' || $_GET['url'] == 'salesproduct') ? 'active' : ''; ?>
                            <li class="dropdown <?php echo $ccds; ?>">
                                <a href="#" class="nav-link has-dropdown"><i data-feather="layout"></i><span>Consulta de
                                        ventas</span></a>
                                <ul class="dropdown-menu">
                                    <li><a class="nav-link" href="clientdatesales">Consulta Ventas</a></li>
                                  <li><a class="nav-link" href="reporteProductoIndividual">Consulta producto individual</a></li>
                                    <li><a class="nav-link" href="salesproduct">Ventas articulo</a></li>
                                </ul>
                            </li>
                        <?php } ?>
                        <li><a class="nav-link" href="#"><i data-feather="grid"></i><span>Ayuda</span></a></li>
                    </ul>
                </aside>
            </div>