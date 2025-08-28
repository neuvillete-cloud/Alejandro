const filtrosSeleccionados = {};
let filtroBusqueda = "";
let filtroUbicacion = "";

document.addEventListener("DOMContentLoaded", function () {
    const salario = document.getElementById('filtro-salario');
    const fecha = document.getElementById('filtro-fecha');
    const modalidad = document.getElementById('filtro-modalidad');
    const contrato = document.getElementById('filtro-contrato');
    const educacion = document.getElementById('filtro-educacion');
    const limpiar = document.getElementById('limpiar-filtros');

    const campoBusqueda = document.querySelector(".campo-busqueda input");
    const campoUbicacion = document.querySelector(".campo-ubicacion input");
    const btnBuscar = document.querySelector(".btn-buscar");

    const sugerenciasContainer = document.querySelector(".campo-busqueda .historial-busquedas");
    const sugerenciasUbicacionContainer = document.querySelector(".campo-ubicacion .historial-ubicaciones");

    const selects = [salario, fecha, modalidad, contrato, educacion];

    selects.forEach(select => {
        select.addEventListener("change", () => {
            const key = select.id.replace('filtro-', '');
            if (select.value === "") {
                delete filtrosSeleccionados[key];
            } else {
                filtrosSeleccionados[key] = select.value;
            }
            cargarVacantes(1);
        });
    });

    limpiar.addEventListener("click", () => {
        selects.forEach(select => select.value = "");
        campoBusqueda.value = "";
        campoUbicacion.value = "";
        filtroBusqueda = "";
        filtroUbicacion = "";
        Object.keys(filtrosSeleccionados).forEach(k => delete filtrosSeleccionados[k]);
        cargarVacantes(1);
    });

    function dispararBusqueda() {
        filtroBusqueda = campoBusqueda.value.trim();
        filtroUbicacion = campoUbicacion.value.trim();
        sugerenciasContainer.style.display = "none";
        sugerenciasUbicacionContainer.style.display = "none";
        cargarVacantes(1);
    }

    btnBuscar.addEventListener("click", dispararBusqueda);
    [campoBusqueda, campoUbicacion].forEach(input => {
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
        cargarVacantes(1);
    });

    document.querySelector(".cerrar-ubicacion").addEventListener("click", () => {
        campoUbicacion.value = "";
        filtroUbicacion = "";
        cargarVacantes(1);
    });

    campoBusqueda.addEventListener("input", () => {
        const texto = campoBusqueda.value.trim();
        if (texto.length < 2) {
            sugerenciasContainer.style.display = "none";
            return;
        }

        fetch(`dao/busquedaSugerencias.php?q=${encodeURIComponent(texto)}`)
            .then(res => res.json())
            .then(sugerencias => {
                sugerenciasContainer.innerHTML = "";
                if (sugerencias.length === 0) {
                    sugerenciasContainer.style.display = "none";
                    return;
                }
                sugerencias.forEach(s => {
                    const li = document.createElement("li");
                    li.textContent = s;
                    li.addEventListener("click", () => {
                        campoBusqueda.value = s;
                        filtroBusqueda = s;
                        sugerenciasContainer.style.display = "none";
                        cargarVacantes(1);
                    });
                    sugerenciasContainer.appendChild(li);
                });
                sugerenciasContainer.style.display = "block";
            });
    });

    campoBusqueda.addEventListener("blur", () => {
        setTimeout(() => sugerenciasContainer.style.display = "none", 200);
    });

    campoUbicacion.addEventListener("input", () => {
        const texto = campoUbicacion.value.trim();
        if (texto.length < 2) {
            sugerenciasUbicacionContainer.style.display = "none";
            return;
        }

        fetch(`dao/busquedaUbicaciones.php?q=${encodeURIComponent(texto)}`)
            .then(res => res.json())
            .then(sugerencias => {
                sugerenciasUbicacionContainer.innerHTML = "";
                if (sugerencias.length === 0) {
                    sugerenciasUbicacionContainer.style.display = "none";
                    return;
                }
                sugerencias.forEach(s => {
                    const li = document.createElement("li");
                    li.textContent = s;
                    li.addEventListener("click", () => {
                        campoUbicacion.value = s;
                        filtroUbicacion = s;
                        sugerenciasUbicacionContainer.style.display = "none";
                        cargarVacantes(1);
                    });
                    sugerenciasUbicacionContainer.appendChild(li);
                });
                sugerenciasUbicacionContainer.style.display = "block";
            });
    });

    campoUbicacion.addEventListener("blur", () => {
        setTimeout(() => sugerenciasUbicacionContainer.style.display = "none", 200);
    });

    cargarVacantes(1);
});


function cargarVacantes(pagina) {
    const limite = 5;
    const params = new URLSearchParams({ pagina, limite });

    for (let key in filtrosSeleccionados) {
        params.append(key, filtrosSeleccionados[key]);
    }

    if (filtroBusqueda) params.append('busqueda', filtroBusqueda);
    if (filtroUbicacion) params.append('ubicacion', filtroUbicacion);

    fetch(`dao/daoVacanteDinamica.php?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            const vacantes = data.vacantes;
            const total = data.total;
            const paginaActual = data.pagina;
            const totalPaginas = Math.ceil(total / limite);

            const lista = document.querySelector(".lista-vacantes");
            const contenedorPaginacion = document.querySelector(".contenedor-paginacion");

            lista.innerHTML = "";
            contenedorPaginacion.innerHTML = "";

            const mensaje = document.querySelector(".mensaje-sin-vacantes");
            const detalleContenido = document.querySelector(".contenido-detalle-vacante");

            if (vacantes.length === 0) {
                mensaje.style.display = "block";
                detalleContenido.style.display = "none";
                return;
            } else {
                mensaje.style.display = "none";
                detalleContenido.style.display = "block";
            }

            const vacantesVistas = JSON.parse(localStorage.getItem('vacantesVistas')) || [];
            let primerItem = null;

            vacantes.forEach((vacante, index) => {
                const item = document.createElement("div");
                item.classList.add("vacante-item");
                item.setAttribute("data-id", vacante.IdVacante);
                if (index === 0) primerItem = item;

                const beneficiosList = (vacante.Beneficios || "")
                    .split(/\n+/)
                    .filter(b => b.trim() !== "")
                    .map(b => `<li>${b.trim()}</li>`)
                    .join("");

                let vistoHTML = '';
                if (vacantesVistas.includes(vacante.IdVacante)) {
                    vistoHTML = `<span class="reciente"><i class="fas fa-check-circle"></i> Vista recientemente.</span>`;
                }

                item.innerHTML = `
                    <p class="fecha">${vacante.FechaPublicacion} ${vistoHTML}</p>
                    <h3>${vacante.Titulo}</h3>
                    <p>${vacante.Sueldo || "Sueldo no mostrado"}</p>
                    <ul>${beneficiosList}</ul>
                    <p class="empresa">Grammer Automotive, S.A. de C.V.</p>
                    <p class="ubicacion">
                        ${vacante.Ciudad}, ${vacante.Estado}
                        <span class="vistas"><i class="fas fa-eye"></i> ${vacante.Visitas}</span>
                    </p>
                `;

                item.addEventListener("click", () => {
                    document.querySelectorAll(".vacante-item").forEach(el => el.classList.remove("activa"));
                    item.classList.add("activa");
                    mostrarDetalle(vacante);

                    // Siempre notificamos al servidor. El PHP decidirá si es una nueva visita para la SESIÓN.
                    const formData = new FormData();
                    formData.append('id', vacante.IdVacante);
                    fetch('dao/registrarVista.php', {
                        method: 'POST',
                        body: formData
                    }).catch(error => console.error('Error al registrar vista:', error));

                    // Actualizamos localStorage solo para el texto "Vista recientemente".
                    const vacantesVistasStorage = JSON.parse(localStorage.getItem('vacantesVistas')) || [];
                    if (!vacantesVistasStorage.includes(vacante.IdVacante)) {
                        vacantesVistasStorage.push(vacante.IdVacante);
                        localStorage.setItem('vacantesVistas', JSON.stringify(vacantesVistasStorage));

                        // Actualizamos el texto visualmente sin recargar
                        const fechaP = item.querySelector(".fecha");
                        if (!fechaP.querySelector(".reciente")) {
                            const span = document.createElement("span");
                            span.innerHTML = ` • <span class="reciente"><i class="fas fa-check-circle"></i> Vista recientemente.</span>`;
                            fechaP.appendChild(span);
                        }
                    }
                });

                lista.appendChild(item);
            });

            if (primerItem) primerItem.click();

            const paginacion = document.createElement("div");
            paginacion.classList.add("paginacion-vacantes");
            const btnPrev = document.createElement("button");
            btnPrev.textContent = "<";
            btnPrev.className = "btn-pagina";
            btnPrev.disabled = paginaActual === 1;
            btnPrev.addEventListener("click", () => cargarVacantes(paginaActual - 1));
            paginacion.appendChild(btnPrev);
            for (let i = 1; i <= totalPaginas; i++) {
                const btn = document.createElement("button");
                btn.textContent = i;
                btn.className = "btn-pagina";
                if (i === paginaActual) btn.classList.add("activa");
                btn.addEventListener("click", () => cargarVacantes(i));
                paginacion.appendChild(btn);
            }
            const btnNext = document.createElement("button");
            btnNext.textContent = ">";
            btnNext.className = "btn-pagina";
            btnNext.disabled = paginaActual === totalPaginas;
            btnNext.addEventListener("click", () => cargarVacantes(paginaActual + 1));
            paginacion.appendChild(btnNext);
            contenedorPaginacion.appendChild(paginacion);
        });
}


function textoAListasHTML(texto) {
    if (!texto) return "<p>No hay información disponible</p>";
    const lineas = texto.split('\n').filter(linea => linea.trim() !== '');
    const listaItems = lineas.map(linea => `<li>${linea.trim()}</li>`).join('');
    return `<ul>${listaItems}</ul>`;
}

function mostrarDetalle(vacante) {
    document.getElementById("imagenVacante").src = vacante.Imagen || "imagenes/default.jpg";
    document.querySelector(".detalle-vacante .fecha").textContent = vacante.FechaPublicacion;
    document.querySelector(".detalle-vacante h2").textContent = vacante.Titulo;

    let textoSueldo = vacante.Sueldo && vacante.Sueldo.trim() !== ""
        ? `<strong>${vacante.Sueldo}</strong><br>`
        : "Si el reclutador te contacta podrás conocer el sueldo<br>";

    document.querySelector(".detalle-vacante .descripcion").innerHTML =
        `${textoSueldo}<strong>Grammer Automotive, S.A. de C.V.</strong> en ${vacante.Ciudad}, ${vacante.Estado}`;

    document.querySelector(".btn-postularme").onclick = () => {
        window.location.href = `postularme.php?id=${vacante.IdVacante}`;
    };

    document.getElementById("previewArea").textContent = vacante.Area;
    document.getElementById("previewescolaridad").textContent = vacante.Escolaridad;
    document.getElementById("previewIdioma").textContent = vacante.Idioma;
    document.getElementById("previewEspecialidad").textContent = vacante.Especialidad;
    document.getElementById("previewHorario").textContent = vacante.Horario;
    document.getElementById("previewEspacio").textContent = vacante.EspacioTrabajo;

    document.getElementById("previewRequisitos").innerHTML = textoAListasHTML(vacante.Requisitos);
    document.getElementById("previewBeneficios").innerHTML = textoAListasHTML(vacante.Beneficios);
    document.getElementById("previewDescripcion").innerHTML = vacante.Descripcion.replace(/\n/g, '<br>');

    if (typeof usuario !== "undefined" && usuario !== null) {
        mostrarCompatibilidad(vacante, usuario);
    } else {
        const compatDiv = document.querySelector(".compatibilidad");
        compatDiv.innerHTML = `<p style="font-size: 0.9em; color: #666;">Inicia sesión para ver tu compatibilidad con esta vacante.</p>`;
    }
}

function mostrarCompatibilidad(vacante, usuario) {
    const compatDiv = document.querySelector(".compatibilidad");
    const checks = [];
    let sueldoOk = false;
    if (vacante.Sueldo && usuario.sueldoEsperado && !isNaN(usuario.sueldoEsperado)) {
        const sueldoVacante = parseInt(vacante.Sueldo.replace(/\D/g, '')) || 0;
        sueldoOk = usuario.sueldoEsperado <= sueldoVacante;
    }
    checks.push({
        label: "Sueldo",
        compatible: sueldoOk,
        mensaje: sueldoOk ? "Entras en el rango" : "Fuera de tu expectativa"
    });
    const ubicacionVacante = normalizarTexto(`${vacante.Ciudad} ${vacante.Estado}`);
    const ubicacionUsuario = normalizarTexto(usuario.ubicacion);
    const ubicacionOk = ubicacionVacante.includes(ubicacionUsuario);
    checks.push({
        label: "Ubicación",
        compatible: ubicacionOk,
        mensaje: ubicacionOk ? "Estás en el lugar correcto" : "Fuera de tu zona"
    });
    const escOk = vacante.Escolaridad && usuario.escolaridad ? vacante.Escolaridad.toLowerCase().includes(usuario.escolaridad.toLowerCase()) : false;
    checks.push({
        label: "Educación",
        compatible: escOk,
        mensaje: escOk ? "Cumples con lo necesario" : "Nivel diferente al requerido"
    });
    const areaOk = vacante.Area && usuario.area ? vacante.Area.toLowerCase().includes(usuario.area.toLowerCase()) : false;
    checks.push({
        label: "Área",
        compatible: areaOk,
        mensaje: areaOk ? "Compatible con el puesto" : "No coincide tu área"
    });
    compatDiv.innerHTML = checks.map(check => `
        <div class="${check.compatible ? '' : 'no-compatible'}">
            <i class="fas ${check.compatible ? 'fa-check-circle' : 'fa-times-circle'}"></i>
            ${check.label} <span>${check.mensaje}</span>
        </div>
    `).join('');
}

function normalizarTexto(texto) {
    if (!texto) return "";
    return texto.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "").replace(/,/g, "").replace(/\s+/g, " ").trim();
}