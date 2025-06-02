document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("vacanteForm");
    const confirmarBtn = document.getElementById("confirmarGuardarVacante");

    confirmarBtn.addEventListener("click", function (e) {
        e.preventDefault();

        // Validar campos obligatorios
        const camposObligatorios = [
            { campo: form.titulo, nombre: "Título del puesto" },
            { campo: form.area, nombre: "Área / Departamento" },
            { campo: form.tipo, nombre: "Tipo de contrato" },
            { campo: form.escolaridad, nombre: "Escolaridad mínima" },
            { campo: form.idioma, nombre: "Idioma" },
            { campo: form.pais, nombre: "País / Región" },
            { campo: form.estado, nombre: "Estado / Provincia" },
            { campo: form.ciudad, nombre: "Ciudad" },
            { campo: form.espacio, nombre: "Espacio de trabajo" },
            { campo: form.descripcion, nombre: "Descripción del puesto" },
        ];

        for (const item of camposObligatorios) {
            if (!item.campo.value.trim()) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Campo obligatorio',
                    text: `Por favor, completa el campo: ${item.nombre}`,
                });
                item.campo.focus();
                return; // Detener envío si hay un campo vacío
            }
        }

        const formData = new FormData();

        formData.append("titulo", form.titulo.value);
        formData.append("area", form.area.value);
        formData.append("tipo", form.tipo.value);
        formData.append("horario", form.horario.value);
        formData.append("sueldo", form.sueldo.value);
        formData.append("escolaridad", form.escolaridad.value);
        formData.append("pais", form.pais.value);
        formData.append("estado", form.estado.value);
        formData.append("ciudad", form.ciudad.value);
        formData.append("espacio", form.espacio.value);
        formData.append("idioma", form.idioma.value);
        formData.append("requisitos", form.requisitos.value);
        formData.append("beneficios", form.beneficios.value);
        formData.append("descripcion", form.descripcion.value);

        // Imagen
        const imagen = form.imagen.files[0];
        if (imagen) {
            formData.append("imagen", imagen);
        }

        // Enviar datos
        fetch("dao/daoVacante.php", {
            method: "POST",
            body: formData
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error("Error en la respuesta del servidor");
                }
                return response.text();
            })
            .then(data => {
                Swal.fire({
                    icon: 'success',
                    title: 'Vacante guardada correctamente',
                    showConfirmButton: false,
                    timer: 2000
                }).then(() => {
                    location.reload();
                });
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error al guardar',
                    text: error.message,
                });
            });
    });
});
