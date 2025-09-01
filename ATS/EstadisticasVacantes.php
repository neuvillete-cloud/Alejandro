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

<?php
session_start();
if (!isset($_SESSION['NumNomina'])) {
    header('Location: login.php');
    exit;
}
?>

<header>
    <div class="header-container">
        <div class="logo">
            <img src="imagenes/logo_blanco.png" alt="Logo Grammer" class="logo-img">
            <div class="logo-texto">
                <h1>Grammer</h1>
                <span>Automotive</span>
            </div>
        </div>
        <nav>
            <a href="#">Nueva Solicitud</a>
            <a href="seguimiento.php">Seguimiento</a>
            <a href="historicos.php">Historial de Solicitudes</a>
            <a href="seleccionFinal.php">Candidatos Finales</a>

            <?php if (isset($_SESSION['Nombre'])): ?>
                <div class="user-menu">
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span><?= htmlspecialchars($_SESSION['Nombre']) ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="dropdown-menu">
                        <a href="perfilUsuarios.php">Perfil</a>
                        <a href="#" id="logout">Cerrar sesión</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php">Inicio de sesión</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<section class="section-title">
    <h1>Panel de Vacantes</h1>
    <img src="imagenes/analitica.png" alt="Imagen decorativa" class="imagen-banner">
</section>

<section class="area-blanca">
    <div class="contenido-blanco">
        <div id="vacantes-container">
        </div>
    </div>
</section>



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

                // --- LÓGICA PARA EL ESTATUS PERSONALIZADO ---
                let estatusTexto = vacante.EstatusVacante; // Texto por defecto
                let estatusClase = ''; // Clase CSS para el color

                if (vacante.IdEstatus == 1) {
                    estatusTexto = 'Abierta';
                    estatusClase = 'estatus-abierta';
                } else if (vacante.IdEstatus == 11) {
                    estatusTexto = 'Cerrada';
                    estatusClase = 'estatus-cerrada';
                }
                // --- FIN DE LA LÓGICA ---

                const cardHTML = `
                <div class="vacante-card">
                    <div class="card-header">
                        <div>
                            <h2 class="titulo-vacante">${vacante.TituloVacante}</h2>
                            <p class="ubicacion-vacante">${vacante.Ciudad}, ${vacante.Estado}</p>
                        </div>
                        <div class="estatus-vacante">
                            <span class="etiqueta-estatus ${estatusClase}">${estatusTexto}</span>
                        </div>
                    </div>

                    <div class="stats-container">
                        <div class="stat-item">
                            <div class="stat-numero">${vacante.Visitas}</div>
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
                            <a href="cargaVacante.php?edit=${vacante.IdVacante}" class="btn-accion btn-editar"><i class="fas fa-pencil-alt"></i> Editar</a>
                            <a href="vacantes.php?preview=${vacante.IdVacante}" target="_blank" class="btn-accion btn-visualizar"><i class="fas fa-eye"></i> Visualizar</a>
                        </div>
                    </div>
                </div>
            `;
                container.innerHTML += cardHTML;
            });
        }

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
<footer class="main-footer">
    <div class="footer-container">

        <div class="footer-column">
            <div class="logo">
                <img src="imagenes/logo_blanco.png" alt="Logo Grammer Blanco" class="logo-img">
                <div class="logo-texto">
                    <h1>Grammer</h1>
                    <span>Automotive</span>
                </div>
            </div>
            <p class="footer-about">
                Sistema de Seguimiento de Candidatos (ATS) para la gestión de talento y requisiciones de personal.
            </p>
        </div>

        <div class="footer-column">
            <h3>Enlaces Rápidos</h3>
            <ul class="footer-links">
                <li><a href="Administrador.php">Inicio</a></li>
                <li><a href="SAprobadas.php">Solicitudes Aprobadas</a></li>
                <li><a href="SeguimientoAdministrador.php">Seguimiento</a></li>
                <li><a href="cargaVacante.php">Carga de Vacantes</a></li>
            </ul>
        </div>

        <div class="footer-column">
            <h3>Contacto</h3>
            <p><i class="fas fa-map-marker-alt"></i> Av. de la Luz #24 Col. satélite , Querétaro, Mexico</p>
            <p><i class="fas fa-phone"></i> +52 (442) 238 4460</p>
            <div class="social-icons">
                <a href="https://www.google.com/url?sa=t&rct=j&q=&esrc=s&source=web&cd=&cad=rja&uact=8&ved=2ahUKEwiA6MqY0KaPAxUmlGoFHX01AXwQFnoECD0QAQ&url=https%3A%2F%2Fwww.facebook.com%2Fgrammermexico%2F%3Flocale%3Des_LA&usg=AOvVaw1Jg2xRElzuIF1PIZ6Ip_Ms&opi=89978449" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="https://mx.linkedin.com/company/grammer-automotive-puebla-s-a-de-c-v-" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                <a href="https://www.instagram.com/grammerqro/" aria-label="Instagram"><i class="fab fa-instagram"></i></a>            </div>
        </div>

    </div>
    <div class="sub-footer">
        <p>&copy; <?= date('Y') ?> Grammer Automotive de México. Todos los derechos reservados.</p>
        <p class="developer-credit">Desarrollado con <i class="fas fa-heart"></i> por Alejandro Torres Jimenez</p>
    </div>
</footer>
</body>
</html>