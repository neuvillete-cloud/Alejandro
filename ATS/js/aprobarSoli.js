document.addEventListener("DOMContentLoaded", function () {
    obtenerSolicitud();
    manejarModal();
});

function obtenerSolicitud() {
    const urlParams = new URLSearchParams(window.location.search);
    const folio = urlParams.get("folio");

    if (folio) {
        fetch(`dao/daoAprobarSolicitud.php?folio=${folio}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === "success") {
                    const solicitud = data.data;
                    document.getElementById("nombre").textContent = solicitud.Nombre || "N/A";
                    document.getElementById("area").textContent = solicitud.NombreArea || "N/A";
                    document.getElementById("puesto").textContent = solicitud.Puesto || "N/A";
                    document.getElementById("tipo").textContent = solicitud.TipoContratacion || "N/A";
                    document.getElementById("NombreReemplazo").textContent = solicitud.NombreReemplazo || "N/A";
                    document.getElementById("FechaSolicitud").textContent = solicitud.FechaSolicitud || "N/A";
                    document.getElementById("FolioSolicitud").textContent = solicitud.FolioSolicitud || "N/A";
                } else {
                    document.querySelector(".solicitud").innerHTML = `<p>No se encontró la solicitud.</p>`;
                }
            })
            .catch(error => console.error("Error al cargar la solicitud:", error));
    } else {
        document.querySelector(".solicitud").innerHTML = `<p>No se proporcionó un folio.</p>`;
    }
}

function manejarModal() {
    const modal = document.getElementById("modalAprobacion");
    const btnAbrirModal = document.querySelector(".boton-aceptar");
    const btnCerrarModal = document.getElementById("cerrarModal");
    const btnConfirmarAccion = document.getElementById("confirmarAccion");
    const selectAccion = document.getElementById("accion");
    const comentario = document.getElementById("comentario");

    btnAbrirModal.addEventListener("click", () => {
        modal.style.display = "flex";
    });

    btnCerrarModal.addEventListener("click", () => {
        modal.style.display = "none";
    });

    btnConfirmarAccion.addEventListener("click", () => {
        const accionSeleccionada = selectAccion.value;
        const comentarioTexto = comentario.value.trim();

        if (accionSeleccionada === "rechazar" && comentarioTexto === "") {
            alert("Debes ingresar un comentario si rechazas la solicitud.");
            return;
        }

        alert(`Solicitud ${accionSeleccionada} con éxito.`);
        modal.style.display = "none";
    });
}
