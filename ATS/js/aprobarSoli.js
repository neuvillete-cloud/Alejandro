document.addEventListener("DOMContentLoaded", function () {
    // Obtener parámetros de la URL
    const urlParams = new URLSearchParams(window.location.search);
    const folio = urlParams.get("folio"); // Tomamos el folio de la URL

    if (folio) {
        fetch(`dao/daoAprobarSolicitud.php?folio=${folio}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === "success") { // Validamos el estado correcto
                    const solicitud = data.data; // Extraemos los datos correctamente

                    // Asignamos los valores a los elementos HTML
                    document.getElementById("nombre").textContent = solicitud.Nombre || "N/A";
                    document.getElementById("area").textContent = solicitud.IdArea || "N/A"; // Ajustar si hay una relación con otra tabla
                    document.getElementById("puesto").textContent = solicitud.Puesto || "N/A";
                    document.getElementById("tipo").textContent = solicitud.TipoContratacion || "N/A";
                    document.getElementById("NombreReemplazo").textContent = solicitud.NombreReemplazo || "N/A"; // Si aplica, puede ser otro campo relevante
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
});

