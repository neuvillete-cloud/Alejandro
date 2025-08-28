document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("vacanteForm");
    const confirmarBtn = document.getElementById("confirmarGuardarVacante");
    const idVacanteInput = document.getElementById("idVacante");

    // --- LÓGICA DE EDICIÓN ---
    const params = new URLSearchParams(window.location.search);
    const idVacanteAEditar = params.get("edit");

    if (idVacanteAEditar) {
        // --- MODO EDICIÓN ---
        document.querySelector(".section-title h1").textContent = "Editar Vacante";
        document.querySelector(".formulario-vacante h2").textContent = "Editar Vacante Existente";

        // Pedimos los datos de la vacante al servidor
        fetch(`dao/daoVacante.php?id=${idVacanteAEditar}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('La vacante no fue encontrada o hubo un error en el servidor.');
                }
                return response.json();
            })
            .then(result => {
                if (result.status === 'success') {
                    const vacante = result.data;

                    // Rellenamos el formulario con los datos recibidos
                    idVacanteInput.value = vacante.IdVacante;
                    form.titulo.value = vacante.TituloVacante;
                    form.area.value = vacante.NombreArea;
                    form.tipo.value = vacante.TipoContrato;
                    form.horario.value = vacante.Horario;
                    form.sueldo.value = vacante.Sueldo;
                    form.escolaridad.value = vacante.EscolaridadMinima;
                    form.pais.value = vacante.Pais;
                    form.estado.value = vacante.Estado;
                    form.ciudad.value = vacante.Ciudad;
                    form.espacio.value = vacante.EspacioTrabajo;
                    form.idioma.value = vacante.Idioma;
                    form.especialidad.value = vacante.Especialidad;
                    form.requisitos.value = vacante.Requisitos;
                    form.beneficios.value = vacante.Beneficios;
                    form.descripcion.value = vacante.Descripcion;

                    // Mostramos la imagen existente
                    if (vacante.Imagen) {
                        document.getElementById('preview').src = vacante.Imagen;
                        document.getElementById('preview').style.display = 'block';
                        document.querySelector('#drop-area .placeholder-text').style.display = 'none';
                    }
                } else {
                    Swal.fire('Error', result.message, 'error').then(() => {
                        window.location.href = "EstadisticasVacantes.php";
                    });
                }
            })
            .catch(error => {
                Swal.fire('Error de Carga', error.message, 'error');
            });
    }

    // --- LÓGICA DE GUARDADO MEJORADA CON VERIFICACIÓN ---
    confirmarBtn.addEventListener("click", function (e) {
        e.preventDefault();
        confirmarBtn.disabled = true;

        const camposObligatorios = [
            { campo: form.titulo, nombre: "Título del puesto" },
            { campo: form.area, nombre: "Área / Departamento" },
            { campo: form.tipo, nombre: "Tipo de contrato" },
            { campo: form.escolaridad, nombre: "Escolaridad mínima" },
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
                confirmarBtn.disabled = false;
                return;
            }
        }

        // Función interna para enviar los datos del formulario
        function guardarDatos() {
            const formData = new FormData(form);

            // Si es una nueva vacante, añadimos el IdSolicitud
            if (!idVacanteAEditar) {
                const idSolicitud = params.get("idSolicitud");
                formData.append("IdSolicitud", idSolicitud);
            }

            fetch("dao/daoVacante.php", {
                method: "POST",
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === "success") {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Éxito!',
                            text: 'Vacante guardada correctamente.',
                            showConfirmButton: false,
                            timer: 2000
                        }).then(() => {
                            window.location.href = "EstadisticasVacantes.php";
                        });
                    } else {
                        Swal.fire('Error al guardar', data.message, 'error');
                        confirmarBtn.disabled = false;
                    }
                })
                .catch(error => {
                    Swal.fire('Error de red', error.message, 'error');
                    confirmarBtn.disabled = false;
                });
        }

        // --- FLUJO PRINCIPAL AL HACER CLIC EN GUARDAR ---
        if (idVacanteAEditar) {
            // Si estamos editando, guardamos directamente sin verificar.
            guardarDatos();
        } else {
            // Si estamos creando, primero verificamos si ya existe.
            const idSolicitud = params.get("idSolicitud");
            if (!idSolicitud) {
                Swal.fire('Error crítico', 'No se encontró el IdSolicitud en la URL para crear la vacante.', 'error');
                confirmarBtn.disabled = false;
                return;
            }

            // REINTEGRADO: Verificación de vacante existente
            fetch(`dao/verificarVacante.php?idSolicitud=${idSolicitud}`)
                .then(response => response.json())
                .then(result => {
                    if (result.existe) {
                        Swal.fire({
                            icon: 'info',
                            title: 'Vacante ya registrada',
                            text: 'Ya se ha registrado una vacante para esta solicitud.',
                            showConfirmButton: false,
                            timer: 2500
                        }).then(() => {
                            window.location.href = "EstadisticasVacantes.php";
                        });
                    } else {
                        // Si no existe, procedemos a guardar
                        guardarDatos();
                    }
                })
                .catch(error => {
                    Swal.fire('Error de verificación', 'No se pudo verificar si ya existe una vacante.', 'error');
                    confirmarBtn.disabled = false;
                });
        }
    });
});