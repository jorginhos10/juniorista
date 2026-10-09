"use strict";
var tabla;

//funcion que se ejecuta al inicio
function init() {
	$("#formulario").on("submit", function (e) {
		guardaryeditar(e);
	});

	//cargamos los items al select proveedor
	$.post("Controllers/Buy.php?op=selectProveedor", function (r) {
		$("#idproveedor").html(r);
		//$("#idproveedor").selectpicker("refresh");
	});
	listarArticulos();
	url_get();
}

function url_get() {
	var query_string = new URLSearchParams(window.location.search);
	let idingreso = query_string.get("id");
	mostrar(idingreso);
}

//funcion limpiar
function limpiar() {
	$("#idproveedor").val("");
	$("#proveedor").val("");
	$("#serie_comprobante").val("");
	$("#num_comprobante").val("");
	$("#impuesto").val("");

	$("#total_compra").val("");
	$(".filas").remove();
	$("#total").html("0");

	//obtenemos la fecha actual
	var now = new Date();
	var day = ("0" + now.getDate()).slice(-2);
	var month = ("0" + (now.getMonth() + 1)).slice(-2);
	var today = now.getFullYear() + "-" + month + "-" + day;
	$("#fecha_hora").val(today);
}

//cancelar form
function cancelarform() {
	limpiar();
	$(location).attr("href", "buy");
}

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
				url: "Controllers/Buy.php?op=listarArticulosEditar",
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
}
//funcion para guardaryeditar
function guardaryeditar(e) {
	e.preventDefault(); //no se activara la accion predeterminada
	//$("#btnGuardar").prop("disabled",true);
	var formData = new FormData($("#formulario")[0]);

	$.ajax({
		url: "Controllers/Buy.php?op=guardaryeditar",
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

	limpiar();
}

function mostrar(idingreso) {
	$.post(
		"Controllers/Buy.php?op=mostrar",
		{ idingreso: idingreso },
		function (data, status) {
			data = JSON.parse(data);

			$("#idproveedor").val(data.idproveedor);
			$("#tipo_comprobante").val(data.tipo_comprobante);
			$("#serie_comprobante").val(data.serie_comprobante);
			$("#num_comprobante").val(data.num_comprobante);
			$("#fecha_hora").val(data.fecha);
			$("#impuesto").val(data.impuesto);
			$("#idingreso").val(data.idingreso);
		}
	);
	$.post(
		"Controllers/Buy.php?op=listarDetalle_editar&id=" + idingreso,
		function (r) {
			var data = JSON.parse(r);
			// let impuesto = data[4].Impuesto;
			//let impuesto = data.forEach((element) =>console.log(element["Impuesto"]));

			let i = 0;
			while (i < data.length) {
				//$("#detalles").html(data[i]);
				let idarticulo = data[i].Idarticulo;
				let articulo = data[i].Articulo;
				let precio_compra = data[i].Pcompra;
				let precio_venta = data[i].Pventa;
				let cantidad = data[i].Cantidad;

				let op = 2;
				agregarDetalle(
					idarticulo,
					articulo,
					precio_compra,
					precio_venta,
					cantidad,
					op
				);
				i++;
			}
		}
	);
}

//declaramos variables necesarias para trabajar con las compras y sus detalles
var cont = 0;
var detalles = 0;
$("#btnGuardar").hide();

function agregarDetalle(
	idarticulo,
	articulo,
	precio_compra,
	precio_venta,
	cantidad,
	op
) {
	var numero_cantidad;
	var nprecio_compra;
	var nprecio_venta;
	var numero_cant;
	op === 2 ? (numero_cantidad = cantidad) : (numero_cantidad = 1);
	op === 2 ? (nprecio_compra = precio_compra) : (nprecio_compra = 1);
	op === 2 ? (nprecio_venta = precio_venta) : (nprecio_venta = 1);

	op === 2 ? (numero_cant = cantidad) : (numero_cant = 0);

	if (idarticulo != "") {
		var subtotal = cantidad * nprecio_compra;
		var fila =
			'<tr class="filas" id="fila' +
			cont +
			'">' +
			'<td><button type="button" class="btn btn-danger btn-sm" onclick="eliminarDetalle(' +
			cont +
			"," +
			op +
			"," +
			idarticulo +
			"," +
			numero_cantidad +
			')">X</button></td>' +
			'<td><input type="hidden" name="idarticulo[]" value="' +
			idarticulo +
			'"><input type="hidden" name="nstock[]" value="' +
			numero_cant +
			'"><input type="hidden" name="nuevostock[]" id="nuevostock[]">' +
			articulo +
			"</td>" +
			'<td><input type="number" onchange="modificarSubtotales()" name="cantidad[]" id="cantidad[]" value="' +
			numero_cantidad +
			'"></td>' +
			'<td><input type="number" step="0.01" onchange="modificarSubtotales()" name="precio_compra[]" id="precio_compra[]" value="' +
			nprecio_compra +
			'"></td>' +
			'<td><input type="number" step="0.01" name="precio_venta[]" value="' +
			nprecio_venta +
			'"></td>' +
			'<td><span id="subtotal' +
			cont +
			'" name="subtotal">' +
			subtotal +
			"</span></td>" +
			"</tr>";
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

function modificarSubtotales() {
	var cant = document.getElementsByName("cantidad[]");
	var prec = document.getElementsByName("precio_compra[]");
	var sub = document.getElementsByName("subtotal");
	var nstock = document.getElementsByName("nstock[]");
	var nuevostock = document.getElementsByName("nuevostock[]");

	for (var i = 0; i < cant.length; i++) {
		var inpC = cant[i];
		var inpP = prec[i];
		var inpS = sub[i];
		var ns = nstock[i];
		var news = nuevostock[i];

		inpS.value = inpC.value * inpP.value;
		news.value = parseFloat(inpC.value) - parseFloat(ns.value);
		document.getElementsByName("subtotal")[i].innerHTML = inpS.value.toFixed(2);
		$("#nuevostock[" + i + "]").val(news.value);
	}

	calcularTotales();
}

function calcularTotales() {
	var sub = document.getElementsByName("subtotal");
	var total = 0.0;

	for (var i = 0; i < sub.length; i++) {
		total += document.getElementsByName("subtotal")[i].value;
		var igv = total * ($("#impuesto").val() / 100);
		var total_monto = total + igv;
		var igv_dec = igv.toFixed(2);
	}

	$("#total").html(total.toFixed(2));
	$("#total_compra").val(parseFloat(total_monto).toFixed(2));
	$("#most_total").html(parseFloat(total_monto).toFixed(2));
	$("#most_imp").html(igv_dec);

	evaluar();
	borrar_filas();
}

function evaluar() {
	if (detalles > 0) {
		$("#btnGuardar").show();
	} else {
		$("#btnGuardar").hide();
		cont = 0;
	}
}

//FUNCION PARA ACTUALIZAR EL STOCK DEL ARTICULO A QUITAR
function updateStock(indice, idarticulo, numero_cantidad) {
	swal({
		title: "Eliminar?",
		text: "Si quita este articulos su stock disminuirá, está seguro de eliminar de la lista..?",
		icon: "warning",
		buttons: {
			cancel: "No, cancelar",
			confirm: "Si, eliminar",
		},
		//buttons: true,
		dangerMode: true,
	}).then((willDelete) => {
		if (willDelete) {
			$.post(
				"Controllers/Product.php?op=quitarArticulo",
				{ idarticulo: idarticulo, cantidad: numero_cantidad },
				function (e) {
					swal(e, "Eliminado!", {
						icon: "success",
					});
					$("#fila" + indice).remove();
					calcularTotales();
					detalles = detalles - 1;
					var tabla = $("#tblarticulos").DataTable();
					tabla.ajax.reload();
				}
			);
		}
	});
}

function eliminarDetalle(indice, op, idarticulo, numero_cantidad) {
	if (op === 2) {
		updateStock(indice, idarticulo, numero_cantidad);
	} else {
		$("#fila" + indice).remove();
		calcularTotales();
		detalles = detalles - 1;
	}
}

init();
