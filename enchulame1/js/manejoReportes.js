document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('reporteForm');

    // Verifica si el formulario existe en el DOM
    if (!form) {
        console.error("Formulario no encontrado. Verifica que el ID 'reporteForm' sea correcto.");
        return;
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault(); // Evita el envío por defecto del formulario

        // Intenta obtener los elementos del formulario
        const descripcion = document.getElementById('descripcion');
        const lugar = document.getElementById('lugar');
        const planta = document.getElementById('planta');
        const descripcionLugar = document.getElementById('descripcionLugar');
        const fotoInput = document.getElementById('foto');

        // Verifica si los elementos existen
        if (!descripcion || !lugar || !planta || !descripcionLugar || !fotoInput) {
            console.error("Uno o más elementos del formulario no se encontraron en el DOM.");
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Hubo un problema al cargar el formulario. Por favor, recarga la página.'
            });
            return;
        }

        // Obtiene valores del formulario
        const descripcionValue = descripcion.value;
        const lugarValue = lugar.value;
        const plantaValue = planta.value;
        const descripcionLugarValue = descripcionLugar.value;
        const foto = fotoInput.files[0];
        const numNomina = sessionStorage.getItem('numNomina'); // Tomado de la sesión

        // Validación de planta
        let idArea;
        if (plantaValue === 'alta') {
            idArea = 1;
        } else if (plantaValue === 'baja') {
            idArea = 2;
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Por favor, selecciona una planta válida.'
            });
            return;
        }

        // Crea el objeto FormData
        const formData = new FormData();
        formData.append('descripcion', descripcionValue);
        formData.append('lugar', lugarValue);
        formData.append('planta', plantaValue);
        formData.append('descripcionLugar', descripcionLugarValue);
        if (foto) {
            formData.append('foto', foto);
        }
        formData.append('numNomina', numNomina);
        formData.append('idArea', idArea);

        // Enviar los datos con fetch
        fetch('dao/daoManejoReporte.php', {
            method: 'POST',
            body: formData,
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: 'Reporte enviado exitosamente.',
                    });
                    form.reset();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: `Error al enviar el reporte: ${data.message}`,
                    });
                }
            })
            .catch(error => {
                console.error("Error en la solicitud:", error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Hubo un error al enviar el reporte. Por favor, inténtalo de nuevo más tarde.',
                });
            });
    });
});

