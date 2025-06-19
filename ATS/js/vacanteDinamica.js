const filtrosSeleccionados = {};

document.addEventListener("DOMContentLoaded", function () {
    const salario = document.getElementById('filtro-salario');
    const fecha = document.getElementById('filtro-fecha');
    const modalidad = document.getElementById('filtro-modalidad');
    const contrato = document.getElementById('filtro-contrato');
    const educacion = document.getElementById('filtro-educacion');
    const limpiar = document.getElementById('limpiar-filtros');

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
        Object.keys(filtrosSeleccionados).forEach(k => delete filtrosSeleccionados[k]);
        cargarVacantes(1);
    });

    cargarVacantes(1);
});

function cargarVacantes(pagina) {
    const limite = 5;
    const params = new URLSearchParams({ pagina, limite });

    for (let key in filtrosSeleccionados) {
        params.append(key, filtrosSeleccionados[key]);
    }

    fetch(`dao/daoVacanteDinamica.php?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            const vacantes = data.vacantes;
            const total = data.total;
            const paginaActual = data.pagina;
            const totalPaginas = Math.ceil(total / limite);

            const lista = document.querySelector(".lista-vacantes");
            const detalle = document.querySelector(".detalle-vacante");
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

                const beneficiosList = vacante.Beneficios
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
                    <p class="ubicacion">${vacante.Ciudad}, ${vacante.Estado}</p>
                `;

                item.addEventListener("click", () => {
                    document.querySelectorAll(".vacante-item").forEach(el => el.classList.remove("activa"));
                    item.classList.add("activa");

                    mostrarDetalle(vacante);

                    if (!vacantesVistas.includes(vacante.IdVacante)) {
                        vacantesVistas.push(vacante.IdVacante);
                        localStorage.setItem('vacantesVistas', JSON.stringify(vacantesVistas));
                    }

                    const fechaP = item.querySelector(".fecha");
                    if (!fechaP.querySelector(".reciente")) {
                        const span = document.createElement("span");
                        span.classList.add("reciente");
                        span.innerHTML = `<i class="fas fa-check-circle"></i> Vista recientemente.`;
                        fechaP.appendChild(document.createTextNode(" • "));
                        fechaP.appendChild(span);
                    }
                });

                lista.appendChild(item);
            });

            if (primerItem) primerItem.click(); // Auto click para mostrar detalles

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

    document.getElementById("previewArea").textContent = vacante.Area;
    document.getElementById("previewescolaridad").textContent = vacante.Escolaridad;
    document.getElementById("previewIdioma").textContent = vacante.Idioma;
    document.getElementById("previewEspecialidad").textContent = vacante.Especialidad;
    document.getElementById("previewHorario").textContent = vacante.Horario;
    document.getElementById("previewEspacio").textContent = vacante.EspacioTrabajo;

    document.getElementById("previewRequisitos").innerHTML = textoAListasHTML(vacante.Requisitos);
    document.getElementById("previewBeneficios").innerHTML = textoAListasHTML(vacante.Beneficios);
    document.getElementById("previewDescripcion").innerHTML = vacante.Descripcion.replace(/\n/g, '<br>');

    if (typeof usuario !== "undefined") {
        mostrarCompatibilidad(vacante, usuario);
    }
}

function mostrarCompatibilidad(vacante, usuario) {
    const compatDiv = document.querySelector(".compatibilidad");
    if (!usuario) {
        compatDiv.innerHTML = `<p>Inicia sesión para ver tu compatibilidad con esta vacante.</p>`;
        return;
    }

    const checks = [];

    let sueldoOk = false;
    if (vacante.Sueldo && !isNaN(usuario.sueldoEsperado)) {
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

    const escOk = vacante.Escolaridad.toLowerCase().includes(usuario.escolaridad.toLowerCase());
    checks.push({
        label: "Educación",
        compatible: escOk,
        mensaje: escOk ? "Cumples con lo necesario" : "Nivel diferente al requerido"
    });

    const areaOk = vacante.Area.toLowerCase().includes(usuario.area.toLowerCase());
    checks.push({
        label: "Área",
        compatible: areaOk,
        mensaje: areaOk ? "Compatible con el puesto" : "No coincide tu área"
    });

    compatDiv.innerHTML = checks.map(check => `
        <div class="${check.compatible ? '' : 'no-compatible'}">
            <i class="fas ${check.compatible ? 'fa-check-circle' : 'fa-sad-tear'}"></i>
            ${check.label} <span>${check.mensaje}</span>
        </div>
    `).join('');
}

function normalizarTexto(texto) {
    return texto
        .toLowerCase()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .replace(/,/g, "")
        .replace(/\s+/g, " ")
        .trim();
}
