<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Siemens Energy Clone Mejorado</title>
    <link rel="stylesheet" href="css/Registro.css">
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

<section class="section-title">
    <h1>Inscripción</h1>
</section>

<div class="section-blanca">
    <form class="formulario-registro" method="post" action="procesar_registro.php">
        <p>Si ya está registrado, <a href="login.php" class="link-inicio">inicie sesión en su cuenta</a> para presentar su solicitud.</p>

        <label>Correo electrónico <span class="required">*</span></label>
        <input type="email" name="email" required>

        <label>Título</label>
        <select name="titulo">
            <option value="">Seleccionar una abreviatura de tratamiento</option>
            <option value="Sr.">Sr.</option>
            <option value="Sra.">Sra.</option>
            <option value="Srta.">Srta.</option>
        </select>

        <label>Nombre <span class="required">*</span></label>
        <input type="text" name="nombre" required>

        <label>Apellidos <span class="required">*</span></label>
        <input type="text" name="apellidos" required>

        <label>Teléfono preferido <span class="required">*</span></label>
        <input type="tel" name="telefono" required>

        <label>Contraseña <span class="required">*</span></label>
        <input type="password" name="contrasena" required>

        <label>Confirmación de contraseña <span class="required">*</span></label>
        <input type="password" name="confirmar_contrasena" required>

        <!-- Nueva sección de aceptación de términos -->
        <section class="terminos-condiciones">
            <div class="checkbox-condiciones">
                <label>
                    <input type="checkbox" name="acepta_terminos" required>
                    He leído y acepto los <a href="terminos.pdf" target="_blank">Términos y Condiciones</a> y el <a href="aviso_privacidad.pdf" target="_blank">Aviso de Privacidad</a>.
                </label>
            </div>

            <div class="botones-final">
                <button type="reset" class="btn-cancelar">Cancelar</button>
                <button type="submit" class="btn-confirmar">Confirmar</button>
            </div>
        </section>
    </form>
</div>

</body>
</html>
