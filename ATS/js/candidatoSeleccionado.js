document.addEventListener("DOMContentLoaded", function () {
    const contenedorCandidatos = document.getElementById("contenedorCandidatos");
    const filtroArea = document.getElementById('filtro-area');
    const limpiarFiltrosBtn = document.getElementById('btn-limpiar');
    const campoVacante = document.getElementById('filtro-vacante');
    const campoCandidato = document.getElementById('filtro-candidato');
    const btnBuscar = document.getElementById('btn-buscar');

    let todosLosCandidatos = []; // Almacenamos todos los candidatos aquí para un filtrado rápido

    // --- Cargar Áreas Dinámicamente (Lógica Original) ---
    function cargarAreas() {
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

    // --- Cargar Todos los Candidatos al Iniciar (Lógica Original) ---
    function cargarCandidatosIniciales() {
        fetch('dao/obtenerCandidatoFinal.php')
            .then(response => response.json())
            .then(data => {
                if (Array.isArray(data)) {
                    todosLosCandidatos = data;
                    renderizarCandidatos(todosLosCandidatos);
                }
            })
            .catch(error => {
                console.error("Error al cargar candidatos:", error);
                contenedorCandidatos.innerHTML = `<p class="mensaje-error">Error al cargar los datos.</p>`;
            });
    }

    // --- INICIO DE LA MODIFICACIÓN: Renderizado con Lógica de Botón ---
    function renderizarCandidatos(candidatos) {
        contenedorCandidatos.innerHTML = "";
        if (candidatos.length === 0) {
            contenedorCandidatos.innerHTML = `<p class="mensaje-vacio">No hay candidatos que coincidan con los filtros.</p>`;
            return;
        }

        candidatos.forEach(candidato => {
            const card = document.createElement("div");
            card.className = "candidato-card";

            // Lógica condicional para decidir qué botón mostrar
            let footerHTML = '';
            if (candidato.OfertaEnviada == 1) {
                // Si la oferta ya se envió, mostrar un botón deshabilitado
                footerHTML = `
                    <button class="btn-accion btn-oferta enviado" disabled>
                        <i class="fas fa-check-circle"></i> Oferta Enviada
                    </button>
                `;
            } else {
                // Si no, mostrar el botón para enviar la oferta
                footerHTML = `
                    <button class="btn-accion btn-oferta" 
                            data-id="${candidato.IdPostulacion}" 
                            data-nombre="${candidato.NombreCompleto}" 
                            data-correo="${candidato.Correo}" 
                            data-vacante="${candidato.TituloVacante}">
                        <i class="fas fa-paper-plane"></i> Enviar Oferta
                    </button>
                `;
            }

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
                <div class="card-footer">
                    ${footerHTML}
                </div>
            `;
            contenedorCandidatos.appendChild(card);
        });
    }

    // --- Lógica de Filtrado (Lógica Original) ---
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

    // --- INICIO DE LA MODIFICACIÓN: Lógica de Clic para Enviar Oferta ---
    contenedorCandidatos.addEventListener('click', function(e) {
        const button = e.target.closest('.btn-oferta');
        if (!button || button.disabled) return;

        const idPostulacion = button.dataset.id;
        const nombreCandidato = button.dataset.nombre;
        const correoCandidato = button.dataset.correo;
        const vacante = button.dataset.vacante;

        Swal.fire({
            title: `¿Enviar oferta a ${nombreCandidato}?`,
            text: `Se enviará un correo de felicitación a ${correoCandidato} para la vacante de ${vacante}.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            confirmButtonText: 'Sí, enviar ahora',
            cancelButtonText: 'Cancelar'
        }).then(async (result) => {
            if (result.isConfirmed) {
                const originalButtonHTML = button.innerHTML;
                button.disabled = true;
                button.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Enviando...`;

                const formData = new URLSearchParams();
                formData.append('idPostulacion', idPostulacion);
                formData.append('nombreCandidato', nombreCandidato);
                formData.append('correoCandidato', correoCandidato);
                formData.append('vacante', vacante);

                try {
                    const response = await fetch('mailer/mailerOferta.php', { method: 'POST', body: formData });
                    const data = await response.json();

                    if (data.status === 'success') {
                        Swal.fire('¡Enviado!', data.message, 'success');
                        button.innerHTML = `<i class="fas fa-check-circle"></i> Oferta Enviada`;
                        button.classList.add('enviado');

                        const candidatoIndex = todosLosCandidatos.findIndex(c => c.IdPostulacion == idPostulacion);
                        if(candidatoIndex > -1) {
                            todosLosCandidatos[candidatoIndex].OfertaEnviada = 1;
                        }
                    } else {
                        Swal.fire('Error', data.message || 'No se pudo enviar el correo.', 'error');
                        button.disabled = false;
                        button.innerHTML = originalButtonHTML;
                    }
                } catch (error) {
                    Swal.fire('Error de Conexión', 'No se pudo comunicar con el servidor.', 'error');
                    button.disabled = false;
                    button.innerHTML = originalButtonHTML;
                }
            }
        });
    });

    // --- Event Listeners (Lógica Original) ---
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

    // --- Inicialización de la Página (Lógica Original) ---
    cargarAreas();
    cargarCandidatosIniciales();
});

