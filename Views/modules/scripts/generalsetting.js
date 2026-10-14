var tabla;

//funcion que se ejecuta al inicio
function init() {
  mostrar();
  $("#formulario").on("submit", function (e) {
    guardaryeditar(e);
  });

  //cargamos los items al celect categoria
  $("#previewImagen").hide();
}

$("#imagen").filestyle({
  badge: true,
  input: false,
  text: " Seleccionar imagen",
  //placeholder: " Selecciona una imagen",
  btnClass: "btn-info",
  htmlIcon: '<span class="far fa-folder-open"></span> ',
});

//VISTA PREVIA DE LA IMAGEN
function readURL(input) {
  let imgActual = $("#imagenactual").val();
  if (input.files && input.files[0]) {
    var reader = new FileReader();
    reader.onload = function (e) {
      $("#imagenmuestra").attr("src", e.target.result);
    };
    reader.readAsDataURL(input.files[0]);
    $("#previewImagen").show();
  } else {
    //if ($("#modoEdicion").val() === "true") {

    if (imgActual) {
      $("#imagenmuestra").attr("src", "Assets/img/company/" + imgActual);
      //alert("Selecione archivo para ver");
    }

  }
}

$("#imagen").change(function () {
  readURL(this);
});

//MOSTRAR LA OPCION DE QUITAR LA IMAGEN
$("#previewImagen").click(function () {
  $(":file").filestyle("clear");
  readURL($("#imagen"));
  $("#previewImagen").hide();
});
//funcion limpiar
function limpiar() {
  $("#codigo").val("");
  $("#nombre").val("");
  $("#ndocumento").val("");
  $("#documento").val("");
  $("#direccion").val("");
  $("#direccion").val("");
  $("#telefono").val("");
  $("#email").val("");
  // $("#imagenmuestra").attr("src", "");
  $("#imagenactual").val("");
  $("#pais").val("");
  $("#ciudad").val("");
  $("#nombre_impuesto").val("");
  $("#monto_impuesto").val("");
  $("#moneda").val("");
  $("#simbolo").val("");
  $("#id_negocio").val("");
  $(":file").filestyle("clear");
  $("#previewImagen").hide();
  $("#btnGuardar").prop("disabled", false);

}



//funcion para guardaryeditar
function guardaryeditar(e) {
  e.preventDefault(); //no se activara la accion predeterminada
  $("#btnGuardar").prop("disabled", true);
  var formData = new FormData($("#formulario")[0]);

  $.ajax({
    url: "Controllers/Company.php?op=guardaryeditar",
    type: "POST",
    data: formData,
    contentType: false,
    processData: false,

    success: function (datos) {
      swal({
        title: "Registro",
        text: datos,
        icon: "info",
        buttons: {
          confirm: "OK",
        },
      }),
        limpiar();
      mostrar();

    },
  });

}

function mostrar() {
  $.post(
    "Controllers/Company.php?op=mostrar",
    function (data, status) {
      data = JSON.parse(data);
      $("#codigo").val(data.codigo);
      $("#nombre").val(data.nombre);
      $("#ndocumento").val(data.ndocumento);
      $("#documento").val(data.documento);
      $("#direccion").val(data.direccion);
      $("#telefono").val(data.telefono);
      $("#email").val(data.email);
      $("#imagenmuestra").show();
      $("#imagenmuestra").attr("src", "Assets/img/company/" + data.logo);
      $("#imagenactual").val(data.logo);
      $("#pais").val(data.pais);
      $("#ciudad").val(data.ciudad);
      $("#nombre_impuesto").val(data.nombre_impuesto);
      $("#monto_impuesto").val(data.monto_impuesto);
      $("#moneda").val(data.moneda);
      $("#simbolo").val(data.simbolo);
      $("#id_negocio").val(data.id_negocio);
    }
  );
}

init();
