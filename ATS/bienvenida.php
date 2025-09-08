<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Bienvenido/a a la familia Grammer Automotive!</title>
    <style>
        /* --- Importación de Fuente --- */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap');

        /* --- Estilos Generales --- */
        :root {
            --color-primario: #005195; /* Azul Grammer */
            --color-acento: #ffc107;  /* Amarillo Grammer */
            --color-fondo: #f0f2f5;
            --color-texto: #333333;
            --color-blanco: #ffffff;
        }

        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--color-fondo);
            color: var(--color-texto);
            line-height: 1.7;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* --- Contenedor Principal --- */
        .welcome-container {
            opacity: 0;
            transform: translateY(20px);
            animation: fadeIn 1s ease-out forwards;
            max-width: 900px;
            margin: 40px auto;
            background-color: var(--color-blanco);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            overflow: hidden; /* Para que el borde superior se vea bien */
        }

        /* --- Encabezado --- */
        .header {
            background-color: var(--color-primario);
            padding: 40px 20px;
            text-align: center;
            border-top: 8px solid var(--color-acento);
        }

        .header img {
            max-width: 220px;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.2));
        }

        /* --- Contenido Principal --- */
        .content {
            padding: 40px 50px;
        }

        h1, h2 {
            color: var(--color-primario);
            text-align: center;
            font-weight: 700;
        }

        h1 {
            font-size: 2.8em;
            margin-bottom: 15px;
        }

        h2 {
            font-size: 1.8em;
            margin-top: 40px;
            margin-bottom: 20px;
            border-bottom: 2px solid var(--color-acento);
            display: inline-block;
            padding-bottom: 5px;
        }

        .content > div:not(.video-container) {
            text-align: center; /* Centrar el h2 */
        }

        p {
            font-size: 1.1em;
            text-align: justify;
            margin-bottom: 20px;
            color: #555;
        }

        /* --- Contenedor del Video Responsivo --- */
        .video-container {
            position: relative;
            padding-bottom: 56.25%; /* Proporción 16:9 */
            height: 0;
            overflow: hidden;
            max-width: 100%;
            background: #000;
            margin: 40px 0;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }

        .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: 0;
        }

        /* --- Pie de Página --- */
        .footer {
            text-align: center;
            padding: 25px;
            margin-top: 20px;
            font-size: 0.95em;
            color: #777;
        }

        /* --- Animación de Entrada --- */
        @keyframes fadeIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* --- Estilos para Móviles --- */
        @media (max-width: 768px) {
            h1 { font-size: 2em; }
            h2 { font-size: 1.5em; }
            .content { padding: 30px 25px; }
            .welcome-container { margin: 20px 15px; }
        }
    </style>
</head>
<body>

<div class="welcome-container">
    <div class="header">
        <img src="imagenes/logo_blanco.png" alt="Logo Grammer Automotive">
    </div>

    <div class="content">
        <h1>¡Felicidades y bienvenido/a a bordo!</h1>
        <p>En Grammer Automotive, estamos construyendo el futuro de la movilidad, y estamos increíblemente emocionados de que ahora formes parte de este viaje. Tu talento nos ha impresionado y estamos seguros de que tu contribución será fundamental para nuestro éxito.</p>

        <div>
            <h2>La Experiencia Grammer</h2>
        </div>
        <p>Te unes a una compañía global líder, comprometida con la innovación, la calidad y las personas. Aquí, no solo desarrollarás tu carrera, sino que también tendrás la oportunidad de impactar en una industria en constante evolución. Prepárate para un entorno dinámico, lleno de retos y oportunidades de crecimiento.</p>

        <!-- IMPORTANTE: Reemplaza la URL de abajo por la de tu video de bienvenida oficial de YouTube. -->
        <div class="video-container">
            <iframe
                src="https://www.youtube.com/embed/2M99h7n38h0?autoplay=1&mute=1&loop=1&playlist=2M99h7n38h0&controls=0&showinfo=0&rel=0"
                title="Video de Bienvenida de Grammer"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen>
            </iframe>
        </div>

        <p>Este es solo el comienzo de una emocionante etapa. Estamos ansiosos por ver todo lo que lograremos juntos. ¡Bienvenido/a al equipo!</p>
    </div>
</div>

<div class="footer">
    &copy; <?= date('Y') ?> Grammer Automotive de México. Todos los derechos reservados.
</div>

</body>
</html>


