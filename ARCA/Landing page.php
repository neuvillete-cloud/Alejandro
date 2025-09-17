<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso al Sistema ARCA</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Montserrat:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        /* --- NUEVA PALETA DE COLORES: AZUL TECNOLÓGICO Y VIBRANTE --- */
        :root {
            --color-primario: #2563eb;   /* Azul Real (más brillante) */
            --color-secundario: #1d4ed8; /* Azul más oscuro para botones */
            --color-acento: #3b82f6;     /* Azul claro para interacciones */
            --color-fondo: #f4f6f9;
            --color-blanco: #ffffff;
            --color-texto: #333333;
            --color-borde: #dbe1e8;
        }

        body {
            font-family: 'Lato', sans-serif;
            margin: 0;
            background-color: var(--color-fondo);
            color: var(--color-texto);
        }

        .login-wrapper {
            display: flex;
            height: 100vh;
            width: 100%;
        }

        .branding-panel {
            flex-basis: 50%;
            /* El gradiente ahora usa los nuevos colores primario y secundario */
            background: linear-gradient(rgba(37, 99, 235, 0.85), rgba(29, 78, 216, 0.85)), url('https://images.unsplash.com/photo-1621999699042-834c6de1b489?q=80&w=1974&auto=format&fit=crop') no-repeat center center;
            background-size: cover;
            color: var(--color-blanco);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 60px;
            box-sizing: border-box;
        }

        .logo {
            font-family: 'Montserrat', sans-serif;
            font-size: 48px;
            font-weight: 800;
            margin-bottom: 20px;
        }

        .logo i { margin-right: 15px; }

        .branding-panel h1 {
            font-family: 'Montserrat', sans-serif;
            font-size: 32px;
            font-weight: 700;
            line-height: 1.4;
            margin-top: 0;
            margin-bottom: 10px;
        }

        .branding-panel p {
            font-size: 18px;
            max-width: 450px;
            line-height: 1.7;
            opacity: 0.9;
        }

        .login-panel {
            flex-basis: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: var(--color-blanco);
            position: relative;
        }

        .login-form-container {
            width: 100%;
            max-width: 400px;
            padding: 40px;
        }

        .login-form-container h2 {
            font-family: 'Montserrat', sans-serif;
            font-size: 28px;
            color: var(--color-primario);
            margin-top: 0;
            margin-bottom: 10px;
        }

        .login-form-container .subtitle {
            font-size: 16px;
            color: #666;
            margin-bottom: 40px;
        }

        .input-group {
            position: relative;
            margin-bottom: 25px;
        }

        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }

        .input-field {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 1px solid var(--color-borde);
            border-radius: 8px;
            font-size: 16px;
            box-sizing: border-box;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .input-field:focus {
            outline: none;
            border-color: var(--color-acento);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }

        .extra-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
            margin-bottom: 25px;
        }

        .extra-options label {
            cursor: pointer;
            color: #555;
        }

        .extra-options a {
            color: var(--color-acento);
            text-decoration: none;
            font-weight: 600;
        }

        .extra-options a:hover { text-decoration: underline; }

        .submit-btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 8px;
            background-color: var(--color-secundario);
            color: var(--color-blanco);
            font-family: 'Montserrat', sans-serif;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .submit-btn:hover {
            background-color: var(--color-primario);
        }

        .form-footer {
            margin-top: 25px;
            text-align: center;
            font-size: 14px;
        }

        .form-footer a {
            color: var(--color-acento);
            text-decoration: none;
            font-weight: 600;
        }

        .form-footer a:hover { text-decoration: underline; }

        .version-info {
            position: absolute;
            bottom: 20px;
            right: 20px;
            font-size: 12px;
            color: #aaa;
            font-weight: 600;
        }

        @media (max-width: 992px) {
            .branding-panel { display: none; }
            .login-panel { flex-basis: 100%; }
            .version-info {
                left: 50%;
                transform: translateX(-50%);
                right: auto;
            }
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="branding-panel">
        <div class="logo"><i class="fa-solid fa-shield-halved"></i>ARCA</div>
        <h1>Sistema de Gestión de Contenciones y Calidad</h1>
        <p>Una herramienta interna para asegurar la integridad de los procesos y materiales de la compañía.</p>
    </div>
    <div class="login-panel">
        <div class="login-form-container">
            <h2>Bienvenido de Vuelta</h2>
            <p class="subtitle">Por favor, introduce tus credenciales para acceder.</p>
            <form action="index.html" method="GET">
                <div class="input-group">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" id="username" name="username" class="input-field" placeholder="Nombre de Usuario" required>
                </div>
                <div class="input-group">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="password" name="password" class="input-field" placeholder="Contraseña" required>
                </div>
                <div class="extra-options">
                    <div>
                        <input type="checkbox" id="remember" name="remember" style="margin-right: 5px;">
                        <label for="remember">Recordar sesión</label>
                    </div>
                    <a href="#">¿Olvidaste tu contraseña?</a>
                </div>
                <button type="submit" class="submit-btn">Acceder</button>
            </form>
            <div class="form-footer">
                <p>¿No tienes una cuenta? <a href="register.html">Regístrate aquí.</a></p>
            </div>
        </div>
        <div class="version-info">
            ARCA v1.0.1
        </div>
    </div>
</div>

</body>
</html>