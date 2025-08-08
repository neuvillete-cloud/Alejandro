const filtrosSeleccionados = {};
let filtroBusqueda = "";
let filtroNombreCandidato = "";

document.addEventListener("DOMContentLoaded", function () {
    const fecha = document.getElementById('filtro-fecha');
    const area = document.getElementById('filtro-area');
    const limpiar = document.getElementById('limpiar-filtros');

    const campoBusqueda = document.querySelector(".campo-busqueda input"); // Título vacante
    const campoNombre = document.querySelector(".campo-ubicacion input"); // Nombre del candidato
    const btnBuscar = document.querySelector(".btn-buscar");

    const sugerenciasTitulo = document.querySelector(".campo-busqueda .historial-busquedas");
    const sugerenciasNombre = document.querySelector(".campo-ubicacion .historial-ubicaciones");

    const selects = [fecha, area];

    selects.forEach(select => {
        select.addEventListener("change", () => {
            const key = select.id.replace('filtro-', '');
            if (select.value === "") {
                delete filtrosSeleccionados[key];
            } else {
                filtrosSeleccionados[key] = select.value;
            }
            cargarCandidatos();
        });
    });

    limpiar.addEventListener("click", () => {
        selects.forEach(select => select.value = "");
        campoBusqueda.value = "";
        campoNombre.value = "";
        filtroBusqueda = "";
        filtroNombreCandidato = "";
        Object.keys(filtrosSeleccionados).forEach(k => delete filtrosSeleccionados[k]);
        cargarCandidatos();
    });

    function dispararBusqueda() {
        filtroBusqueda = campoBusqueda.value.trim();
        filtroNombreCandidato = campoNombre.value.trim();
        sugerenciasTitulo.style.display = "none";
        sugerenciasNombre.style.display = "none";
        cargarCandidatos();
    }

    btnBuscar.addEventListener("click", dispararBusqueda);
    [campoBusqueda, campoNombre].forEach(input => {
        input.addEventListener("keydown", (e) => {
            if (e.key === "Enter") {
                e.preventDefault();
                dispararBusqueda();
            }
        });
    });

    document.querySelector(".cerrar-busqueda").addEventListener("click", () => {
        campoBusqueda.value = "";
        filtroBusqueda = "";
        cargarCandidatos();
    });

    document.querySelector(".cerrar-ubicacion").addEventListener("click", () => {
        campoNombre.value = "";
        filtroNombreCandidato = "";
        cargarCandidatos();
    });

    campoBusqueda.addEventListener("input", () => {
        const texto = campoBusqueda.value.trim();
        if (texto.length < 2) {
            sugerenciasTitulo.style.display = "none";
            return;
        }

        fetch(`php/obtenerCandidatoFinal.php?autocomplete=titulo&q=${encodeURIComponent(texto)}`)
            .then(res => res.json())
            .then(sugerencias => {
                sugerenciasTitulo.innerHTML = "";

                if (sugerencias.length === 0) {
                    sugerenciasTitulo.style.display = "none";
                    return;
                }

                sugerencias.forEach(s => {
                    const li = document.createElement("li");
                    li.textContent = s;
                    li.addEventListener("click", () => {
                        campoBusqueda.value = s;
                        filtroBusqueda = s;
                        sugerenciasTitulo.style.display = "none";
                        cargarCandidatos();
                    });
                    sugerenciasTitulo.appendChild(li);
                });

                sugerenciasTitulo.style.display = "block";
            });
    });

    campoBusqueda.addEventListener("blur", () => {
        setTimeout(() => sugerenciasTitulo.style.display = "none", 200);
    });

    campoNombre.addEventListener("input", () => {
        const texto = campoNombre.value.trim();
        if (texto.length < 2) {
            sugerenciasNombre.style.display = "none";
            return;
        }

        fetch(`php/obtenerCandidatoFinal.php?autocomplete=nombre&q=${encodeURIComponent(texto)}`)
            .then(res => res.json())
            .then(sugerencias => {
                sugerenciasNombre.innerHTML = "";

                if (sugerencias.length === 0) {
                    sugerenciasNombre.style.display = "none";
                    return;
                }

                sugerencias.forEach(s => {
                    const li = document.createElement("li");
                    li.textContent = s;
                    li.addEventListener("click", () => {
                        campoNombre.value = s;
                        filtroNombreCandidato = s;
                        sugerenciasNombre.style.display = "none";
                        cargarCandidatos();
                    });
                    sugerenciasNombre.appendChild(li);
                });

                sugerenciasNombre.style.display = "block";
            });
    });

    campoNombre.addEventListener("blur", () => {
        setTimeout(() => sugerenciasNombre.style.display = "none", 200);
    });

    cargarCandidatos();
});

// Función que envía los filtros al PHP y renderiza los candidatos seleccionados
function cargarCandidatos() {
    const params = new URLSearchParams();

    if (filtroBusqueda) params.append("titulo", filtroBusqueda);
    if (filtroNombreCandidato) params.append("nombre", filtroNombreCandidato);

    for (let key in filtrosSeleccionados) {
        params.append(key, filtrosSeleccionados[key]);
    }

    fetch(`php/obtenerCandidatoFinal.php?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            const contenedor = document.getElementById("contenedorCandidatos");
            if (!Array.isArray(data) || data.length === 0) {
                contenedor.innerHTML = `<p class="mensaje-vacio">No hay candidatos que coincidan con los filtros.</p>`;
                return;
            }

            contenedor.innerHTML = "";
            data.forEach(candidato => {
                let fechaFormateada = "Sin fecha";
                if (candidato.FechaSeleccion && candidato.FechaSeleccion !== "0000-00-00 00:00:00") {
                    const fecha = new Date(candidato.FechaSeleccion);
                    const opciones = { day: 'numeric', month: 'long', year: 'numeric' };
                    fechaFormateada = fecha.toLocaleDateString("es-MX", opciones);
                }

                const card = document.createElement("div");
                card.classList.add("candidato-card");
                card.innerHTML = `
                    <div class="foto-candidato">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <div class="info-candidato">
                        <h3>${candidato.NombreCompleto}</h3>
                        <p><strong>Puesto:</strong> ${candidato.TituloVacante}</p>
                        <p><strong>Área:</strong> ${candidato.NombreArea}</p>
                        <p><strong>Seleccionado por:</strong> ${candidato.NombreSelector}</p>
                        <p><strong>Correo:</strong> <a href="mailto:${candidato.Correo}">${candidato.Correo}</a></p>
                        <p><strong>Teléfono:</strong> <a href="tel:${candidato.Telefono}">${candidato.Telefono}</a></p>
                        <p><strong>Fecha de selección:</strong> ${fechaFormateada}</p>
                    </div>
                `;
                contenedor.appendChild(card);
            });
        })
        .catch(error => {
            console.error("Error al cargar candidatos:", error);
            document.getElementById("contenedorCandidatos").innerHTML = `<p class="mensaje-error">Error al cargar los datos.</p>`;
        });
}
