document.addEventListener("DOMContentLoaded", function () {
    const contenedor = document.getElementById('contenedorCandidatos');

    if (!contenedor) return;

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

            contenedor.innerHTML = '';
            data.forEach(candidato => {
                const card = document.createElement('div');
                card.classList.add('candidato-card');

                // 👉 Formato mexicano de fecha
                let fechaOriginal = candidato.FechaSeleccion;
                let fechaFormateada = 'Sin fecha';

                if (fechaOriginal && fechaOriginal !== '0000-00-00 00:00:00') {
                    const fecha = new Date(fechaOriginal);
                    const opciones = { day: 'numeric', month: 'long', year: 'numeric' };
                    fechaFormateada = fecha.toLocaleDateString('es-MX', opciones);
                }

                card.innerHTML = `
                    <div class="foto-candidato">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <div class="info-candidato">
                        <h3>${candidato.NombreCompleto}</h3>
                        <p><strong>Puesto:</strong> ${candidato.TituloVacante}</p>
                        <p><strong>Área:</strong> ${candidato.NombreArea}</p>
                        <p><strong>Seleccionado por:</strong> ${candidato.NombreSelector}</p>
                        <p><strong>Correo:</strong> <a href="mailto:${candidato.Correo}">${candidato.Correo}</a></p>
                        <p><strong>Teléfono:</strong> <a href="tel:${candidato.Telefono}">${candidato.Telefono}</a></p>
                        <p><strong>Fecha de selección:</strong> ${fechaFormateada}</p>
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
