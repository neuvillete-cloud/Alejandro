document.addEventListener("DOMContentLoaded", function () {
    fetch("dao/daoVacanteDinamica.php")
        .then(response => response.json())
        .then(vacantes => {
            const lista = document.querySelector(".lista-vacantes");
            const detalle = document.querySelector(".detalle-vacante");

            lista.innerHTML = "";

            vacantes.forEach((vacante, index) => {
                const item = document.createElement("div");
                item.classList.add("vacante-item");
                if (index === 0) item.classList.add("activa");

                item.innerHTML = `
                    <p class="fecha">${vacante.FechaPublicacion}</p>
                    <h3>${vacante.Titulo}</h3>
                    <p>${vacante.Sueldo ? vacante.Sueldo : "Sueldo no mostrado"}</p>
                    <ul>
                        <li>${vacante.Beneficios.split(',')[0]}</li>
                    </ul>
                    <p class="empresa">Grammer Automotive, S.A. de C.V.</p>
                    <p class="ubicacion">${vacante.Ciudad}, ${vacante.Estado}</p>
                `;

                item.addEventListener("click", () => {
                    document.querySelectorAll(".vacante-item").forEach(el => el.classList.remove("activa"));
                    item.classList.add("activa");
                    mostrarDetalle(vacante);
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
