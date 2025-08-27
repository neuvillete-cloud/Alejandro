<?php
session_start();
if (!isset($_SESSION['NumNomina'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estadísticas de Vacantes | ATS Grammer</title>
    <link rel="stylesheet" href="css/estilosEstadisticas.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<header>
</header>

<section class="section-title">
    <h1>Panel de Vacantes</h1>
    <img src="imagenes/analitica.png" alt="Imagen decorativa"> </section>

<section class="area-blanca">
    <div class="contenido-blanco">
        <div id="vacantes-container">
        </div>
    </div>
</section>

<footer class="main-footer">
</footer>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const container = document.getElementById('vacantes-container');

        function renderizarVacantes(vacantes) {
            container.innerHTML = '';
            if (!vacantes || vacantes.length === 0) {
                container.innerHTML = '<p>No se encontraron vacantes.</p>';
                return;
            }

            vacantes.forEach(vacante => {
                const fechaCreacion = new Date(vacante.FechaCreacion).toLocaleDateString('es-MX', {
                    day: '2-digit', month: '2-digit', year: 'numeric'
                });

                const cardHTML = `
                <div class="vacante-card">
                    <div class="card-header">
                        <div>
                            <h2 class="titulo-vacante">${vacante.TituloVacante}</h2>
                            <p class="ubicacion-vacante">${vacante.Ciudad}, ${vacante.Estado}</p>
                        </div>
                        <div class="estatus-vacante">
                            <span class="stat-etiqueta">${vacante.EstatusVacante}</span>
                        </div>
                    </div>

                    <div class="stats-container">
                        <div class="stat-item">
                            <div class="stat-numero">-</div>
                            <div class="stat-etiqueta">Visitas</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-numero">${vacante.PorRevisar}</div>
                            <div class="stat-etiqueta">Por Revisar</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-numero">${vacante.MeInteresan}</div>
                            <div class="stat-etiqueta">Me Interesan</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-numero">${vacante.Descartados}</div>
                            <div class="stat-etiqueta">Descartados</div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="info-creacion">
                            Creada el ${fechaCreacion} | ID: ${vacante.IdVacante}
                        </div>
                        <div class="acciones-container">
                            <a href="#" class="btn-accion btn-editar"><i class="fas fa-pencil-alt"></i> Editar</a>
                            <a href="#" class="btn-accion btn-visualizar"><i class="fas fa-eye"></i> Visualizar</a>
                        </div>
                    </div>
                </div>
            `;
                container.innerHTML += cardHTML;
            });
        }

        // Cargar las estadísticas al iniciar la página
        fetch('dao/daoEstadisticasVacantes.php')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    renderizarVacantes(data.data);
                } else {
                    container.innerHTML = `<p>Error al cargar las estadísticas: ${data.message}</p>`;
                }
            })
            .catch(error => {
                console.error('Error en el fetch:', error);
                container.innerHTML = '<p>Error de conexión al servidor.</p>';
            });
    });
</script>

</body>
</html>