document.addEventListener("DOMContentLoaded", function () {
    // Obtener el contenedor donde se mostrarán los candidatos
    const contenedor = document.getElementById('contenedorCandidatos');


    if (!contenedor) return;

    // Cargar los candidatos seleccionados al iniciar la página
    fetch('dao/obtenerCandidatoFinal.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Error al obtener los candidatos');
            }
            return response.json();
        })
        .then(data => {
            if (!Array.isArray(data) || data.length === 0) {
                contenedor.innerHTML = '<p class="mensaje-vacio">No hay candidatos seleccionados aún.</p>';
                return;
            }

            // Generar tarjetas
            contenedor.innerHTML = '';
            data.forEach(candidato => {
                const card = document.createElement('div');
                card.classList.add('candidato-card');

                // Dentro de data.forEach...
                card.innerHTML = `
                    <div class="foto-candidato">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <div class="info-candidato">
                        <h3>${candidato.NombreCompleto}</h3>
                        <p><strong>Puesto:</strong> ${candidato.TituloVacante}</p>
                        <p><strong>Área:</strong> ${candidato.NombreArea}</p>
                        <p><strong>Seleccionado por:</strong> ${candidato.NombreSelector}</p>
                    </div>
                `;

                contenedor.appendChild(card);
            });
        })
        .catch(error => {
            console.error('Error:', error);
            contenedor.innerHTML = '<p class="mensaje-error">Hubo un error al cargar los candidatos.</p>';
        });
});
