<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Bienvenido/a a la familia Grammer Automotive!</title>
    <!-- Se añade Font Awesome para los nuevos iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* --- Importación de Fuente --- */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap');

        /* --- Estilos Generales --- */
        :root {
            --color-primario: #004a8d; /* Azul Grammer más oscuro */
            --color-secundario: #005cb9;
            --color-acento: #ffc107;  /* Amarillo Grammer */
            --color-fondo: #f4f7fc;
            --color-texto: #343a40;
            --color-blanco: #ffffff;
        }

        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--color-fondo);
            color: var(--color-texto);
            line-height: 1.8;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* --- Contenedor Principal --- */
        .welcome-container {
            max-width: 950px;
            margin: 40px auto;
        }

        .card {
            opacity: 0;
            transform: translateY(30px);
            animation: fadeIn 1s ease-out forwards;
            background-color: var(--color-blanco);
            border-radius: 16px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            margin-bottom: 30px;
        }

        /* --- Encabezado --- */
        .header {
            background: linear-gradient(45deg, var(--color-primario), var(--color-secundario));
            padding: 50px 20px;
            text-align: center;
        }

        .header img {
            max-width: 240px;
            filter: drop-shadow(0 5px 8px rgba(0,0,0,0.25));
        }

        /* --- Contenido Principal --- */
        .content {
            padding: 50px 60px;
        }

        h1, h2 {
            color: var(--color-primario);
            text-align: center;
            font-weight: 700;
        }

        h1 {
            font-size: 3em;
            margin-bottom: 20px;
            line-height: 1.2;
        }

        h2 {
            font-size: 2em;
            margin-top: 50px;
            margin-bottom: 25px;
            position: relative;
            padding-bottom: 10px;
        }

        h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background-color: var(--color-acento);
            border-radius: 2px;
        }

        p {
            font-size: 1.1em;
            text-align: center;
            margin-bottom: 25px;
            color: #555;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        /* --- Sección de Puntos Clave --- */
        .key-points-container {
            display: flex;
            justify-content: space-around;
            gap: 25px;
            margin: 50px 0;
            text-align: center;
        }
        .key-point {
            flex: 1;
            padding: 20px;
            animation: fadeIn 1s ease-out forwards;
            animation-delay: 0.5s;
            opacity: 0;
        }
        .key-point .icon {
            font-size: 3.5em;
            color: var(--color-primario);
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }
        .key-point:hover .icon {
            transform: scale(1.1) translateY(-5px);
            color: var(--color-secundario);
        }
        .key-point h3 {
            font-size: 1.4em;
            margin-bottom: 10px;
            color: var(--color-texto);
        }
        .key-point p {
            font-size: 1em;
            line-height: 1.6;
        }

        /* --- Contenedor del Video Responsivo --- */
        .video-container {
            position: relative;
            padding-bottom: 56.25%; /* 16:9 */
            height: 0;
            overflow: hidden;
            max-width: 100%;
            margin: 40px 0;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        .video-container iframe {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0;
        }

        /* --- Sección de Próximos Pasos --- */
        .next-steps-list {
            list-style: none;
            padding: 0;
            max-width: 700px;
            margin: 30px auto 0;
        }
        .next-steps-list li {
            display: flex;
            align-items: center;
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            font-size: 1.1em;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .next-steps-list li:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.08);
        }
        .next-steps-list .icon {
            font-size: 1.8em;
            color: var(--color-acento);
            margin-right: 20px;
            min-width: 30px;
        }

        /* --- Pie de Página --- */
        .footer {
            text-align: center;
            padding: 25px;
            margin-top: 20px;
            font-size: 0.95em;
            color: #777;
        }

        /* --- Animaciones --- */
        @keyframes fadeIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .key-point:nth-child(2) { animation-delay: 0.7s; }
        .key-point:nth-child(3) { animation-delay: 0.9s; }

        /* --- Estilos para Móviles --- */
        @media (max-width: 768px) {
            h1 { font-size: 2em; }
            h2 { font-size: 1.6em; }
            .content { padding: 30px 25px; }
            .welcome-container { margin: 20px 15px; }
            .key-points-container { flex-direction: column; }
        }
    </style>
</head>
<body>

<div class="welcome-container">
    <div class="card">
        <div class="header">
            <img src="imagenes/logo_blanco.png" alt="Logo Grammer Automotive">
        </div>
        <div class="content">
            <h1>¡Felicidades y bienvenido/a a bordo!</h1>
            <p>En Grammer Automotive, estamos construyendo el futuro de la movilidad. Tu talento nos ha impresionado y estamos seguros de que tu contribución será fundamental para nuestro éxito. ¡Nos emociona que te unas a este viaje!</p>

            <div class="key-points-container">
                <div class="key-point">
                    <div class="icon"><i class="fas fa-rocket"></i></div>
                    <h3>Innovación Constante</h3>
                    <p>Formarás parte de proyectos que definen el futuro de la industria automotriz.</p>
                </div>
                <div class="key-point">
                    <div class="icon"><i class="fas fa-globe-americas"></i></div>
                    <h3>Equipo Global</h3>
                    <p>Colaborarás con profesionales talentosos de todo el mundo en un entorno diverso.</p>
                </div>
                <div class="key-point">
                    <div class="icon"><i class="fas fa-chart-line"></i></div>
                    <h3>Crecimiento Profesional</h3>
                    <p>Te esperan retos y oportunidades para que desarrolles tu carrera al máximo nivel.</p>
                </div>
            </div>

            <h2>Tu Futuro Comienza Aquí</h2>
            <div class="video-container">
                <iframe
                    src="https://www.youtube.com/embed/2M99h7n38h0?autoplay=1&mute=1&loop=1&playlist=2M99h7n38h0&controls=0&showinfo=0&rel=0"
                    title="Video de Bienvenida de Grammer"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen>
                </iframe>
            </div>

            <h2>¿Qué sigue ahora?</h2>
            <p>Este es el inicio de tu camino en Grammer. Aquí te adelantamos los próximos pasos en tu proceso de incorporación:</p>
            <ul class="next-steps-list">
                <li><span class="icon"><i class="fas fa-file-signature"></i></span><div><strong>Contacto de RRHH:</strong> Pronto recibirás noticias de nuestro equipo para formalizar tu oferta.</div></li>
                <li><span class="icon"><i class="fas fa-calendar-check"></i></span><div><strong>Tu Primer Día:</strong> Te informaremos los detalles para tu día de inducción y bienvenida al equipo.</div></li>
                <li><span class="icon"><i class="fas fa-question-circle"></i></span><div><strong>¿Dudas?</strong> Si tienes alguna pregunta, no dudes en contactar al reclutador que te acompañó en el proceso.</div></li>
            </ul>
        </div>
    </div>
</div>

<div class="footer">
    &copy; <?= date('Y') ?> Grammer Automotive de México. Todos los derechos reservados.
</div>

</body>
</html>

