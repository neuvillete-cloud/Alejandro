<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vacantes en Grammer Automotive</title>
    <link rel="stylesheet" href="css/postularme.css">
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
            <a href="practicantes.php"> Escuela de Talentos</a>
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
                        <a href="#" id="logout">Cerrar sesión</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="loginATS.php">Inicio de sesión</a>
            <?php endif; ?>

        </nav>
    </div>
</header>

<section class="section-title">
    <h1>Postularme</h1>
</section>

<section class="area-blanca">
    <div class="contenido-blanco">
        <div class="formulario-postulacion">
            <!-- Columna izquierda: datos del candidato -->
            <div class="columna-formulario">
                <h2>Agrega tu información de contacto</h2>
                <form id="formPostulacion">
                    <label>Nombre *</label>
                    <input type="text" name="nombre" value="<?= $_SESSION['NombreCandidato'] ?? '' ?>" required>

                    <label>Apellido *</label>
                    <input type="text" name="apellido" value="<?= $_SESSION['ApellidoCandidato'] ?? '' ?>" required>

                    <label>Email</label>
                    <input type="email" name="email" value="<?= $_SESSION['CorreoCandidato'] ?? '' ?>">

                    <label>País</label>
                    <div class="dato-estatico">México</div>

                    <label>Ciudad, estado</label>
                    <input type="text" name="ciudad" value="<?= $_SESSION['UbicacionCandidato'] ?? '' ?>">

                    <label>Número de teléfono</label>
                    <div class="telefono-input">
                        <span class="lada">🇲🇽 +52</span>
                        <input type="tel" name="telefono" value="<?= $_SESSION['TelefonoCandidato'] ?? '' ?>" placeholder="442-864-4068">
                    </div>

                    <button type="submit" class="btn-continuar">Continuar</button>
                </form>
            </div>

            <!-- Columna derecha: resumen de la vacante -->
            <div class="columna-vacante">
                <div class="tarjeta-vacante">
                    <h3>Programador y operador CNC</h3>
                    <p><strong>FEM TOOLING</strong> - La Griega, Qro.</p>
                    <hr>
                    <p><strong>Rol y responsabilidades:</strong><br>
                        Utilizar maquinaria controlada numéricamente por computadora (CNC) de manera segura y precisa para realizar una variedad de funciones en el maquinado de piezas.</p>
                    <p><strong>Actividades:</strong></p>
                    <a href="#">Ver descripción completa del empleo</a>
                </div>
            </div>
        </div>





    </div>
</section>
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

<script>
    const logoutLink = document.getElementById('logout');

    if (logoutLink) {
        logoutLink.addEventListener('click', (e) => {
            e.preventDefault();
            fetch('dao/logout.php', { method: 'POST' })
                .then(response => {
                    if (response.ok) {
                        window.location.href = 'loginATS.php';
                    } else {
                        alert('Error al cerrar sesión. Inténtalo nuevamente.');
                    }
                })
                .catch(error => console.error('Error al cerrar sesión:', error));
        });
    }
</script>

</body>
</html>

