<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grammer Automotive login</title>
    <link rel="stylesheet" href="css/loginAts.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

</head>
<body>
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
            <a href="indexAts.php">Buscar empleos</a>
            <a href="aboutUs.php">Acerca de nosotros</a>
            <a href="practicantes.php">Escuela de Talentos</a>
            <a href="#">Inclusión y diversidad</a>
            <a href="loginATS.php">Inicio de sesión</a>
        </nav>
    </div>
</header>

<!-- AQUÍ INICIA EL LOGIN -->

<!-- Header sigue fijo arriba -->

<section class="section-title">
    <h1>Inicio de Sesion</h1>
    <img src="imagenes/iniciar-sesion.png" alt="Imagen decorativa" class="imagen-banner">
</section>

<section class="section-login">
    <div class="login-left">
        <h2>¿Se ha registrado ya?</h2>
        <form>
            <label for="email">Correo electrónico</label>
            <input type="email" id="email" name="email" required>

            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Inicio de sesión</button>
        </form>
    </div>

    <div class="login-right">
        <h2>Candidato por primera vez</h2>
        <p>Si aún no se ha registrado, cree su cuenta aquí.</p>
        <a href="Registro.php" class="register-btn">Crear perfil</a>
    </div>
</section>

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
                <li><a href="indexAts.php">Inicio</a></li>
                <li><a href="aboutUs.php">Acerca de Nosotros</a></li>
                <li><a href="practicantes.php">Escuela de Talentos</a></li>
                <li><a href="inclusionDiversidad.php">Inclusion y Diversidad</a></li>
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

<script src="js/loginATS.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>
