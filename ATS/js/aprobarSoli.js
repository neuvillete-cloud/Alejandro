document.addEventListener("DOMContentLoaded", function() {
    // Obtener parámetros de la URL
    const urlParams = new URLSearchParams(window.location.search);
    const folio = urlParams.get("folio"); // Tomamos el folio de la URL

    if (folio) {
        fetch(`php/obtenerSolicitud.php?folio=${folio}`) // No hay que cambiar esto, ya que es el nombre del parámetro
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById("nombre").textContent = data.nombre;
                    document.getElementById("area").textContent = data.area;
                    document.getElementById("puesto").textContent = data.puesto;
                    document.getElementById("tipo").textContent = data.tipo;
                    document.getElementById("descripcion").textContent = data.descripcion;
                } else {
                    document.querySelector(".solicitud").innerHTML = `<p>No se encontró la solicitud.</p>`;
                }
            })
            .catch(error => console.error("Error al cargar la solicitud:", error));
    } else {
        document.querySelector(".solicitud").innerHTML = `<p>No se proporcionó un folio.</p>`;
    }
});
