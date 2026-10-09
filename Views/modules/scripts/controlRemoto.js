
    // Variable para ingresar el nombre de la veterinaria
    let veterinaria = "pradera"; // Rellena el nombre de la veterinaria aquí
    let estadoAnterior = null; // Variable para guardar el estado anterior de la veterinaria

    // Función para realizar la acción dependiendo del estado de la veterinaria
    function realizarAccion(estado) {
        if (estado === 1) {
            console.log('La veterinaria está activa. Realizando acción A.');
            alert('¡Veterinaria Activa! Realizando Acción A.');
        } else if (estado === 0) {
            console.log('La veterinaria está inactiva. Realizando acción B.');
            alert('Veterinaria Inactiva. Realizando Acción B.');
        }
    }

    // Función para obtener los datos de veterinarias
    async function obtenerVeterinarias() {
        try {
            const response = await fetch('https://control.jorginhos.com/back_consumo.php');  // Realiza la solicitud al archivo PHP
            const veterinarias = await response.json(); // Convierte la respuesta a JSON
            
            console.log(veterinarias); // Verificar lo que devuelve el servidor
            
            // Verificar si hay veterinarias y recorrerlas
            veterinarias.forEach(veterinariaItem => {
                // Comparar si el nombre de la veterinaria es el que has ingresado
                if (veterinariaItem.nombre.toLowerCase() === veterinaria.toLowerCase()) {
                    // Verificar si ha habido un cambio en el estado
                    if (veterinariaItem.estado !== estadoAnterior) {
                        // Si el estado ha cambiado, realizamos la acción
                        realizarAccion(veterinariaItem.estado);

                        // Actualizamos el estado anterior al nuevo estado
                        estadoAnterior = veterinariaItem.estado;
                    }
                }
            });
        } catch (error) {
            console.error('Error al obtener los datos:', error);
        }
    }

    // Llamar la función para obtener veterinarias al cargar la página
    window.onload = function() {
        obtenerVeterinarias(); // Cargar datos inmediatamente

        // Configurar setInterval para actualizar los datos cada 5 segundos (5000 ms)
        setInterval(obtenerVeterinarias, 5000);  // Actualizar cada 5 segundos
    }

