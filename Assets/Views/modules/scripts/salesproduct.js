var tabla;

//funcion que se ejecuta al inicio
function init() {
  var now = new Date();
  var day = ("0" + now.getDate()).slice(-2);
  var month = ("0" + (now.getMonth() + 1)).slice(-2);
  var today = now.getFullYear() + "-" + month + "-" + day;
  $("#fecha_inicio").val(today);
  $("#fecha_fin").val(today);
  listar();

  $("#fecha_inicio").change(listar);
  $("#fecha_fin").change(listar);
}


//funcion listar
function listar() {
  var fecha_inicio = $("#fecha_inicio").val();
  var fecha_fin = $("#fecha_fin").val();

  tabla = $("#tbllistado")
    .dataTable({
      language: {
        decimal: "",
        emptyTable: "No hay información",
        info: "Mostrando _START_ a _END_ de _TOTAL_ Entradas",
        infoEmpty: "Mostrando 0 to 0 of 0 Entradas",
        infoFiltered: "(Filtrado de _MAX_ total entradas)",
        infoPostFix: "",
        thousands: ",",
        lengthMenu: "Mostrar _MENU_ Entradas",
        loadingRecords: "Cargando...",
        processing: "Procesando...",
        search: "Buscar:",
        zeroRecords: "Sin resultados encontrados",
        paginate: {
          first: "Primero",
          last: "Ultimo",
          next: "Siguiente",
          previous: "Anterior",
        },
      },
      aProcessing: true, //activamos el procedimiento del datatable
      aServerSide: true, //paginacion y filrado realizados por el server
      dom: "Bfrtip", //definimos los elementos del control de la tabla

      buttons: [
        {
          extend: "excelHtml5",
          text: '<i class="fa fa-file-excel-o"></i> Excel',
          titleAttr: "Exportar a Excel",
          sheetName: "VentaArticulos",
          footer:true,
          title:
            "Reporte de Venta de articulos de " +
            fecha_inicio +
            " a " +
            fecha_fin,
        },
        {
          extend: "pdfHtml5",
          text: '<i class="fa fa-file-pdf-o"></i> PDF',
          titleAttr: "Exportar a PDF",
          title:
            "Reporte de Venta de articulos de " +
            fecha_inicio +
            " a " +
            fecha_fin,
          //orientation: 'landscape',
          pageSize: "A4",
          footer:true,
          exportOptions: {
            columns: [0, 1, 2, 3, 4,5],
            //alignment: "center",
          },
          customize: function (doc) {
            doc.content[1].table.widths = ["5%", "15%", "40%", "15%", "10%","15%"];
          },
        },
      ],
      ajax: {
        url: "Controllers/Consult.php?op=listaventasarticulos",
        data: { fecha_inicio: fecha_inicio, fecha_fin: fecha_fin },
        type: "get",
        dataType: "json",
        error: function (e) {
          console.log(e.responseText);
        },
      },
      bDestroy: true,
      iDisplayLength: 20, //paginacion
      //""
      //"order":[[0,"desc"]]//ordenar (columna, orden)
      drawCallback: function () {
        var api = this.api();
        $(api.column(2).footer()).html('TOTALES');
        $(api.column(3).footer()).html(api.column(3).data().sum().toFixed(2));
        $(api.column(4).footer()).html(api.column(4).data().sum());
        $(api.column(5).footer()).html(api.column(5).data().sum().toFixed(2));
      },
    })
    .DataTable();
}

init();
