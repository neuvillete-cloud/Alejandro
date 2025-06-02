document.addEventListener('DOMContentLoaded', function () {
    const listaVacantes = document.querySelector('.lista-vacantes');

    // 1. Cargar todas las vacantes automáticamente
    fetch('cargarVacantes.php')
        .then(response => response.text())
        .then(html => {
            listaVacantes.innerHTML = html;
            activarClicks(); // Para que funcionen los clicks después de insertar
        });

    // 2. Activar los clics en vacantes cargadas
    function activarClicks() {
        const tarjetas = document.querySelectorAll('.vacante-item');
        const panelDetalle = document.getElementById('vacante-detalle');

        tarjetas.forEach(tarjeta => {
            tarjeta.addEventListener('click', function () {
                const idVacante = this.dataset.id;

                panelDetalle.innerHTML = '<p>Cargando información de la vacante...</p>';

                fetch(`obtenerVacante.php?id=${idVacante}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            panelDetalle.innerHTML = `<p>${data.error}</p>`;
                        } else {
                            panelDetalle.innerHTML = `
                                <div class="detalle-header">
                                    <span>Hace ${data.fecha}</span>
                                    <h2>${data.titulo}</h2>
                                    <p>${data.descripcion}</p>
                                    <p><strong>${data.empresa}</strong> en ${data.ubicacion}</p>
                                    ${data.verificada ? '<p class="verificada">Empresa verificada</p>' : ''}
                                    <button class="btn-postular">Postularme</button>
                                </div>
                                <hr>
                                <div class="detalle-compatibilidad">
                                    <h3>Conoce tu compatibilidad con la vacante</h3>
                                    <ul>
                                        <li><strong>Sueldo:</strong> ${data.sueldo}</li>
                                        <li><strong>Ubicación:</strong> ${data.ubicacion_match}</li>
                                        <li><strong>Educación:</strong> ${data.educacion}</li>
                                        <li><strong>Área:</strong> ${data.area}</li>
                                    </ul>
                                </div>
                            `;
                        }
                    })
                    .catch(error => {
                        panelDetalle.innerHTML = `<p>Error al obtener la vacante.</p>`;
                        console.error('Error:', error);
                    });
            });
        });
    }
});
