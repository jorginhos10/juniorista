            <footer class="main-footer">
              <div class="footer-left">
                Copyright &copy; <div class="bullet"></div> CHIPHYSI - <a class="text-success" target="blanck" href="https://compartiendocodigos.com/">CompartiendoCódigos</a>
              </div>
              <div class="footer-right">Versión 1.1.2
              </div>
            </footer>


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
            </body>


            </html>