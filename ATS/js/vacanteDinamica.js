document.addEventListener("DOMContentLoaded", function () {
    fetch("dao/daoVacanteDinamica.php")
        .then(response => response.json())
        .then(vacantes => {
            const lista = document.querySelector(".lista-vacantes");
            const detalle = document.querySelector(".detalle-vacante");

            lista.innerHTML = "";

            const vacantesVistas = JSON.parse(localStorage.getItem('vacantesVistas')) || [];

            vacantes.forEach((vacante, index) => {
                const item = document.createElement("div");
                item.classList.add("vacante-item");
                item.setAttribute("data-id", vacante.IdVacante);

                if (index === 0) item.classList.add("activa");

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
                    <p>${vacante.Sueldo ? vacante.Sueldo : "Sueldo no mostrado"}</p>
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

                if (index === 0) mostrarDetalle(vacante);
            });

            // Agregar paginación estática visual al final
            const paginacion = document.createElement("div");
            paginacion.classList.add("paginacion-vacantes");
            paginacion.innerHTML = `
                <button class="btn-pagina" disabled>&lt;</button>
                <button class="btn-pagina activa">1</button>
                <button class="btn-pagina">2</button>
                <button class="btn-pagina">3</button>
                <button class="btn-pagina">&gt;</button>
            `;
            document.querySelector(".contenedor-paginacion").appendChild(paginacion);



        });
});

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

    // ✅ MOSTRAR COMPATIBILIDAD
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

    // Sueldo
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

    // Ubicación
    const ubicacionVacante = normalizarTexto(`${vacante.Ciudad} ${vacante.Estado}`);
    const ubicacionUsuario = normalizarTexto(usuario.ubicacion);

    const ubicacionOk = ubicacionVacante.includes(ubicacionUsuario);

    checks.push({
        label: "Ubicación",
        compatible: ubicacionOk,
        mensaje: ubicacionOk ? "Estás en el lugar correcto" : "Fuera de tu zona"
    });

    // Escolaridad
    const escOk = vacante.Escolaridad.toLowerCase().includes(usuario.escolaridad.toLowerCase());
    checks.push({
        label: "Educación",
        compatible: escOk,
        mensaje: escOk ? "Cumples con lo necesario" : "Nivel diferente al requerido"
    });

    // Área
    const areaOk = vacante.Area.toLowerCase().includes(usuario.area.toLowerCase());
    checks.push({
        label: "Área",
        compatible: areaOk,
        mensaje: areaOk ? "Compatible con el puesto" : "No coincide tu área"
    });

    // Mostrar resultados
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
        .normalize("NFD") // Quita tildes
        .replace(/[\u0300-\u036f]/g, "")
        .replace(/,/g, "") // Quita comas
        .replace(/\s+/g, " ") // Colapsa múltiples espacios
        .trim();
}
