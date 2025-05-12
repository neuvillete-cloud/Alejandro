<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Siemens Energy Clone Mejorado</title>
    <link rel="stylesheet" href="css/loginAts.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

</head>
<body>
<header>
    <div class="header-container">
        <div class="logo">
            <h1>Grammer</h1>
            <span>Automotive</span>
        </div>
        <nav>
            <a href="#">Buscar empleos</a>
            <a href="aboutUs.php">Acerca de nosotros</a>
            <a href="practicantes.php">Programa de posgrado</a>
            <a href="#">Inclusión y diversidad</a>
            <a href="#">Inicio de sesión</a>
            <a href="#">🌐 Español ▾</a>
        </nav>
    </div>
</header>

<!-- AQUÍ INICIA EL LOGIN -->

<!-- Header sigue fijo arriba -->

<section class="section-title">
    <h1>Inicio de sesión</h1>
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

<footer class="footer-grammer">
    <div class="footer-container">
        <div class="footer-left">
            <button class="contact-btn">
                ✉ Contáctenos
            </button>

            <ul class="footer-links">
                <li><strong>Solo en MX:</strong> Revisar las adaptaciones para discapacidades</li>
                <li><a href="#">Solicitud para condiciones laborales</a></li>
                <li><a href="#">grammer.com</a> Página web global</li>
            </ul>
        </div>

        <div class="footer-right">
            <div class="footer-nav">
                <a href="#">Información de la empresa</a>
                <a href="#">Política de privacidad</a>
                <a href="#">Aviso sobre cookies</a>
                <a href="#">Condiciones de uso</a>
                <a href="#">ID digital</a>
            </div>
            <div class="footer-social">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-x-twitter"></i></a>
                <a href="#"><i class="fab fa-linkedin-in"></i></a>
                <a href="#"><i class="fas fa-globe"></i></a>
            </div>
        </div>
    </div>

    <hr>

    <div class="footer-bottom">
        <p>Grammer es una marca registrada de GRAMMER AG.</p>
        <p>© Grammer, 2020 - 2025</p>
    </div>
</footer>

<script src="js/loginATS.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>
