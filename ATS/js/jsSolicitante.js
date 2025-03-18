document.getElementById('solicitudForm').addEventListener('submit', function (event) {
    event.preventDefault(); // Evitar el envío del formulario por defecto

    // Obtener los valores de los campos del formulario
    const nombre = document.getElementById('nombre').value.trim();
    const area = document.getElementById('area').value.trim();
    const puesto = document.getElementById('puesto').value.trim();
    const tipo = document.getElementById('tipo').value;
    const reemplazoNombre = document.getElementById('reemplazoNombre').value.trim();

    // Validar que los campos obligatorios no estén vacíos
    if (!nombre || !area || !puesto || !tipo) {
        Swal.fire({
            icon: 'error',
            title: 'Campos incompletos',
            text: 'Por favor, complete todos los campos obligatorios.',
        });
        return;
    }

    // Validar que el campo de reemplazo esté lleno si el tipo es "reemplazo"
    if (tipo === 'reemplazo' && !reemplazoNombre) {
        Swal.fire({
            icon: 'error',
            title: 'Campos incompletos',
            text: 'Por favor, ingrese el nombre de la persona a reemplazar.',
        });
        return;
    }

    // Crear un objeto FormData para enviar los datos
    const formData = new FormData();
    formData.append('nombre', nombre);
    formData.append('area', area);
    formData.append('puesto', puesto);
    formData.append('tipo', tipo);
    if (tipo === 'reemplazo') {
        formData.append('reemplazoNombre', reemplazoNombre);
    }

    // Enviar los datos al servidor mediante fetch
    fetch('https://grammermx.com/Mailer/registroSolicitud.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            console.log(data.message);
            if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: '¡Solicitud registrada!',
                    text: 'Tu solicitud ha sido registrada exitosamente.',
                }).then(() => {
                    // Reiniciar el formulario
                    document.getElementById('solicitudForm').reset();
                    // Ocultar los campos de reemplazo si estaban visibles
                    document.getElementById('reemplazoFields').style.display = 'none';
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error en el registro',
                    text: data.message,
                });
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Error del servidor',
                text: 'Hubo un problema en la comunicación con el servidor. Por favor, inténtelo de nuevo más tarde.',
            });
        });
});
