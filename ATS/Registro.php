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
    <h1>Inscripcion</h1>
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

        <!-- Sección de privacidad -->
        <section class="privacidad-grammer">
            <h3>Nota sobre privacidad</h3>
            <p>Estimado candidato:</p>
            <p>Gracias por su interés en un trabajo con Grammer.</p>
            <p>
                Ha dado el primer paso hacia una nueva oportunidad profesional en nuestra empresa.
                Le invitamos a completar su solicitud. En caso de que acepte que sus datos sean
                accesibles para nuestros equipos de selección de personal, su perfil completo aumentará
                sus posibilidades de ser considerado para futuras oportunidades.
            </p>

            <label class="radio-group-title">Seleccione una opción: <span class="required">*</span></label>
            <div class="radio-group">
                <label><input type="radio" name="privacidad" value="global" required>
                    Que mis datos sean accesibles a todas las unidades relevantes de Grammer.</label><br>

                <label><input type="radio" name="privacidad" value="limitado" required>
                    Que mis datos solo sean accesibles a la unidad que ofrece el puesto solicitado.</label>
            </div>

            <p>
                Consulte nuestro <a href="#" class="aviso-link">Aviso de privacidad de datos</a> para obtener más información sobre cómo gestionamos sus datos personales.
            </p>
            <p class="nota-cambios">(Esta configuración se puede cambiar en cualquier momento desde su perfil de candidato.)</p>

            <div class="botones-final">
                <button type="reset" class="btn-cancelar">Cancelar</button>
                <button type="submit" class="btn-confirmar">Confirmar</button>
            </div>
        </section>
    </form>
</div>


</body>
</html>
