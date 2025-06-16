<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vacantes en Grammer Automotive</title>
    <link rel="stylesheet" href="css/vacantes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php
session_start();
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
            <a href="#">Buscar empleos</a>
            <a href="aboutUs.php">Acerca de nosotros</a>
            <a href="practicantes.php">Programa de posgrado</a>
            <a href="#">Inclusión y diversidad</a>

            <?php if (isset($_SESSION['NombreCandidato'])): ?>
                <div class="user-menu">
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span><?= htmlspecialchars($_SESSION['NombreCandidato']) ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="dropdown-menu">
                        <a href="perfil.php">Perfil</a>
                        <a href="#">Alertas de empleo</a>
                        <a href="#">Historial de solicitudes</a>
                        <a href="cerrarSesion.php">Cerrar sesión</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="loginATS.php">Inicio de sesión</a>
            <?php endif; ?>

        </nav>
    </div>
</header>

<section class="section-title">
    <h1>Vacantes en Grammer Automotive</h1>
</section>

<section class="area-blanca">
    <div class="contenido-blanco">
        <div class="buscador-vacantes">
            <div class="fila-superior">
                <div class="campo-busqueda">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="practicante de ingeniería">
                    <i class="fas fa-times cerrar-busqueda"></i>
                </div>

                <div class="campo-ubicacion">
                    <i class="fas fa-map-marker-alt"></i>
                    <input type="text" placeholder="Querétaro">
                    <i class="fas fa-times cerrar-ubicacion"></i>
                </div>

                <button class="btn-buscar">Buscar empleo</button>
            </div>

            <div class="filtros">
                <button class="filtro">$5,000 - $10,000 <i class="fas fa-chevron-down"></i></button>
                <button class="filtro">Fecha <i class="fas fa-chevron-down"></i></button>
                <button class="filtro">Presencial/Desde casa <i class="fas fa-chevron-down"></i></button>
                <button class="filtro">Tipo de Contratación <i class="fas fa-chevron-down"></i></button>
                <button class="filtro">Educación <i class="fas fa-chevron-down"></i></button>
                <button class="filtro limpiar">Limpiar filtros</button>
            </div>
        </div>

        <div class="contenedor-vacantes">
            <!-- Lista de vacantes -->
            <div class="lista-vacantes">
                <div class="vacante-item activa">
                    <p class="fecha">Hace 3 días • <span class="reciente">Vista recientemente.</span></p>
                    <h3>Practicante</h3>
                    <p>Sueldo no mostrado por la empresa</p>
                    <ul>
                        <li>Capacitación pagada</li>
                        <li>Apoyo económico</li>
                    </ul>
                    <p class="empresa">Crown Industrias Montacargas</p>
                    <p class="ubicacion">Querétaro, Qro.</p>
                </div>

                <div class="vacante-item">
                    <p class="fecha">Hace 1 día</p>
                    <h3>PRACTICANTE O RESIDENTE</h3>
                    <p>Sueldo no mostrado por la empresa</p>
                    <ul>
                        <li>Plan de crecimiento personal y laboral</li>
                        <li>Oportunidad de contratación</li>
                    </ul>
                    <p class="empresa">Otra Empresa S.A.</p>
                    <p class="ubicacion">Querétaro, Qro.</p>
                </div>
            </div>
            <!-- Detalle de la vacante seleccionada -->
            <div class="detalle-vacante">
                <!-- ENCABEZADO CON IMAGEN -->
                <div class="encabezado-vacante">
                    <div class="imagen-vacante-detalle">
                        <img id="imagenVacante" src="imagenes/default.jpg" alt="Logo empresa">
                    </div>
                    <div class="info-principal-vacante">
                        <p class="fecha">Hace 3 días</p>
                        <h2>Practicante</h2>
                        <p class="descripcion">
                            $8000 <br>
                            <strong>Crown Industrias Montacargas, S.A. de C.V.</strong> en Querétaro, Qro.
                        </p>
                        <button class="btn-postularme">Postularme</button>
                    </div>
                </div>

                <hr>

                <h4>Conoce tu compatibilidad con la vacante</h4>
                <div class="compatibilidad">
                    <div><i class="fas fa-check-circle"></i> Sueldo <span>Entras en el rango</span></div>
                    <div><i class="fas fa-check-circle"></i> Ubicación <span>Estás en el lugar correcto</span></div>
                    <div><i class="fas fa-check-circle"></i> Educación <span>Cumples con lo necesario</span></div>
                    <div><i class="fas fa-check-circle"></i> Área <span>Compatible con el puesto</span></div>
                </div>

                <!-- Sección: Sobre el empleo -->
                <hr class="linea-divisoria-vacante">
                <div class="seccion-empleo-vacante">
                    <h3 class="titulo-seccion-vacante">Sobre el empleo</h3>

                    <div class="info-empleo-fila">
                        <div class="info-item-vacante">
                            <strong>Área / Departamento:</strong>
                            <span id="previewArea"></span>
                        </div>

                        <div class="info-item-vacante">
                            <strong>Escolaridad mínima:</strong>
                            <span id="previewescolaridad"></span>
                        </div>
                    </div>

                    <div class="info-item-vacante">
                        <strong>Idioma:</strong> <span id="previewIdioma"></span>
                    </div>

                    <div class="info-item-vacante">
                        <strong>Area de Especialidad:</strong> <span id="previewEspecialidad"></span>
                    </div>

                    <hr class="linea-divisoria-vacante">
                </div>

                <!-- Detalles del empleo -->
                <div class="seccion-empleo-vacante">
                    <h3 class="titulo-seccion-vacante">Detalles del empleo</h3>

                    <div class="info-item-vacante">
                        <strong>Horario:</strong> <span id="previewHorario"></span>
                    </div>
                    <div class="info-item-vacante">
                        <strong>Espacio de trabajo:</strong> <span id="previewEspacio"></span>
                    </div>

                    <hr class="linea-divisoria-vacante">
                </div>

                <div class="preview-seccion-vacante">
                    <h3>Requisitos</h3>
                    <p id="previewRequisitos"></p>
                </div>
                <hr class="linea-divisoria-vacante">

                <div class="preview-seccion-vacante">
                    <h3>Beneficios</h3>
                    <p id="previewBeneficios"></p>
                </div>
                <hr class="linea-divisoria-vacante">

                <div class="preview-seccion-vacante">
                    <h3>Descripción del puesto</h3>
                    <p id="previewDescripcion"></p>
                </div>
            </div>
        </div>



    </div>
</section>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const vacantes = document.querySelectorAll('.vacante-item');

        vacantes.forEach((vacante, index) => {
            vacante.addEventListener('click', () => {
                // Guardar como vista
                const titulo = vacante.querySelector('h3').textContent;
                let vistas = JSON.parse(localStorage.getItem('vacantesVistas')) || [];

                if (!vistas.includes(titulo)) {
                    vistas.push(titulo);
                    localStorage.setItem('vacantesVistas', JSON.stringify(vistas));
                }

                // Mostrar detalles (si tienes esa función)
                mostrarDetalleVacante(index);
            });
        });

        // Al cargar, marcar las vistas
        const vistas = JSON.parse(localStorage.getItem('vacantesVistas')) || [];
        vacantes.forEach((vacante) => {
            const titulo = vacante.querySelector('h3').textContent;
            if (vistas.includes(titulo)) {
                const fecha = vacante.querySelector('.fecha');
                const span = document.createElement('span');
                span.classList.add('reciente');
                span.innerHTML = `<i class="fas fa-check-circle"></i> Vista recientemente.`;
                fecha.appendChild(document.createTextNode(' • '));
                fecha.appendChild(span);
            }
        });
    });

</script>
<script src="js/vacanteDinamica.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php if (isset($_SESSION['IdCandidato'])): ?>
    <script>
        const usuario = {
            sueldoEsperado: <?= intval($_SESSION['SueldoEsperado']) ?>,
            ubicacion: "<?= addslashes($_SESSION['UbicacionCandidato']) ?>",
            escolaridad: "<?= addslashes($_SESSION['Escolaridad']) ?>",
            area: "<?= addslashes($_SESSION['AreaInteres']) ?>"
        };
    </script>
<?php else: ?>
    <script>
        const usuario = null;
    </script>
<?php endif; ?>

</body>
</html>
