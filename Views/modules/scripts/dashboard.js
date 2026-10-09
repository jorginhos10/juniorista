"use strict";
function init() {
	inventario();
	cuadros1();
	cuadros2();
	compra10dias();
	venta12meses();
}

function inventario(){
	$("#jorginhos").html("hola ya estoy aqui");
}

function cuadros1() {
	$.post("Controllers/Dashboard.php?op=cuadros1", function (data, status) {
		data = JSON.parse(data);
		//console.log(data.totalcomprahoy);
		//COMPRAS
		$("#tcomprahoy").html(data.totalcomprahoy);
		//VENTAS
		$("#tventahoy").html(data.totalventahoy);
		//CLIENTES
		$("#tclientes").html(data.cantidadclientes);
		//PROVEEDORES
		$("#tproveedores").html(data.cantidadproveedores);

	});
}
function cuadros2() {
	$.post("Controllers/Dashboard.php?op=cuadros2", function (data, status) {
		data = JSON.parse(data);
		//console.log(data.totalcomprahoy);
		//CATEGORIAS
		$("#tcategorias").html(data.cantidadcategorias);
		//ALAMACEN
		$("#tarticulos").html(data.cantidadarticulos);
	});
}
//COMPRA DE LOS ULTIMOS 10 DIAS
function compra10dias() {
	$.post("Controllers/Dashboard.php?op=compras10dias", function (data, status) {
		data = JSON.parse(data);
		var ctx = document.getElementById("compra10dias").getContext("2d");
		var myChart = new Chart(ctx, {
			type: "bar",
			data: {
				labels: data.fechas,
				datasets: [
					{
						label: "Compras",
						data: data.totales,
						//borderWidth: 2,
						backgroundColor: [
							"#fc544b",
							"#F4D03F",
							"#63ed7a",
							"#1262F7",
							"#ffa426",
							"#6777ef",
							"#fc544b",
							"#F4D03F",
							"#63ed7a",
							"#1262F7",
							"#ffa426",
							"#6777ef",
						],
						//borderColor: "#6777ef",
						//borderWidth: 2.5,
						//pointBackgroundColor: "#ffffff",
						//pointRadius: 4,
					},
				],
			},
			options: {
				legend: {
					display: true,
				},
				scales: {
					yAxes: [
						{
							gridLines: {
								drawBorder: true,
								color: "#f2f2f2",
							},
							ticks: {
								beginAtZero: true,
								stepSize: 5000,
								fontColor: "#9aa0ac", // Font Color
							},
						},
					],
					xAxes: [
						{
							ticks: {
								display: true,
							},
							gridLines: {
								display: true,
							},
						},
					],
				},
			},
		});
	});
}

//VENTAS DE LOS ULTIMOS 12 MESES
function venta12meses() {
	$.post("Controllers/Dashboard.php?op=ventas12meses", function (data, status) {
		data = JSON.parse(data);
		var ctx = document.getElementById("venta12meses").getContext("2d");
		var myChart = new Chart(ctx, {
			type: "bar",
			data: {
				labels: data.fechas,
				datasets: [
					{
						label: "Ventas",
						data: data.totales,
						//borderWidth: 2,
						backgroundColor: [
							"#fc544b",
							"#F4D03F",
							"#63ed7a",
							"#1262F7",
							"#ffa426",
							"#6777ef",
							"#fc544b",
							"#F4D03F",
							"#63ed7a",
							"#1262F7",
							"#ffa426",
							"#6777ef",
						],
						//borderColor: "#6777ef",
						//borderWidth: 2.5,
						//pointBackgroundColor: "#ffffff",
						//pointRadius: 4,
					},
				],
			},
			options: {
				legend: {
					display: true,
				},
				scales: {
					yAxes: [
						{
							gridLines: {
								drawBorder: true,
								color: "#f2f2f2",
							},
							ticks: {
								beginAtZero: true,
								stepSize: 5000,
								fontColor: "#9aa0ac", // Font Color
							},
						},
					],
					xAxes: [
						{
							ticks: {
								display: true,
							},
							gridLines: {
								display: true,
							},
						},
					],
				},
			},
		});
	});
}

init();
