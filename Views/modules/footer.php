            <footer class="main-footer">
              <div class="footer-left">
                Copyright &copy; <div class="bullet"></div> CHIPHYSI - <a class="text-success" target="blanck" href="https://compartiendocodigos.com/">CompartiendoCódigos</a>
              </div>
              <div class="footer-right">Versión 1.1.2
              </div>
            </footer>

            <!-- Modal: stock del producto en todas las veterinarias -->
            <div class="modal fade" id="modalStockVeterinarias" tabindex="-1" role="dialog" aria-hidden="true">
              <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title">Stock en veterinarias</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                      <span aria-hidden="true">&times;</span>
                    </button>
                  </div>
                  <div class="modal-body">
                    <input type="text" id="buscarStockVet" class="form-control" placeholder="Escribe el nombre del producto..." autocomplete="off">
                    <div id="resultadoStockVet" class="table-responsive mt-3"></div>
                  </div>
                </div>
              </div>
            </div>


            <script src="Assets/js/jquery.min.js"></script>
            <!-- General JS Scripts -->
            <script src="Assets/js/app.min.js"></script>
            <!-- JS Libraies -->

            <!-- JS Libraies -->
            <script src="Assets/bundles/sweetalert/sweetalert.min.js"></script>
            <!-- DATATABLES -->
            <script src="Assets/bundles/datatables/datatables.min.js"></script>
            <script src="Assets/bundles/datatables/DataTables-1.10.16/js/dataTables.bootstrap4.min.js"></script>
            <script src="Assets/bundles/datatables/export-tables/dataTables.buttons.min.js"></script>
            <script src="Assets/bundles/datatables/export-tables/buttons.flash.min.js"></script>
            <script src="Assets/bundles/datatables/export-tables/jszip.min.js"></script>
            <script src="Assets/bundles/datatables/export-tables/pdfmake.min.js"></script>
            <script src="Assets/bundles/datatables/export-tables/vfs_fonts.js"></script>
            <script src="Assets/bundles/datatables/export-tables/buttons.print.min.js"></script>

            <!-- Template JS File -->
            <script src="Assets/js/scripts.js?t=<?php echo time(); ?>"></script>

            <script type="text/javascript" src="Assets/bundles/filestyle/bootstrap-filestyle.min.js"> </script>
            <script src="Assets/bundles/select2/dist/js/select2.full.min.js"></script>

            <script>
              jQuery.fn.dataTable.Api.register('sum()', function() {
                return this.flatten().reduce(function(a, b) {
                  if (typeof a === 'string') {
                    a = a.replace(/[^\d.-]/g, '') * 1;
                  }
                  if (typeof b === 'string') {
                    b = b.replace(/[^\d.-]/g, '') * 1;
                  }
                  return a + b;
                }, 0);
              });
            </script>

            <script>
              (function() {
                var temporizador = null;
                var $resultado = $("#resultadoStockVet");

                function escapar(texto) {
                  return $("<div>").text(texto).html();
                }

                function buscar(termino) {
                  if (termino.length < 2) {
                    $resultado.html('<p class="text-muted">Escribe al menos 2 letras.</p>');
                    return;
                  }
                  $resultado.html('<p class="text-muted">Buscando...</p>');
                  $.getJSON("Controllers/StockVeterinarias.php", { q: termino }, function(data) {
                    if (!data.productos.length) {
                      $resultado.html('<p class="text-muted">No se encontró el producto en ninguna veterinaria.</p>');
                      return;
                    }
                    var html = '<table class="table table-striped table-sm"><thead><tr><th>Producto</th>';
                    data.veterinarias.forEach(function(vet) {
                      html += '<th class="text-center">' + escapar(vet) + '</th>';
                    });
                    html += '</tr></thead><tbody>';
                    data.productos.forEach(function(p) {
                      html += '<tr><td>' + escapar(p.nombre) + '</td>';
                      data.veterinarias.forEach(function(vet) {
                        var cantidad = p.stock[vet];
                        html += '<td class="text-center' + (cantidad > 0 ? '' : ' text-danger') + '">' + cantidad + '</td>';
                      });
                      html += '</tr>';
                    });
                    html += '</tbody></table>';
                    if (data.errores && data.errores.length) {
                      html += '<p class="text-warning small">Sin conexión con: ' + data.errores.map(escapar).join(", ") + ' (se muestra 0).</p>';
                    }
                    $resultado.html(html);
                  }).fail(function() {
                    $resultado.html('<p class="text-danger">Error al consultar el stock.</p>');
                  });
                }

                $("#buscarStockVet").on("input", function() {
                  var termino = $.trim(this.value);
                  clearTimeout(temporizador);
                  temporizador = setTimeout(function() { buscar(termino); }, 300);
                });

                $("#modalStockVeterinarias").on("shown.bs.modal", function() {
                  $("#buscarStockVet").trigger("focus");
                });
              })();
            </script>
            </body>


            </html>