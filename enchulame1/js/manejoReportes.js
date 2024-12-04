
document.getElementById('reporteForm').addEventListener('submit', function(event) {
    event.preventDefault(); // Evita el envío por defecto del formulario

    const descripcion = document.getElementById('descripcion').value;
    const lugar = document.getElementById('lugar').value;
    const planta = document.getElementById('planta').value;
    const descripcionLugar = document.getElementById('descripcionLugar').value;
    const foto = document.getElementById('foto').files[0];
    const numNomina = sessionStorage.getItem('numNomina'); // Tomado de la sesión

    // Asignar el ID del área según la planta seleccionada
    let idArea;
    if (planta === 'alta') {
        idArea = 1;
    } else if (planta === 'baja') {
        idArea = 2;
    } else {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Por favor, selecciona una planta válida.'
        });
        return;
    }

    // Crear un objeto FormData para enviar archivos
    const formData = new FormData();
    formData.append('descripcion', descripcion);
    formData.append('lugar', lugar);
    formData.append('planta', planta);
    formData.append('descripcionLugar', descripcionLugar);
    formData.append('foto', foto);
    formData.append('numNomina', numNomina);
    formData.append('idArea', idArea);

    // Enviar la solicitud con fetch
    fetch('dao/daoManejoReporte.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: 'Reporte enviado exitosamente.'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Redirigir a la página deseada
                        window.location.href = 'Administrador.php'; // Reemplaza con la URL a la que deseas redirigir
                    }
                });
                // Opcional: Puedes limpiar el formulario si lo deseas
                document.getElementById('reporteForm').reset();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al enviar el reporte: ' + data.message
                });
            }
        })
        .catch(error => {
            console.error("Error:", error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Hubo un error al enviar el reporte.'
            });
        });
});
