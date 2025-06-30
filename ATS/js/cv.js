document.getElementById("formPostulacion").addEventListener("submit", function (e) {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);

    // Obtener ID de vacante desde la URL
    const params = new URLSearchParams(window.location.search);
    const idVacante = params.get("id");

    if (!idVacante) {
        Swal.fire("Error", "No se encontró el ID de la vacante en la URL.", "error");
        return;
    }

    // Agregar archivo manualmente si hiciste carga personalizada (opcional)
    const archivo = document.getElementById("cvFile").files[0];
    if (archivo) {
        formData.append("cv", archivo);
    } else {
        Swal.fire("Error", "Debes subir un archivo CV antes de continuar.", "error");
        return;
    }

    fetch(`dao/subirPostulacion.php?idVacante=${idVacante}`, {
        method: "POST",
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.status === "success") {
                Swal.fire("¡Postulación exitosa!", data.message, "success")
                    .then(() => window.location.href = "perfil.php");
            } else {
                Swal.fire("Error", data.message, "error");
            }
        })

        .catch(error => {
            console.error("Error al enviar postulación:", error);
            Swal.fire("Error", "Ocurrió un problema al enviar tu postulación.", "error");
        });
});