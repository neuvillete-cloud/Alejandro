document.addEventListener("DOMContentLoaded", function () {
    fetch("dao/daoVacanteDinamica.php")
        .then(response => response.json())
        .then(vacantes => {
            const lista = document.querySelector(".lista-vacantes");
            const detalle = document.querySelector(".detalle-vacante");

            lista.innerHTML = "";

            // Obtener vacantes vistas desde localStorage
            const vacantesVistas = JSON.parse(localStorage.getItem('vacantesVistas')) || [];

            vacantes.forEach((vacante, index) => {
                const item = document.createElement("div");
                item.classList.add("vacante-item");
                item.setAttribute("data-id", vacante.IdVacante); // ID único

                if (index === 0) item.classList.add("activa");

                const beneficiosList = vacante.Beneficios
                    .split(/\n+/)
                    .filter(b => b.trim() !== "")
                    .map(b => `<li>${b.trim()}</li>`)
                    .join("");

                // Etiqueta "Visto recientemente" si aplica
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

                    // Marcar como vista
                    if (!vacantesVistas.includes(vacante.IdVacante)) {
                        vacantesVistas.push(vacante.IdVacante);
                        localStorage.setItem('vacantesVistas', JSON.stringify(vacantesVistas));
                    }

                    // Añadir texto "Visto recientemente" si no lo tiene
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

        });
});

function textoAListasHTML(texto) {
    if (!texto) return "<p>No hay información disponible</p>";
    const lineas = texto.split('\n').filter(linea => linea.trim() !== '');
    const listaItems = lineas.map(linea => `<li>${linea.trim()}</li>`).join('');
    return `<ul>${listaItems}</ul>`;
}

function mostrarDetalle(vacante) {
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
    document.getElementById("previewHorario").textContent = vacante.Horario;
    document.getElementById("previewEspacio").textContent = vacante.EspacioTrabajo;

    document.getElementById("previewRequisitos").innerHTML = textoAListasHTML(vacante.Requisitos);
    document.getElementById("previewBeneficios").innerHTML = textoAListasHTML(vacante.Beneficios);
    document.getElementById("previewDescripcion").innerHTML = vacante.Descripcion.replace(/\n/g, '<br>');
}
