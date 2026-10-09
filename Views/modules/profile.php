<?php

ob_start();
session_start();
if (!isset($_SESSION['nombre'])) {
  header("location: login");
} else {
  require "header.php";
  require "sidebar.php";
?>
  <!-- Main Content -->
  <div class="main-content">
    <section class="section">
      <!--CENTRO-->
      <div class="row">
        <div class="col-12 col-md-12 col-lg-4">
          <div class="card author-box">
            <div class="card-body">
              <div class="author-box-center">
                <img alt="image" src="Assets/img/users/<?php echo $_SESSION['imagen']; ?>" class="rounded-circle author-box-picture">
                <div class="clearfix"></div>
                <div class="author-box-name">
                  <a href="#"><?php echo $_SESSION['nombre']; ?></a>
                </div>
                <div class="author-box-job"><?php echo $_SESSION['cargo']; ?></div>
              </div>
              <div class="text-center">
                <div class="author-box-description">
                  <p id="desc">
                  </p>
                </div>

              </div>
            </div>
          </div>
        </div>
        <div class="col-12 col-md-12 col-lg-8">
          <div class="card">
            <div class="padding-20">
              <ul class="nav nav-tabs" id="myTab2" role="tablist">
                <li class="nav-item">
                  <a class="nav-link active" id="home-tab2" data-toggle="tab" href="#about" role="tab" aria-selected="true">Mis datos</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="profile-tab2" data-toggle="tab" href="#settings" role="tab" aria-selected="false">Editar</a>
                </li>
              </ul>
              <div class="tab-content tab-bordered" id="myTab3Content">
                <div class="tab-pane fade show active" id="about" role="tabpanel" aria-labelledby="home-tab2">
                  <div class="row">
                    <div class="col-md-3 col-6 b-r">
                      <strong>Nombres</strong>
                      <br>
                      <p class="text-muted"><?php echo $_SESSION['nombre']; ?></p>
                    </div>
                    <div class="col-md-3 col-6 b-r">
                      <strong>Teléfono</strong>
                      <br>
                      <p class="text-muted"><?php echo $_SESSION['telefono']; ?></p>
                    </div>
                    <div class="col-md-3 col-6 b-r">
                      <strong>Email</strong>
                      <br>
                      <p class="text-muted"><?php echo $_SESSION['email']; ?></p>
                    </div>
                    <div class="col-md-3 col-6">
                      <strong>Dirección</strong>
                      <br>
                      <p class="text-muted"><?php echo $_SESSION['direccion']; ?></p>
                    </div>
                  </div>
                  <div class="section-title">Sobre mi</div>
                  <p id="bio"></p>
                </div>
                <div class="tab-pane fade" id="settings" role="tabpanel" aria-labelledby="profile-tab2">
                  <form action="" name="formulario" id="formulario" method="POST">
                    <div class="card-header">
                      <h4>Editar Perfil</h4>
                    </div>
                    <div class="card-body">
                      <div class="row">
                        <div class="form-group col-md-12 col-12">
                          <label>Nombre y apellidos</label>
                          <input type="hidden" id="idusuario" name="idusuario" value="<?php echo $_SESSION['idusuario']; ?>">
                          <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>
                      </div>
                      <div class="row">
                        <div class="form-group col-md-6 col-12">
                          <label>Tipo documento</label>
                          <select class="form-control" name="tipo_documento" id="tipo_documento" required>
                            <option value=" DNI">DNI</option>
                            <option value="RUC">RUC</option>
                            <option value="CEDULA">CEDULA</option>
                          </select>
                        </div>
                        <div class="form-group col-md-6 col-12">
                          <label>Número de documento</label>
                          <input type="text" class="form-control" id="num_documento" name="num_documento" required>
                        </div>
                      </div>

                      <div class="row">
                        <div class="form-group col-md-7 col-12">
                          <label>Email</label>
                          <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="form-group col-md-5 col-12">
                          <label>Teléfono</label>
                          <input type="tel" class="form-control" id="telefono" name="telefono" required>
                        </div>
                      </div>

                      <div class="row">
                        <div class="form-group col-md-12 col-12">
                          <label>Dirección</label>
                          <input type="text" class="form-control" id="direccion" name="direccion" required>
                        </div>
                      </div>

                      <div class="row">
                        <div class="form-group col-md-6 col-12">
                          <label>Login</label>
                          <input type="text" class="form-control" id="login" name="login" required>
                        </div>
                        <div class="form-group col-md-6 col-12">
                          <label>Clave</label>
                          <input type="password" class="form-control" id="clave" name="clave">
                        </div>
                      </div>

                      <div class="row">
                        <div class="form-group col-12">
                          <label>Descripción del cargo</label>
                          <textarea class="form-control summernote-simple" name="descripcion" id="descripcion"></textarea>
                        </div>
                      </div>

                      <div class="row">
                        <div class="form-group col-12">
                          <label>Biografía</label>
                          <textarea class="form-control summernote-simple" name="biografia" id="biografia"></textarea>
                        </div>
                      </div>

                      <div class="row">
                        <!-- <div class="form-group col-md-6 col-12">
                          <label for="">Imagen:</label>
                          <input class="form-control filestyle" type="file" name="imagen" id="imagen" data-buttonText="Buscar..." data-buttonName="btn-success" data-iconName="fa fa-folder-open" data-placeholder="Selecciona un imagen">
                          <input type="hidden" name="imagenactual" id="imagenactual">
                          <img src="" alt="" width="150px" height="120" id="imagenmuestra">
                        </div>-->
                        <div class="form-group col-md-6 col-12">
                          <label for="">Imagen:</label>
                          <input type="hidden" id="modoEdicion" value="false">
                          <input class="form-control filestyle" type="file" name="imagen" id="imagen">
                          <input type="hidden" name="imagenactual" id="imagenactual">
                          <img src="" alt="" width="150px" height="120" id="imagenmuestra">
                          <!--<img id="previewHolder" alt="Selecciona una imagen" width="150px" height="120px" style="border-radius:10px;" />-->
                          <span id="previewImagen" class="btn btn-danger btn-sm">X</span>
                        </div>
                      </div>

                    </div>
                    <div class="card-footer text-right">
                      <button class="btn btn-primary" id="btnGuardar">Actualizar</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!--FIN CENTRO-->
    </section>
  </div>
  <?php

  require "footer.php";
  ?>
  <script src="Views/modules/scripts/profile.js"></script>
<?php
}
ob_end_flush();
?>