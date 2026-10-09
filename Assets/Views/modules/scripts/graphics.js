"use strict";
function init() {
	compras_grafica();
	ventas_grafica();
	resumen_compras();
	resumen_ventas();

	cuadro_1();
	cuadro_2();
	cuadro_3();
}

function compras_grafica() {
	$.post(
		"Controllers/Graphics.php?op=compras_grafica",
		function (data, status) {
			data = JSON.parse(data);
			//console.log(data.fechas);

			var ctx = document.getElementById("compras_grafica");
			if (ctx) {
				ctx.height = 150;
				var myChart = new Chart(ctx, {
					type: "line",
					data: {
						labels: data.fechas,
						type: "line",
						defaultFontFamily: "Poppins",
						datasets: [
							{
								label: "Compras",
								data: data.totales,
								backgroundColor: "transparent",
								borderColor: "#f96332",
								borderWidth: 2,
								pointStyle: "circle",
								pointRadius: 3,
								pointBorderColor: "transparent",
								pointBackgroundColor: "#f96332",
							},
						],
					},
					options: {
						responsive: true,
						tooltips: {
							mode: "index",
							titleFontSize: 12,
							titleFontColor: "#000",
							bodyFontColor: "#000",
							backgroundColor: "#fff",
							titleFontFamily: "Poppins",
							bodyFontFamily: "Poppins",
							cornerRadius: 3,
							intersect: false,
						},
						legend: {
							display: false,
							labels: {
								usePointStyle: true,
								fontFamily: "Poppins",
							},
						},
						scales: {
							xAxes: [
								{
									display: true,
									gridLines: {
										display: false,
										drawBorder: false,
									},
									scaleLabel: {
										display: true,
										labelString: "Fecha",
									},
									ticks: {
										fontFamily: "Poppins",
										fontColor: "#9aa0ac", // Font Color
									},
								},
							],
							yAxes: [
								{
									display: true,
									gridLines: {
										display: true,
										drawBorder: false,
									},
									scaleLabel: {
										display: true,
										labelString: "Total",
										fontFamily: "Poppins",
									},
									ticks: {
										fontFamily: "Poppins",
										fontColor: "#9aa0ac", // Font Color
									},
								},
							],
						},
						title: {
							display: false,
							text: "Normal Legend",
						},
					},
				});
			}
		}
	);
}

function ventas_grafica() {
	$.post("Controllers/Graphics.php?op=ventas_grafica", function (data, status) {
		data = JSON.parse(data);
		// console.log(data.fechas);

		var ctx = document.getElementById("ventas_grafica");
		if (ctx) {
			ctx.height = 150;
			var myChart = new Chart(ctx, {
				type: "line",
				data: {
					labels: data.fechas,
					type: "line",
					defaultFontFamily: "Poppins",
					datasets: [
						{
							label: "Ventas",
							data: data.totales,
							backgroundColor: "transparent",
							borderColor: "#2ECC71",
							borderWidth: 2,
							pointStyle: "circle",
							pointRadius: 3,
							pointBorderColor: "transparent",
							pointBackgroundColor: "#2ECC71",
						},
					],
				},
				options: {
					responsive: true,
					tooltips: {
						mode: "index",
						titleFontSize: 12,
						titleFontColor: "#000",
						bodyFontColor: "#000",
						backgroundColor: "#fff",
						titleFontFamily: "Poppins",
						bodyFontFamily: "Poppins",
						cornerRadius: 3,
						intersect: false,
					},
					legend: {
						display: false,
						labels: {
							usePointStyle: true,
							fontFamily: "Poppins",
						},
					},
					scales: {
						xAxes: [
							{
								display: true,
								gridLines: {
									display: false,
									drawBorder: false,
								},
								scaleLabel: {
									display: true,
									labelString: "Fecha",
								},
								ticks: {
									fontFamily: "Poppins",
									fontColor: "#9aa0ac", // Font Color
								},
							},
						],
						yAxes: [
							{
								display: true,
								gridLines: {
									display: true,
									drawBorder: false,
								},
								scaleLabel: {
									display: true,
									labelString: "Total",
									fontFamily: "Poppins",
								},
								ticks: {
									fontFamily: "Poppins",
									fontColor: "#9aa0ac", // Font Color
								},
							},
						],
					},
					title: {
						display: false,
						text: "Normal Legend",
					},
				},
			});
		}
	});
}

function resumen_compras() {
	$.post(
		"Controllers/Graphics.php?op=resumen_compras",
		function (data, status) {
			data = JSON.parse(data);
			//console.log(data);
			var ctx = document.getElementById("resumen_compras").getContext("2d");
			var myChart = new Chart(ctx, {
				type: "pie",
				data: {
					datasets: [
						{
							data: data.totales,
							backgroundColor: [
								"#191d21",
								"#63ed7a",
								"#ffa426",
								"#fc544b",
								"#6777ef",
							],
							label: "Dataset 1",
						},
					],
					labels: data.fechas,
				},
				options: {
					responsive: true,
					legend: {
						position: "bottom",
					},
				},
			});
		}
	);
}

function resumen_ventas() {
	$.post("Controllers/Graphics.php?op=resumen_ventas", function (data, status) {
		data = JSON.parse(data);
		//console.log(data);
		var options = {
			tooltips: {
				enabled: true,
			},
			plugins: {
				datalabels: {
					formatter: (value, ctx) => {
						let sum = ctx.dataset._meta[0].total;
						let percentage = ((value * 100) / sum).toFixed(2) + "%";
						return percentage;
					},
					color: "#fff",
				},
			},
		};
		var ctx = document.getElementById("resumen_ventas").getContext("2d");
		var myChart = new Chart(ctx, {
			type: "pie",
			data: {
				datasets: [
					{
						data: data.totales,
						backgroundColor: [
							"#191d21",
							"#63ed7a",
							"#ffa426",
							"#fc544b",
							"#6777ef",
						],
						label: "Dataset 1",
					},
				],
				labels: data.fechas,
			},
			options: {
				responsive: true,
				legend: {
					position: "bottom",
				},
			},
		});
	});
}

/* chart shadow */
var draw = Chart.controllers.line.prototype.draw;
Chart.controllers.lineShadow = Chart.controllers.line.extend({
	draw: function () {
		draw.apply(this, arguments);
		var ctx = this.chart.chart.ctx;
		var _stroke = ctx.stroke;
		ctx.stroke = function () {
			ctx.save();
			ctx.shadowColor = "#00000075";
			ctx.shadowBlur = 10;
			ctx.shadowOffsetX = 8;
			ctx.shadowOffsetY = 8;
			_stroke.apply(this, arguments);
			ctx.restore();
		};
	},
});
//cuadritos
var balance_chart = document.getElementById("chart-1").getContext("2d");

var balance_chart_bg_color = balance_chart.createLinearGradient(0, 0, 0, 70);
balance_chart_bg_color.addColorStop(0, "rgba(120, 107, 236, .2)");
balance_chart_bg_color.addColorStop(1, "rgba(120, 107, 236, 0)");
//LINEA SOBREADA 1
function cuadro_1() {
	$.post(
		"Controllers/Graphics.php?op=ventasultimos_10dias",
		function (data, status) {
			data = JSON.parse(data);
			console.log(data);

			var balance_chart = document.getElementById("chart-1").getContext("2d");
			var myChart = new Chart(balance_chart, {
				type: "lineShadow",
				data: {
					labels: data.fechas,
					datasets: [
						{
							label: "Ventas",
							data: data.totales,
							backgroundColor: balance_chart_bg_color,
							borderWidth: 3,
							borderColor: "rgba(41, 192, 177, 1)",
							pointBorderWidth: 0,
							pointBorderColor: "transparent",
							pointRadius: 3,
							pointBackgroundColor: "transparent",
							pointHoverBackgroundColor: "rgba(120, 107, 236,1)",
						},
					],
				},
				options: {
					layout: {
						padding: {
							bottom: -1,
							left: -1,
						},
					},
					legend: {
						display: false,
					},

					scales: {
						yAxes: [
							{
								gridLines: {
									display: false,
									drawBorder: false,
								},
								ticks: {
									beginAtZero: true,
									display: false,
									fontColor: "#9aa0ac", // Font Color
								},
							},
						],
						xAxes: [
							{
								gridLines: {
									drawBorder: false,
									display: false,
								},
								ticks: {
									display: false,
									fontColor: "#9aa0ac", // Font Color
								},
							},
						],
					},
				},
			});
		}
	);
}

//LINEA SOBREADA 2
function cuadro_2() {
	$.post(
		"Controllers/Graphics.php?op=comprasultimos_10dias",
		function (data, status) {
			data = JSON.parse(data);
			//console.log(data);
			//var sales_chart = document.getElementById("chart-2").getContext("2d");
			var sales_chart = document.getElementById("chart-2").getContext("2d");

			var myChart = new Chart(sales_chart, {
				type: "lineShadow",
				data: {
					labels: data.fechas,
					datasets: [
						{
							label: "Compras",
							data: data.totales,
							borderWidth: 2,
							backgroundColor: balance_chart_bg_color,
							borderWidth: 3,
							borderColor: "rgba(156, 39, 176, 1)",
							pointBorderWidth: 0,
							pointBorderColor: "transparent",
							pointRadius: 3,
							pointBackgroundColor: "transparent",
							pointHoverBackgroundColor: "rgba(120, 107, 236,1)",
						},
					],
				},
				options: {
					layout: {
						padding: {
							bottom: -1,
							left: -1,
						},
					},
					legend: {
						display: false,
					},
					scales: {
						yAxes: [
							{
								gridLines: {
									display: false,
									drawBorder: false,
								},
								ticks: {
									beginAtZero: true,
									display: false,
								},
							},
						],
						xAxes: [
							{
								gridLines: {
									drawBorder: false,
									display: false,
								},
								ticks: {
									display: false,
								},
							},
						],
					},
				},
			});
		}
	);
}

//LINEA SOBREADA 3
function cuadro_3() {
	$.post(
		"Controllers/Graphics.php?op=ingresos_grafica",
		function (data, status) {
			data = JSON.parse(data);
			//console.log(data);
			var sales_chart = document.getElementById("chart-3").getContext("2d");

			var myChart = new Chart(sales_chart, {
				type: "lineShadow",
				data: {
					labels: data.fechas,
					datasets: [
						{
							label: "Ingresos",
							data: data.totales,
							borderWidth: 2,
							backgroundColor: balance_chart_bg_color,
							borderWidth: 3,
							borderColor: "rgba(76, 175, 80, 1)",
							pointBorderWidth: 0,
							pointBorderColor: "transparent",
							pointRadius: 3,
							pointBackgroundColor: "transparent",
							pointHoverBackgroundColor: "rgba(120, 107, 236,1)",
						},
					],
				},
				options: {
					layout: {
						padding: {
							bottom: -1,
							left: -1,
						},
					},
					legend: {
						display: false,
					},
					scales: {
						yAxes: [
							{
								gridLines: {
									display: false,
									drawBorder: false,
								},
								ticks: {
									beginAtZero: true,
									display: false,
								},
							},
						],
						xAxes: [
							{
								gridLines: {
									drawBorder: false,
									display: false,
								},
								ticks: {
									display: false,
								},
							},
						],
					},
				},
			});
		}
	);
}

init();
