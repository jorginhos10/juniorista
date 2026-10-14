var tabla;

//funcion que se ejecuta al inicio
function init() {
	var idusuario = $("#idusuario").val();
	mostrar(idusuario);
	$("#formulario").on("submit", function (e) {
		guardaryeditar(e);
	});

	$("#imagenmuestra").attr("src", "Assets/img/users/user-default.jpg");
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
		if ($("#modoEdicion").val() === "true") {

			if (imgActual) {
				$("#imagenmuestra").attr("src", "Assets/img/users/" + imgActual);
				//alert("Selecione archivo para ver");
			}
		} else {
			$("#imagenmuestra").attr("src", "Assets/img/users/user-default.jpg");
		}

	}
}

$("#imagen").change(function () {
	readURL(this);
});

//MOSTRAR LA OPCION DE QUITAR LA IMAGEN
$("#previewImagen").click(function () {
	$(":file").filestyle("clear");
	//$(":file").filestyle("destroy");
	readURL($("#imagen"));
	//$("#imagenmuestra").attr("src", "Assets/img/products/default.png");
	$("#previewImagen").hide();
});

//funcion limpiar
function limpiar() {
	$("#nombre").val("");
	$("#num_documento").val("");
	$("#direccion").val("");
	$("#telefono").val("");
	$("#email").val("");
	$("#cargo").val("");
	$("#login").val("");
	$("#clave").val("");
	$("#imagenmuestra").attr("src", "");
	$("#imagenactual").val("");
	var idusuario = $("#idusuario").val();
	mostrar(idusuario);
}

//funcion para guardaryeditar
function guardaryeditar(e) {
	e.preventDefault(); //no se activara la accion predeterminada
	$("#btnGuardar").prop("disabled", true);
	var formData = new FormData($("#formulario")[0]);

	$.ajax({
		url: "Controllers/User.php?op=editarPerfil",
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
			});
			$("#btnGuardar").prop("disabled", false);
			limpiar();
		},
	});
}

function mostrar(idusuario) {
	$("#modoEdicion").val("true");
	var miInput = $("#imagen");
	readURL(miInput);
	$.post(
		"Controllers/User.php?op=mostrar",
		{ idusuario: idusuario },
		function (data, status) {
			data = JSON.parse(data);
			$("#nombre").val(data.nombre);
			$("#tipo_documento").val(data.tipo_documento);
			$("#num_documento").val(data.num_documento);
			$("#direccion").val(data.direccion);
			$("#telefono").val(data.telefono);
			$("#biografia").val(data.biografia);
			$("#bio").html(data.biografia);
			$("#desc").html(data.descripcion);
			$("#descripcion").val(data.descripcion);
			$("#email").val(data.email);
			$("#cargo").val(data.cargo);
			$("#login").val(data.login);

			$("#imagenmuestra").show();
			$("#imagenmuestra").attr("src", "Assets/img/users/" + data.imagen);
			$("#imagenactual").val(data.imagen);
			$("#idusuario").val(data.idusuario);

		}
	);
}

init();
