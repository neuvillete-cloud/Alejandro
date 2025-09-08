document.addEventListener("DOMContentLoaded", function () {
    const contenedorCandidatos = document.getElementById("contenedorCandidatos");
    const filtroArea = document.getElementById('filtro-area');
    const limpiarFiltrosBtn = document.getElementById('btn-limpiar');
    const campoVacante = document.getElementById('filtro-vacante');
    const campoCandidato = document.getElementById('filtro-candidato');
    const btnBuscar = document.getElementById('btn-buscar');

    let todosLosCandidatos = [];

    function cargarAreas() {
        // Asumiendo que tu PHP de candidatos puede devolver áreas.
        // Si no, puedes apuntar a un script específico.
        fetch('dao/obtenerCandidatoFinal.php?action=get_areas')
            .then(response => response.json())
            .then(areas => {
                if (Array.isArray(areas)) {
                    areas.forEach(area => {
                        const option = document.createElement('option');
                        option.value = area;
                        option.textContent = area;
                        filtroArea.appendChild(option);
                    });
                }
            })
            .catch(error => console.error("Error al cargar áreas:", error));
    }

    function cargarCandidatosIniciales() {
        contenedorCandidatos.innerHTML = `<p class="mensaje-carga">Cargando candidatos...</p>`;
        fetch('dao/obtenerCandidatoFinal.php')
            .then(response => response.json())
            .then(data => {
                if (Array.isArray(data)) {
                    todosLosCandidatos = data;
                    renderizarCandidatos(todosLosCandidatos);
                } else {
                    throw new Error("La respuesta no es un formato válido.");
                }
            })
            .catch(error => {
                console.error("Error al cargar candidatos:", error);
                contenedorCandidatos.innerHTML = `<p class="mensaje-error">Error al cargar los datos de los candidatos.</p>`;
            });
    }

    // --- FUNCIÓN DE RENDERIZADO MODIFICADA ---
    function renderizarCandidatos(candidatos) {
        contenedorCandidatos.innerHTML = "";
        if (candidatos.length === 0) {
            contenedorCandidatos.innerHTML = `<p class="mensaje-vacio">No hay candidatos que coincidan con los filtros.</p>`;
            return;
        }

        candidatos.forEach(candidato => {
            const card = document.createElement("div");
            card.className = "candidato-card";
            card.innerHTML = `
                <div class="card-body">
                    <div class="card-header-info">
                        <div class="avatar"><i class="fas fa-user-tie"></i></div>
                        <div>
                            <h3 class="nombre-candidato">${candidato.NombreCompleto}</h3>
                        </div>
                    </div>
                    <div class="info-grid">
                        <div class="info-item">
                            <i class="fas fa-briefcase"></i>
                            <div class="info-item-content">
                                <strong>Vacante</strong>
                                <span>${candidato.TituloVacante}</span>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-building"></i>
                            <div class="info-item-content">
                                <strong>Área</strong>
                                <span>${candidato.NombreArea}</span>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-user-check"></i>
                            <div class="info-item-content">
                                <strong>Seleccionado por</strong>
                                <span>${candidato.NombreSelector}</span>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-envelope"></i>
                            <div class="info-item-content">
                                <strong>Correo</strong>
                                <a href="mailto:${candidato.Correo}">${candidato.Correo}</a>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-phone"></i>
                            <div class="info-item-content">
                                <strong>Teléfono</strong>
                                <a href="tel:${candidato.Telefono}">${candidato.Telefono}</a>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- INICIO: Botón Nuevo Añadido -->
                <div class="card-footer">
                    <button class="btn-accion btn-oferta" 
                            data-nombre="${candidato.NombreCompleto}" 
                            data-email="${candidato.Correo}" 
                            data-vacante="${candidato.TituloVacante}">
                        <i class="fas fa-paper-plane"></i> Enviar Oferta de Contratación
                    </button>
                </div>
                <!-- FIN: Botón Nuevo Añadido -->
            `;
            contenedorCandidatos.appendChild(card);
        });
    }

    // --- Lógica de Filtrado (sin cambios) ---
    function aplicarFiltros() {
        const filtroVacanteTexto = campoVacante.value.toLowerCase().trim();
        const filtroCandidatoTexto = campoCandidato.value.toLowerCase().trim();
        const filtroAreaValor = filtroArea.value;

        const candidatosFiltrados = todosLosCandidatos.filter(candidato => {
            const vacanteCoincide = candidato.TituloVacante.toLowerCase().includes(filtroVacanteTexto);
            const candidatoCoincide = candidato.NombreCompleto.toLowerCase().includes(filtroCandidatoTexto);
            const areaCoincide = filtroAreaValor === "" || candidato.NombreArea === filtroAreaValor;

            return vacanteCoincide && candidatoCoincide && areaCoincide;
        });

        renderizarCandidatos(candidatosFiltrados);
    }

    // --- INICIO: Nueva Lógica para Enviar Correo ---
    contenedorCandidatos.addEventListener('click', e => {
        if (e.target.classList.contains('btn-oferta') || e.target.closest('.btn-oferta')) {
            const button = e.target.closest('.btn-oferta');
            const { nombre, email, vacante } = button.dataset;

            Swal.fire({
                title: '¿Confirmar envío de oferta?',
                html: `Se enviará un correo de contratación a <strong>${nombre}</strong> para la vacante de <strong>${vacante}</strong>.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, enviar ahora',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
            }).then(result => {
                if (result.isConfirmed) {
                    enviarCorreoOferta(button, nombre, email, vacante);
                }
            });
        }
    });

    async function enviarCorreoOferta(button, nombre, email, vacante) {
        const originalButtonHTML = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';

        const formData = new URLSearchParams();
        formData.append('nombreCandidato', nombre);
        formData.append('emailCandidato', email);
        formData.append('nombreVacante', vacante);

        try {
            const response = await fetch('https://grammermx.com/Mailer/mailerOferta.php', { method: 'POST', body: formData });
            const data = await response.json();

            if (data.status === 'success') {
                Swal.fire('¡Correo Enviado!', 'La oferta de contratación ha sido enviada con éxito.', 'success');
                button.innerHTML = '<i class="fas fa-check-circle"></i> Oferta Enviada';
                button.classList.add('enviado'); // Cambia el estilo para mostrar que ya se envió
            } else {
                throw new Error(data.message || 'Error desconocido al enviar el correo.');
            }
        } catch (error) {
            Swal.fire('Error de Envío', `No se pudo enviar el correo: ${error.message}`, 'error');
            button.disabled = false;
            button.innerHTML = originalButtonHTML;
        }
    }
    // --- FIN: Nueva Lógica para Enviar Correo ---

    // Event Listeners (sin cambios)
    btnBuscar.addEventListener("click", aplicarFiltros);
    campoVacante.addEventListener("keyup", (e) => { if (e.key === 'Enter') aplicarFiltros(); });
    campoCandidato.addEventListener("keyup", (e) => { if (e.key === 'Enter') aplicarFiltros(); });
    filtroArea.addEventListener("change", aplicarFiltros);
    limpiarFiltrosBtn.addEventListener("click", () => {
        campoVacante.value = "";
        campoCandidato.value = "";
        filtroArea.value = "";
        aplicarFiltros();
    });

    // Inicialización
    cargarAreas();
    cargarCandidatosIniciales();
});

