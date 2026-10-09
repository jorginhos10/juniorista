"use strict";
var tabla;

//funcion que se ejecuta al inicio
function init() {
	mostrar_impuesto();
	nombre_impuesto();
	listarArticulos();
	$("#t_pago").hide();

	$("#formulario").on("submit", function (e) {
		guardaryeditar(e);
	});

	$("#formulariocliente").on("submit", function (e) {
		agregarCliente(e);
	});

	//cargamos los items al select cliente
	cargarClientes();

	//cargamos los items al celect comprobantes
	$.post("Controllers/Sell.php?op=selectComprobante", function (c) {
		//alert(c);
		$("#tipo_comprobante").val("Ticket");
		$("#tipo_comprobante").html(c);
		//$("#tipo_comprobante").selectpicker("refresh");
	});

	//obtenemos la fecha actual
	var now = new Date();
	var day = ("0" + now.getDate()).slice(-2);
	var month = ("0" + (now.getMonth() + 1)).slice(-2);
	var today = now.getFullYear() + "-" + month + "-" + day;
	$("#fecha_hora").val(today);
}
//funcion limpiar
function limpiar() {
	$("#idventa").val("");
	$("#idcliente").val("");
	$("#cliente").val("");
	$("#serie_comprobante").val("");
	$("#num_comprobante").val("");
	$("#impuesto").val("");
	$("#total_venta").val("");
	$(".filas").remove();
	$("#total").html("0");
	$("#tpagado").val("");
	//marcamos el primer tipo_documento
	//$("#tipo_comprobante").selectpicker("refresh");
	//$("#idcliente").selectpicker("refresh");

	$("#nombre").val("");
	$("#num_documento").val("");
	$("#num_transac").val("");
	$("#tipo_pago").val("");
	$("#direccion").val("");
	$("#telefono").val("");
	$("#email").val("");
	$("#idpersona").val("");
	$("#Modalcliente").modal("hide");
	$("#aplicar_impuesto").prop("checked", false);
	$("#t_pago").hide();
}

function ShowTipopago() {
	var t_pago = $("#tipo_pago").val();
	if (t_pago == "Pago en efectivo" || t_pago == "Efectivo") {
		$("#t_pago").hide();
		$("#num_transac").val("");
	} else {
		$("#t_pago").show();
		$("#num_transac").val("");
	}
}

//aplicar impuesto
$("#aplicar_impuesto").change(function () {
	if ($("#aplicar_impuesto").is(":checked")) {
		mostrar_impuesto();
	} else {
		mostrar_impuesto();
	}
});

//mostramos el impuesto
var no_aplica = 0;
function mostrar_impuesto() {
	$.ajax({
		url: "Controllers/Company.php?op=mostrar_impuesto",
		type: "get",
		dataType: "json",
		success: function (i) {
			var impuesto = i;
			var sin_imp = 0;
			if ($("#aplicar_impuesto").is(":checked")) {
				$("#impuesto").val(impuesto);
				no_aplica = impuesto;
				calcularTotales();
				nombre_impuesto();
			} else {
				$("#impuesto").val(sin_imp);
				no_aplica = 0;
				calcularTotales();
				nombre_impuesto();
			}
		},
	});
}

//declaramos variables necesarias para trabajar con las compras y sus detalles
var cont = 0;
var detalles = 0;
$("#btnGuardar").hide();

//_______________________________________________________________________________________________

function listarArticulos() {
	tabla = $("#tblarticulos")
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
			buttons: [],
			ajax: {
				url: "Controllers/Sell.php?op=listarArticulos",
				type: "get",
				dataType: "json",
				error: function (e) {
					console.log(e.responseText);
				},
			},
			bDestroy: true,
			iDisplayLength: 10, //paginacion
			order: [[0, "desc"]], //ordenar (columna, orden)
		})
		.DataTable();
	//alert( 'Rows '+tabla.rows( '.selected' ).count()+' are selected' );
}
//funcion para guardaryeditar
function guardaryeditar(e) {
	e.preventDefault(); //no se activara la accion predeterminada
	//$("#btnGuardar").prop("disabled",true);
	var formData = new FormData($("#formulario")[0]);

	$.ajax({
		url: "Controllers/Sell.php?op=guardaryeditar",
		type: "POST",
		data: formData,
		contentType: false,
		processData: false,

		success: function (datos) {
			var tabla = $("#tbllistado").DataTable();
			swal({
				title: "Registro",
				text: datos,
				icon: "info",
				buttons: {
					confirm: "OK",
				},
			}),
				tabla.ajax.reload();
		},
	});
	//init();
	limpiar();
	listarArticulos();
}

//funcion para anular
function anular(idventa) {
	swal({
		title: "Anular?",
		text: "Esá seguro de anular venta?",
		icon: "warning",
		buttons: {
			cancel: "No, cancelar",
			confirm: "Si, anular",
		},
		//buttons: true,
		dangerMode: true,
	}).then((willDelete) => {
		if (willDelete) {
			$.post(
				"Controllers/Sell.php?op=anular",
				{ idventa: idventa },
				function (e) {
					swal(e, "Desactivado!", {
						icon: "success",
					});
					var tabla = $("#tbllistado").DataTable();
					tabla.ajax.reload();
				}
			);
		}
	});
}

function agregarDetalle(
	idarticulo,
	articulo,
	precio_compra,
	precio_venta,
	cantidad
) {
	var stock = cantidad;
	var numero_cantidad = 1;
	var descuento = 0;

	if (idarticulo != "") {
		var subtotal = cantidad * precio_venta;
		var fila =
			'<tr class="filas" id="fila' +
			cont +
			'">' +
			'<td class=""><button type="button" id="del" class="btn btn-danger btn-sm del" onclick="eliminarDetalle(' +
			cont +
			')"><i class="fas fa-times"></i></button></td>' +
			'<td class="col-xs-6"><input style="width : 70px;" type="hidden" name="idarticulo[]" value="' +
			idarticulo +
			'"><input style="width : 70px;" type="hidden" name="precio_compra[]" value="' +
			precio_compra +
			'">' +
			articulo +
			'<td class="col-xs-1"><input class="form-control form-control-sm" style="width : 70px;" type="number" min="1" max="' +
			stock +
			'" onchange="ver_stock(this.value,' +
			idarticulo +
			"," +
			cantidad +
			"," +
			cont +
			",'" +
			articulo +
			'\')" name="cantidad[]" id="cantidad' +
			cont +
			'" value="' +
			numero_cantidad +
			'"></td>' +
			'<td class="col-xs-1"><input class="form-control form-control-sm" style="width : 70px;" type="number" min="1" step="0.01" onchange="modificarSubtotales()" name="precio_venta[]" id="precio_venta[]" value="' +
			precio_venta +
			'"></td>' +
			'<td class="col-xs-1"><input class="form-control controlRemotoJ form-control-sm" style="width : 70px;" type="number" min="0" step="0.01" onchange="modificarSubtotales()" name="descuento[]" value="' +
			descuento +
			'"></td>' +
			'<td class="col-xs-1"><span id="subtotal' +
			cont +
			'" name="subtotal">' +
			subtotal +
			"</span></td>" +
			"</tr>";
		var product = null;
		var shelf = null;
		var status = null;

		//submit

		cont++;
		detalles++;
		$("#detalles").append(fila);
		modificarSubtotales();
	} else {
		alert("error al ingresar el detalle, revisar las datos del articulo ");
	}
}

//borrar filas del datables
function borrar_filas() {
	$('#tblarticulos tbody tr[role="row"] #addetalle').prop("disabled", false);
	for (let i = 0; i < $(".filas").length; i++) {
		const element = $('input[name="idarticulo[]"]').get(i);
		for (let f = 0; f < $('#tblarticulos tbody tr[role="row"]').length; f++) {
			const button = $('#tblarticulos tbody tr[role="row"] #addetalle').get(f);
			if (button["name"] === element["value"]) {
				button["disabled"] = true;
			}
		}
	}
}

var boton = true;
//esta funcion valida la cantidad a vender con el stock
function ver_stock(valor, idarticulo, cantidad, vfila, articulo) {
	$.post(
		"Controllers/Product.php?op=validarSotck",
		{ idarticulo: idarticulo },
		function (data, status) {
			data = JSON.parse(data);
			var stockVenta = data.stock;
			//console.log(stockVenta);
			if (valor > parseInt(stockVenta)) {
				var msj = "El sotck maximo disponible es: " + cantidad;
				swal({
					title: "Error",
					text: msj + " ",
					icon: "error",
					buttons: {
						confirm: "OK",
					},
				}),
					validar(vfila);
				//$("#btnGuardar").hide();
				boton = false;
			} else {
				modificarSubtotales();
				$('input[name="cantidad[]"]').prop("disabled", false);
				//$("#btnGuardar").show();
				boton = true;
			}
		}
	);
}
//VALIDAR CAMPOS
function validar(vfila) {
	//$("#btnGuardar").hide();
	//$('input[name="cantidad[]"]').prop("disabled", true);

	for (let i = 0; i < $(".filas").length; i++) {
		//var elemento = $("#cantidad" + vfila).get(i);
		var v = $("#cantidad" + vfila).attr("id");
		console.log(v);
		//var art = elemento["id"].substr(8, 3);
		var art = v.substr(8, 3);

		if (parseInt(vfila) === parseInt(art)) {
			boton = false;
			console.log(vfila + " " + art);
			//$("#cantidad" + art).prop("disabled", false);
			$("#cantidad" + art).val("1");
			//$("#cantidad" + art).prop("style", "width:50px, background:orange");
		} else {
			boton = true;
		}
	}
}

function modificarSubtotales() {
	var cant = document.getElementsByName("cantidad[]");
	var prev = document.getElementsByName("precio_venta[]");
	var desc = document.getElementsByName("descuento[]");
	var sub = document.getElementsByName("subtotal");

	for (var i = 0; i < cant.length; i++) {
		var inpV = cant[i];
		var inpP = prev[i];
		var inpS = sub[i];
		var des = desc[i];

		inpS.value = inpV.value * inpP.value - des.value;
		document.getElementsByName("subtotal")[i].innerHTML = inpS.value.toFixed(2);
	}

	calcularTotales();
}

function calcularTotales() {
	"use strict";
	var sub = document.getElementsByName("subtotal");
	var total = 0.0;
	var total_monto = 0.0;
	var igv_dec = 0.0;
	var igv = 0.0;
	var simbolo = "";

	for (var i = 0; i < sub.length; i++) {
		total += document.getElementsByName("subtotal")[i].value;
		igv = parseFloat(total) * (no_aplica / 100);
		total_monto = parseFloat(total) + parseFloat(igv);
		igv_dec = parseFloat(igv).toFixed(2);
	}
	$.ajax({
		url: "Controllers/Company.php?op=mostrar_simbolo",
		type: "get",
		dataType: "json",
		success: function (sim) {
			simbolo = sim;
			$("#total").html(simbolo + " " + total.toFixed(2));
			$("#total_venta").val(total_monto.toFixed(2));

			$("#most_total").html(simbolo + total_monto.toFixed(2));
			$("#most_imp").html(simbolo + parseFloat(igv_dec).toFixed(2));
			var tpagado = $("#tpagado").val();
			var totalvuelto = 0;

			if (tpagado > total_monto) {
				totalvuelto = tpagado - total_monto;
				$("#vuelto").html(simbolo + " " + parseFloat(totalvuelto).toFixed(2));
			} else {
				totalvuelto = 0.0;
				$("#vuelto").html(simbolo + " " + parseFloat(totalvuelto).toFixed(2));
			}

			evaluar();
		},
	});
	borrar_filas();
}
function nombre_impuesto() {
	$.ajax({
		url: "Controllers/Company.php?op=nombre_impuesto",
		type: "get",
		dataType: "json",
		success: function (n) {
			var nomp = n;
			var valor_impuesto = no_aplica;
			$("#valor_impuesto").html(nomp + " " + valor_impuesto + "%");
		},
	});
}

function evaluar() {
	if (detalles > 0) {
		$("#btnGuardar").show();
	} else {
		$("#btnGuardar").hide();
		cont = 0;
	}
}
/*function evaluar() {
	if (detalles > 0) {
		if (boton === true) {
			$("#btnGuardar").show();
		} else {
			$("#btnGuardar").hide();
		}
		//valida_sotck();
		//validar_campos();
	} else {
		$("#btnGuardar").hide();
		//valida_sotck();
		//validar_campos();
		cont = 0;
	}
}*/

function eliminarDetalle(indice) {
	$("#fila" + indice).remove();
	calcularTotales();
	detalles = detalles - 1;
	//evaluar();
}

//funcion para guardar nuevo cliente
function agregarCliente(e) {
	$("#Modalcliente").modal("show");
	e.preventDefault(); //no se activara la accion predeterminada
	$("#btnGuardarcliente").prop("disabled", true);
	var formData = new FormData($("#formulariocliente")[0]);

	$.ajax({
		url: "Controllers/Person.php?op=guardaryeditar",
		type: "POST",
		data: formData,
		contentType: false,
		processData: false,
		success: function (datos) {
			var tabla = $("#tbllistado").DataTable();
			swal({
				title: "Registro",
				text: datos,
				icon: "info",
				buttons: {
					confirm: "OK",
				},
			}),
				mostrarform(false);
			tabla.ajax.reload();
		},
	});

	limpiar();
	cargarClientes();
}

function cargarClientes() {
	$.post("Controllers/Sell.php?op=selectCliente", function (r) {
		$("#idcliente").html(r);
		//$("#idcliente").selectpicker("refresh");
	});
}

init();
