<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ARCA - Bienvenido</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Montserrat:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        :root {
            --color-primario: #1a237e;
            --color-secundario: #3f51b5;
            --color-acento: #448aff;
            --color-fondo: #f4f6f9;
            --color-blanco: #ffffff;
            --color-texto: #333333;
        }

        body {
            font-family: 'Lato', sans-serif;
            margin: 0;
            background-color: var(--color-fondo);
            color: var(--color-texto);
            overflow: hidden; /* Evita el scroll */
        }

        .landing-container {
            display: flex;
            height: 100vh;
        }

        .left-panel {
            flex-basis: 55%;
            background: linear-gradient(rgba(26, 35, 126, 0.85), rgba(63, 81, 181, 0.85)), url('https://images.unsplash.com/photo-1581092921462-20526a0a80b7?q=80&w=2070&auto=format&fit=crop') no-repeat center center;
            background-size: cover;
            color: var(--color-blanco);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 80px;
            box-sizing: border-box;
        }

        .logo {
            font-family: 'Montserrat', sans-serif;
            font-size: 48px;
            font-weight: 800;
            margin-bottom: 20px;
        }

        .logo i {
            margin-right: 15px;
        }

        .left-panel h1 {
            font-family: 'Montserrat', sans-serif;
            font-size: 42px;
            font-weight: 700;
            line-height: 1.3;
            margin-bottom: 20px;
        }

        .left-panel p {
            font-size: 18px;
            max-width: 500px;
            line-height: 1.7;
            opacity: 0.9;
        }

        .right-panel {
            flex-basis: 45%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
            background-color: var(--color-blanco);
        }

        .right-panel h2 {
            font-family: 'Montserrat', sans-serif;
            font-size: 32px;
            color: var(--color-primario);
            margin-bottom: 40px;
        }

        .action-button {
            display: block;
            width: 80%;
            max-width: 350px;
            padding: 18px;
            margin-bottom: 20px;
            border-radius: 8px;
            text-align: center;
            text-decoration: none;
            font-family: 'Montserrat', sans-serif;
            font-size: 18px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .login-btn {
            background-color: var(--color-acento);
            color: var(--color-blanco);
        }

        .login-btn:hover {
            background-color: #2979ff;
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(68, 138, 255, 0.4);
        }

        .register-btn {
            background-color: transparent;
            color: var(--color-secundario);
            border: 2px solid var(--color-secundario);
        }

        .register-btn:hover {
            background-color: var(--color-secundario);
            color: var(--color-blanco);
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(63, 81, 181, 0.3);
        }

        footer {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            color: #999;
            font-size: 14px;
        }

    </style>
</head>
<body>

<div class="landing-container">
    <div class="left-panel">
        <div class="logo">
            <i class="fa-solid fa-shield-halved"></i>ARCA
        </div>
        <h1>Control Total sobre tus Procesos de Calidad.</h1>
        <p>La solución definitiva para la administración, seguimiento y liberación de material en cuarentena. Optimiza tus tiempos, reduce errores y asegura la integridad de tu inventario.</p>
    </div>
    <div class="right-panel">
        <h2>Bienvenido</h2>
        <a href="login.html" class="action-button login-btn">Iniciar Sesión</a>
        <a href="register.html" class="action-button register-btn">Crear una Cuenta</a>
    </div>
</div>

<footer>© 2025 ARCA Systems. Todos los derechos reservados.</footer>

</body>
</html>
