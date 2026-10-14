<?php

ob_start();
session_start();
if (!isset($_SESSION['nombre'])) {
    header("location: login");
} else {
    //echo $_SESSION['nombre'];
    require "header.php";
    require "sidebar.php";

    if ($_SESSION['settings'] == 1) {
?>
        <!-- Main Content -->
        <div class="main-content">
            <section class="section">
                <div class="section-body">
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4>Datos generales</h4>
                                </div>
                                <!--TABLA DE LISTADO DE REGISTROS-->
                                <div class="card-body">
                                    <!--FORMULARIO PARA DE REGISTRO-->
                                    <div id="formularioregistros">
                                        <form action="" name="formulario" id="formulario" method="POST">
                                            <div class="row">
                                                <div class="form-group col-lg-6 col-md-6 col-xs-12">
                                                    <label for="imagen">Logo:</label>
                                                    <input class="form-control form-control-sm filestyle" type="file" name="imagen" id="imagen">
                                                    <input type="hidden" name="imagenactual" id="imagenactual">
                                                    <img src="" alt="" width="150px" height="120" id="imagenmuestra">
                                                    <!--<img id="previewHolder" alt="Selecciona una imagen" width="150px" height="120px" style="border-radius:10px;" />-->
                                                    <span id="previewImagen" class="btn btn-danger btn-sm">X</span>
                                                </div>
                                                <div class="form-group col-lg-6 col-md-6 col-xs-12">
                                                    <label for="nombre">Nombre(*):</label>
                                                    <input class="form-control form-control-sm" type="hidden" name="id_negocio" id="id_negocio">
                                                    <input class="form-control form-control-sm" type="text" name="nombre" id="nombre" maxlength="100" placeholder="Nombre" required>
                                                </div>
                                                <div class="form-group col-lg-6 col-md-6 col-xs-12">
                                                    <label for="ndocumento">Nombre del documento:(*)</label>
                                                    <input class="form-control form-control-sm" type="text" name="ndocumento" placeholder="RUC" id="ndocumento" required>
                                                </div>
                                                <div class="form-group col-lg-6 col-md-6 col-xs-12">
                                                    <label for="documento">Documento:(*)</label>
                                                    <input class="form-control form-control-sm" type="text" name="documento" id="documento" required>
                                                </div>
                                                <div class="form-group col-lg-12 col-md-12 col-xs-12">
                                                    <label for="direccion">Dirección(*):</label>
                                                    <input class="form-control form-control-sm" type="text" name="direccion" id="direccion" maxlength="256" placeholder="Dirección" required>
                                                </div>
                                                <div class="form-group col-lg-6 col-md-6 col-xs-12">
                                                    <label for="pais">Pais:</label>
                                                    <input class="form-control form-control-sm" type="text" name="pais" id="pais">
                                                </div>
                                                <div class="form-group col-lg-6 col-md-6 col-xs-12">
                                                    <label for="telefono">Telefono(*):</label>
                                                    <input class="form-control form-control-sm" type="text" name="telefono" id="telefono" required>
                                                </div>
                                                <div class="form-group col-lg-6 col-md-6 col-xs-12">
                                                    <label for="">E-mail:</label>
                                                    <input class="form-control form-control-sm" type="email" name="email" id="email">
                                                </div>
                                                <div class="form-group col-lg-6 col-md-6 col-xs-12">
                                                    <label for="ciudad">Ciudad:</label>
                                                    <input class="form-control form-control-sm" type="text" name="ciudad" id="ciudad">
                                                </div>
                                                <div class="form-group col-lg-12 col-md-12 col-xs-12">
                                                    <label for="">Datos Financieros</label>
                                                </div>
                                                <div class="form-group col-lg-3 col-md-3 col-xs-12">
                                                    <label for="nombre_impuesto">Nombre Imp:</label>
                                                    <input class="form-control form-control-sm" type="text" name="nombre_impuesto" id="nombre_impuesto" placeholder="IVA - IGV">
                                                </div>
                                                <div class="form-group col-lg-3 col-md-3 col-xs-12">
                                                    <label for="monto_impuesto">Cantidad (%):</label>
                                                    <input class="form-control form-control-sm" type="text" name="monto_impuesto" id="monto_impuesto">
                                                </div>
                                                <div class="form-group col-lg-3 col-md-3 col-xs-12">
                                                    <label for="moneda">Moneda:</label>
                                                    <input class="form-control form-control-sm" type="text" name="moneda" id="moneda" placeholder="$">
                                                </div>
                                                <div class="form-group col-lg-3 col-md-3 col-xs-12">
                                                    <label for="simbolo">Simbolo:</label>
                                                    <input class="form-control form-control-sm" type="text" name="simbolo" id="simbolo" placeholder="s/ - $">
                                                </div>
                                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                    <button class="btn btn-primary" type="submit" id="btnGuardar"><i class="fa fa-save"></i> Actualizar</button>

                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                    <!--FORMULARIO PARA DE REGISTRO FIN-->
                                </div>
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
    <script src="Views/modules/scripts/generalsetting.js"></script>
<?php
}
ob_end_flush();
?>