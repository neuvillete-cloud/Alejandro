<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ARCA - Control y Gestión de Calidad</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Montserrat:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        :root {
            --color-primario: #1a237e; /* Azul oscuro */
            --color-secundario: #3f51b5; /* Azul medio */
            --color-acento: #448aff; /* Azul brillante */
            --color-fondo: #ffffff;
            --color-fondo-seccion: #f4f6f9;
            --color-texto: #333333;
            --color-blanco: #ffffff;
        }

        body {
            font-family: 'Lato', sans-serif;
            margin: 0;
            background-color: var(--color-fondo);
            color: var(--color-texto);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* --- Header --- */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
        }

        .logo {
            font-family: 'Montserrat', sans-serif;
            font-size: 28px;
            font-weight: 700;
            color: var(--color-primario);
            text-decoration: none;
        }

        .logo i { margin-right: 10px; }

        .nav-buttons .btn {
            font-family: 'Montserrat', sans-serif;
            text-decoration: none;
            padding: 10px 22px;
            border-radius: 6px;
            margin-left: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-login {
            background-color: transparent;
            color: var(--color-primario);
        }
        .btn-login:hover { background-color: #e3f2fd; }

        .btn-register {
            background-color: var(--color-acento);
            color: var(--color-blanco);
        }
        .btn-register:hover { background-color: #2979ff; }


        /* --- Hero Section --- */
        .hero {
            text-align: center;
            padding: 80px 0;
            background: linear-gradient(135deg, #1a237e, #3f51b5, #448aff);
            background-size: 400% 400%;
            animation: gradientAnimation 15s ease infinite;
            color: var(--color-blanco);
        }

        @keyframes gradientAnimation {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .hero h1 {
            font-family: 'Montserrat', sans-serif;
            font-size: 52px;
            font-weight: 800;
            margin-bottom: 20px;
        }

        .hero p {
            font-size: 20px;
            max-width: 700px;
            margin: 0 auto 30px;
            opacity: 0.9;
        }

        .app-mockup {
            margin-top: 50px;
            position: relative;
        }

        .app-mockup img {
            max-width: 80%;
            border-radius: 12px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
            transform: perspective(1500px) rotateX(15deg);
            transition: transform 0.5s ease;
        }

        .app-mockup:hover img {
            transform: perspective(2000px) rotateX(0deg);
        }

        /* --- Sections General Style --- */
        .section {
            padding: 80px 0;
        }

        .section-title {
            text-align: center;
            font-family: 'Montserrat', sans-serif;
            font-size: 36px;
            margin-bottom: 15px;
            color: var(--color-primario);
        }

        .section-subtitle {
            text-align: center;
            font-size: 18px;
            color: #666;
            margin-bottom: 60px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        /* --- Features Section --- */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
        }

        .feature-card {
            background: var(--color-blanco);
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            border: 1px solid #e0e0e0;
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
            border-color: var(--color-acento);
        }

        .feature-card .icon {
            font-size: 42px;
            color: var(--color-acento);
            margin-bottom: 20px;
        }

        .feature-card h3 {
            font-family: 'Montserrat', sans-serif;
            font-size: 20px;
            margin-bottom: 10px;
        }

        /* --- How It Works Section --- */
        #how-it-works { background-color: var(--color-fondo-seccion); }

        .steps-container {
            display: flex;
            justify-content: space-between;
            text-align: center;
            gap: 40px;
            position: relative;
        }

        .step { flex: 1; }

        .step-icon {
            width: 80px;
            height: 80px;
            background-color: var(--color-blanco);
            border-radius: 50%;
            display: grid;
            place-items: center;
            margin: 0 auto 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.07);
            font-size: 32px;
            color: var(--color-secundario);
            border: 2px solid var(--color-secundario);
        }

        .step h3 {
            font-family: 'Montserrat', sans-serif;
            font-size: 22px;
        }

        /* --- CTA Section --- */
        #cta { text-align: center; }

        /* --- Footer --- */
        .footer {
            background-color: var(--color-primario);
            color: var(--color-blanco);
            text-align: center;
            padding: 40px 20px;
        }

    </style>
</head>
<body>

<header class="header container">
    <a href="#" class="logo">
        <i class="fa-solid fa-shield-halved"></i>ARCA
    </a>
    <nav class="nav-buttons">
        <a href="login.html" class="btn btn-login">Iniciar Sesión</a>
        <a href="register.html" class="btn btn-register">Empezar Ahora</a>
    </nav>
</header>

<main>
    <section class="hero">
        <div class="container">
            <h1>El Futuro de la Gestión de Calidad</h1>
            <p>Transforma tus procesos de cuarentena con una plataforma centralizada, visual e inteligente. ARCA te da el control total.</p>
            <div class="nav-buttons">
                <a href="register.html" class="btn btn-register" style="font-size: 18px; padding: 15px 35px;">Crear mi Cuenta Gratis</a>
            </div>
            <div class="app-mockup">
                <<img src="https://via.placeholder.com/1200x750/E3F2FD/1A237E?text=Dashboard+ARCA" alt="Maqueta del Dashboard de ARCA">
            </div>
        </div>
    </section>

    <section id="features" class="section container">
        <h2 class="section-title">Todo lo que necesitas, en un solo lugar</h2>
        <p class="section-subtitle">ARCA no es solo un registro. Es un ecosistema completo para una gestión de cuarentenas impecable.</p>
        <div class="features-grid">
            <div class="feature-card">
                <div class="icon"><i class="fa-solid fa-sitemap"></i></div>
                <h3>Control Centralizado</h3>
                <p>Crea, gestiona y da seguimiento a todas tus solicitudes de contención desde un único panel de control intuitivo.</p>
            </div>
            <div class="feature-card">
                <div class="icon"><i class="fa-solid fa-camera-retro"></i></div>
                <h3>Evidencia Visual</h3>
                <p>Adjunta fotos de defectos y documentos PDF directamente a cada solicitud. La información visual clara acelera las decisiones.</p>
            </div>
            <div class="feature-card">
                <div class="icon"><i class="fa-solid fa-chart-line"></i></div>
                <h3>Seguimiento en Tiempo Real</h3>
                <p>Conoce el estatus exacto de cada material retenido, desde el registro inicial hasta su liberación final.</p>
            </div>
        </div>
    </section>

    <section id="how-it-works" class="section">
        <div class="container">
            <h2 class="section-title">¿Cómo funciona ARCA?</h2>
            <p class="section-subtitle">Hemos simplificado el proceso para que puedas concentrarte en lo que más importa: la calidad.</p>
            <div class="steps-container">
                <div class="step">
                    <div class="step-icon">1</div>
                    <h3>Registra la Solicitud</h3>
                    <p>Crea una nueva contención en segundos con toda la información necesaria: número de parte, proveedor, cantidad, etc.</p>
                </div>
                <div class="step">
                    <div class="step-icon">2</div>
                    <h3>Documenta los Defectos</h3>
                    <p>Sube fotos y archivos para registrar de forma clara y precisa cada defecto encontrado en el material.</p>
                </div>
                <div class="step">
                    <div class="step-icon">3</div>
                    <h3>Gestiona y Libera</h3>
                    <p>Da seguimiento al estatus, asigna responsables y documenta la liberación del material una vez resuelta la incidencia.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="cta" class="section container">
        <h2 class="section-title">¿Listo para tomar el control?</h2>
        <p class="section-subtitle">Únete a la nueva era de la gestión de calidad. Crea tu cuenta y empieza a optimizar tus procesos hoy mismo.</p>
        <a href="register.html" class="btn btn-register" style="font-size: 20px; padding: 18px 40px;">Empezar Ahora, es Gratis</a>
    </section>
</main>

<footer class="footer">
    <p>© 2025 ARCA Systems. Creado para optimizar la calidad industrial.</p>
</footer>

</body>
</html>