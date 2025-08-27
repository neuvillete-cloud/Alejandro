document.addEventListener("DOMContentLoaded", function () {
    const contenedorCandidatos = document.getElementById("contenedorCandidatos");
    const filtroArea = document.getElementById('filtro-area');
    const limpiarFiltrosBtn = document.getElementById('btn-limpiar');
    const campoVacante = document.getElementById('filtro-vacante');
    const campoCandidato = document.getElementById('filtro-candidato');
    const btnBuscar = document.getElementById('btn-buscar');

    let todosLosCandidatos = []; // Almacenamos todos los candidatos aquí para un filtrado rápido
    let filtrosActivos = {};

    // --- 1. Cargar Áreas Dinámicamente ---
    function cargarAreas() {
        // Usamos tu PHP existente, pero le pedimos una acción diferente
        fetch('dao/obtenerCandidatoFinal.php?action=get_areas')
            .then(response => response.json())
            .then(areas => {
                if (Array.isArray(areas)) {
                    areas.forEach(area => {
                        const option = document.createElement('option');
                        option.value = area; // El valor será el nombre del área
                        option.textContent = area;
                        filtroArea.appendChild(option);
                    });
                }
            })
            .catch(error => console.error("Error al cargar áreas:", error));
    }

    // --- 2. Cargar Todos los Candidatos al Iniciar ---
    function cargarCandidatosIniciales() {
        fetch('dao/obtenerCandidatoFinal.php')
            .then(response => response.json())
            .then(data => {
                if (Array.isArray(data)) {
                    todosLosCandidatos = data;
                    renderizarCandidatos(todosLosCandidatos); // Mostramos todos al principio
                }
            })
            .catch(error => {
                console.error("Error al cargar candidatos:", error);
                contenedorCandidatos.innerHTML = `<p class="mensaje-error">Error al cargar los datos.</p>`;
            });
    }

    // --- 3. Función de Renderizado con Nuevo Diseño de Tarjeta ---
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
                <div class="card-footer">
                    <a href="#" class="btn-contratar" data-id="${candidato.IdPostulacion}">
                        <i class="fas fa-file-signature"></i> Contratar
                    </a>
                </div>
            `;
            contenedorCandidatos.appendChild(card);
        });
    }

    // --- 4. Lógica de Filtrado ---
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

    // --- Event Listeners ---
    btnBuscar.addEventListener("click", aplicarFiltros);
    campoVacante.addEventListener("keyup", (e) => { if (e.key === 'Enter') aplicarFiltros(); });
    campoCandidato.addEventListener("keyup", (e) => { if (e.key === 'Enter') aplicarFiltros(); });
    filtroArea.addEventListener("change", aplicarFiltros);

    limpiarFiltrosBtn.addEventListener("click", () => {
        campoVacante.value = "";
        campoCandidato.value = "";
        filtroArea.value = "";
        aplicarFiltros(); // Re-renderiza con todos los candidatos
    });

    // --- Inicialización de la Página ---
    cargarAreas();
    cargarCandidatosIniciales();
});