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
    const nombreAprobador = document.getElementById("nombreAprobador");

    btnAbrirModal.addEventListener("click", () => {
        modal.style.display = "flex";
    });

    btnCerrarModal.addEventListener("click", () => {
        modal.style.display = "none";
    });

    btnConfirmarAccion.addEventListener("click", () => {
        const accionSeleccionada = selectAccion.value;
        const comentarioTexto = comentario.value.trim();
        const nombreAprobadorTexto = nombreAprobador.value.trim();

        // Validaciones con SweetAlert
        if (!nombreAprobadorTexto) {
            Swal.fire({
                icon: 'error',
                title: 'Nombre faltante',
                text: 'Debes ingresar tu nombre.',
            });
            return;
        }

        if (accionSeleccionada === "rechazar" && comentarioTexto === "") {
            Swal.fire({
                icon: 'error',
                title: 'Comentario requerido',
                text: 'Debes ingresar un comentario si rechazas la solicitud.',
            });
            return;
        }

        // Crear un objeto FormData para enviar los datos
        const formData = new FormData();
        formData.append('nombreAprobador', nombreAprobadorTexto);
        formData.append('accion', accionSeleccionada);
        formData.append('folio', new URLSearchParams(window.location.search).get("folio"));

        // Solo agregar el comentario si la acción es "rechazar"
        if (accionSeleccionada === "rechazar") {
            formData.append('comentario', comentarioTexto);
        }

        // Enviar los datos al servidor mediante fetch
        fetch('dao/daoAprobacionS.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: `Solicitud ${accionSeleccionada} con éxito`,
                        text: `La solicitud ha sido ${accionSeleccionada} exitosamente.`,
                    }).then(() => {
                        modal.style.display = "none";
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error en la solicitud',
                        text: data.message,
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error del servidor',
                    text: 'Hubo un problema en la comunicación con el servidor. Por favor, inténtelo de nuevo más tarde.',
                });
            });
    });
}
