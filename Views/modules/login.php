 <!DOCTYPE html>
 <html lang="en">

 <head>
     <meta charset="UTF-8">
     <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
     <title>Bendey</title>
     <!-- General CSS Files -->
     <link rel="stylesheet" href="Assets/css/app.min.css">
     <!-- Template CSS -->
     <link rel="stylesheet" href="Assets/css/style.css">
     <link rel="stylesheet" href="Assets/css/components.css">
     <!-- Custom style CSS -->
     <link rel="stylesheet" href="Assets/css/custom.css">
     <link rel='shortcut icon' type='image/x-icon' href='Assets/img/favicon.ico' />
 </head>

 <body>
     <div class="loader"></div>
     <div id="app" class="login-page">
         <section class="section">
             <div class="login-form">
                 <div class="logo"></div>
                 <h2>Iniciar sesión</h2>
                 <form method="POST" action="" novalidate="" id="formAcceso" autocomplete="off">

                     <label for="name">Usuario(*)</label>
                     <input autocomplete="off" id="nombre" type="text" name="nombre" tabindex="1" required autofocus
                         placeholder="usuario">

                     <label for="password" class="control-label">Contraseña(*)</label>
                     <input autocomplete="off" id="clave" type="password" name="clave" tabindex="2" required
                         placeholder="contraseña">

                     <button type="submit" class="" tabindex="4">
                         Iniciar sesion
                     </button>

                 </form>
                 <p class="tagline">Sistema de Compras y Ventas</p>
             </div>
         </section>
     </div>

     <script src="https://code.jquery.com/jquery-3.6.0.slim.js"
         integrity="sha256-HwWONEZrpuoh951cQD1ov2HUK5zA5DwJ1DNUXaM6FsY=" crossorigin="anonymous"></script>

     <script src="Views/modules/scripts/login.js"></script>

     <!-- General JS Scripts -->
     <script src="Assets/js/app.min.js"></script>
     <!-- JS Libraies -->
     <!-- Page Specific JS File -->
     <!-- Template JS File -->
     <script src="Assets/js/scripts.js"></script>
     <!-- Custom JS File -->
     <script src="Assets/js/custom.js"></script>
     <script src="Assets/bundles/sweetalert/sweetalert.min.js"></script>
 </body>


 </html>