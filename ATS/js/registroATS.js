// Al cargar la página, establecer fecha máxima para ser mayor de 18 años
window.addEventListener('DOMContentLoaded', () => {
    const inputFechaNacimiento = document.querySelector('input[name="fecha_nacimiento"]');
    const hoy = new Date();
    const anio18Atras = new Date(hoy.getFullYear() - 18, hoy.getMonth(), hoy.getDate());
    inputFechaNacimiento.max = anio18Atras.toISOString().split('T')[0];

    // Validar fecha al cambiar input
    inputFechaNacimiento.addEventListener('change', function () {
        const fechaSeleccionada = new Date(this.value);
        if (fechaSeleccionada > anio18Atras) {
            Swal.fire({
                icon: 'error',
                title: 'Fecha inválida',
                text: 'Debes tener al menos 18 años para poder registrarte.',
            });
            this.value = '';
        }
    });
});

document.querySelector('.formulario-registro').addEventListener('submit', function (event) {
    event.preventDefault();

    // Obtener campos
    const email = document.querySelector('input[name="email"]').value.trim();
    const nombre = document.querySelector('input[name="nombre"]').value.trim();
    const apellidos = document.querySelector('input[name="apellidos"]').value.trim();
    const telefono = document.querySelector('input[name="telefono"]').value.trim();
    const contrasena = document.querySelector('input[name="contrasena"]').value.trim();
    const confirmarContrasena = document.querySelector('input[name="confirmar_contrasena"]').value.trim();
    const sueldo = document.querySelector('input[name="sueldo"]').value.trim();
    const nivelEstudios = document.querySelector('select[name="nivel_estudios"]').value;
    const ubicacion = document.querySelector('input[name="ubicacion"]').value.trim();
    const area = document.querySelector('select[name="area"]').value;
    const especialidad = document.querySelector('select[name="especialidad"]').value;
    const otraEspecialidadInput = document.querySelector('input[name="otra_especialidad"]');
    const otraEspecialidad = (especialidad === 'otra' && otraEspecialidadInput) ? otraEspecialidadInput.value.trim() : '';
    const fechaNacimiento = document.querySelector('input[name="fecha_nacimiento"]').value;
    const aceptaTerminos = document.querySelector('input[name="acepta_terminos"]').checked;

    // Validaciones básicas
    if (!email || !nombre || !apellidos || !telefono || !contrasena || !confirmarContrasena ||
        !sueldo || !nivelEstudios || !ubicacion || !area || !especialidad || !fechaNacimiento) {
        Swal.fire({
            icon: 'error',
            title: 'Campos incompletos',
            text: 'Por favor, completa todos los campos obligatorios.',
        });
        return;
    }

    if (especialidad === 'otra' && !otraEspecialidad) {
        Swal.fire({
            icon: 'error',
            title: 'Especifique la carrera',
            text: 'Debes escribir tu especialidad si seleccionas "Otra".',
        });
        return;
    }

    if (!validateEmail(email)) {
        Swal.fire({
            icon: 'error',
            title: 'Correo inválido',
            text: 'Ingresa un correo electrónico válido.',
        });
        return;
    }

    if (contrasena !== confirmarContrasena) {
        Swal.fire({
            icon: 'error',
            title: 'Contraseñas diferentes',
            text: 'Las contraseñas no coinciden.',
        });
        return;
    }

    if (!aceptaTerminos) {
        Swal.fire({
            icon: 'error',
            title: 'Acepta los términos',
            text: 'Debes aceptar los términos y condiciones para continuar.',
        });
        return;
    }

    // Validar que sea mayor o igual a 18 años
    const edad = calcularEdad(fechaNacimiento);
    if (edad < 18) {
        Swal.fire({
            icon: 'error',
            title: 'Edad inválida',
            text: 'Debes tener al menos 18 años para poder registrarte.',
        });
        return;
    }

    // Preparar FormData
    const formData = new FormData();
    formData.append('email', email);
    formData.append('nombre', nombre);
    formData.append('apellidos', apellidos);
    formData.append('telefono', telefono);
    formData.append('contrasena', contrasena);
    formData.append('sueldo', sueldo);
    formData.append('nivel_estudios', nivelEstudios);
    formData.append('ubicacion', ubicacion);
    formData.append('area', area);
    formData.append('especialidad', especialidad === 'otra' ? otraEspecialidad : especialidad);
    formData.append('fecha_nacimiento', fechaNacimiento);

    // Enviar datos al backend
    fetch('dao/daoRegistroATS.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: '¡Registro exitoso!',
                    text: 'Tu cuenta ha sido creada.',
                    confirmButtonText: 'Iniciar sesión'
                }).then(() => {
                    window.location.href = 'loginATS.php';
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Ocurrió un error al registrar.',
                });
            }
        })
        .catch(() => {
            Swal.fire({
                icon: 'error',
                title: 'Error del servidor',
                text: 'No se pudo conectar con el servidor.',
            });
        });
});

function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email.toLowerCase());
}

function calcularEdad(fecha) {
    const fechaNacimiento = new Date(fecha);
    const hoy = new Date();
    let edad = hoy.getFullYear() - fechaNacimiento.getFullYear();
    const m = hoy.getMonth() - fechaNacimiento.getMonth();
    if (m < 0 || (m === 0 && hoy.getDate() < fechaNacimiento.getDate())) {
        edad--;
    }
    return edad;
}
