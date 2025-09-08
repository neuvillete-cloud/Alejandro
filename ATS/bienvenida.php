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

        /* --- INICIO: Estilos para la nueva sección de Documentos --- */
        .tabs-container {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #dee2e6;
        }
        .tab-link {
            padding: 15px 30px;
            cursor: pointer;
            border: none;
            background: none;
            font-size: 1.2em;
            font-weight: 600;
            color: #6c757d;
            position: relative;
            transition: color 0.3s ease;
        }
        .tab-link::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 4px;
            background-color: var(--color-acento);
            border-radius: 2px;
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        .tab-link.active {
            color: var(--color-primario);
        }
        .tab-link.active::after {
            transform: scaleX(1);
        }
        .tab-content {
            display: none;
            animation: fadeInContent 0.5s ease;
        }
        .tab-content.active {
            display: block;
        }
        .document-list {
            list-style: none;
            padding: 0;
            column-count: 2;
            column-gap: 40px;
        }
        .document-list li {
            display: flex;
            align-items: flex-start;
            margin-bottom: 20px;
            font-size: 1em;
        }
        .document-list .icon {
            font-size: 1.4em;
            color: var(--color-secundario);
            margin-right: 15px;
            margin-top: 5px;
        }
        .alert-note {
            margin-top: 40px;
            padding: 20px;
            background-color: #fff3cd;
            border-left: 5px solid var(--color-acento);
            border-radius: 8px;
            text-align: left;
        }
        .alert-note strong {
            display: block;
            margin-bottom: 5px;
            color: #664d03;
        }
        /* --- FIN: Estilos para la nueva sección de Documentos --- */

        /* --- Pie de Página --- */
        .footer {
            text-align: center;
            padding: 25px;
            margin-top: 20px;
            font-size: 0.95em;
            color: #777;
        }

        /* --- Animaciones --- */
        @keyframes fadeIn { to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeInContent { from { opacity: 0; } to { opacity: 1; } }
        .key-point:nth-child(2) { animation-delay: 0.7s; }
        .key-point:nth-child(3) { animation-delay: 0.9s; }

        /* --- Estilos para Móviles --- */
        @media (max-width: 768px) {
            h1 { font-size: 2em; }
            h2 { font-size: 1.6em; }
            .content { padding: 30px 25px; }
            .welcome-container { margin: 20px 15px; }
            .key-points-container, .document-list { flex-direction: column; column-count: 1; }
            .tab-link { padding: 15px; font-size: 1em; }
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

            <!-- INICIO: Nueva Sección de Documentación -->
            <h2>Documentación Requerida</h2>
            <p>Para agilizar tu proceso de contratación, por favor reúne la siguiente documentación. Selecciona tu tipo de puesto:</p>

            <div class="tabs-container">
                <button class="tab-link active" data-tab="practicante">Practicante</button>
                <button class="tab-link" data-tab="empleado">Empleado</button>
            </div>

            <div id="practicante" class="tab-content active">
                <ol class="document-list">
                    <li><span class="icon"><i class="fas fa-id-card"></i></span><div><strong>Credencial de Elector (INE):</strong> 1 Copia por ambos lados, legible.</div></li>
                    <li><span class="icon"><i class="fas fa-user-shield"></i></span><div><strong>Número de Seguro Social (NSS):</strong> 1 Copia del documento oficial.</div></li>
                    <li><span class="icon"><i class="fas fa-fingerprint"></i></span><div><strong>CURP:</strong> 1 Copia del formato actualizado (SEGOB).</div></li>
                    <li><span class="icon"><i class="fas fa-file-invoice-dollar"></i></span><div><strong>Constancia de Situación Fiscal:</strong> 1 Copia de la hoja membretada del SAT.</div></li>
                    <li><span class="icon"><i class="fas fa-map-marker-alt"></i></span><div><strong>Comprobante de Domicilio:</strong> 1 Copia reciente (no mayor a 2 meses) de recibo de agua o luz.</div></li>
                    <li><span class="icon"><i class="fas fa-at"></i></span><div><strong>Correo Electrónico:</strong> Tu dirección de correo es obligatoria para la comunicación.</div></li>
                    <li><span class="icon"><i class="fas fa-graduation-cap"></i></span><div><strong>Carta de la Universidad:</strong> 1 Copia de la carta para realizar prácticas profesionales.</div></li>
                    <li><span class="icon"><i class="fas fa-baby"></i></span><div><strong>Acta de Nacimiento:</strong> 1 Copia.</div></li>
                    <li><span class="icon"><i class="fas fa-landmark"></i></span><div><strong>Cuenta Bancaria HSBC:</strong> 1 Copia del contrato si ya tienes una. Si no, la empresa la gestionará por ti.</div></li>
                    <li><span class="icon"><i class="fas fa-syringe"></i></span><div><strong>Comprobante de Vacunación COVID-19:</strong> 1 Copia.</div></li>
                    <li><span class="icon"><i class="fas fa-file-alt"></i></span><div><strong>CV Actualizado:</strong> Tu currículum vitae más reciente.</div></li>
                </ol>
            </div>

            <div id="empleado" class="tab-content">
                <ol class="document-list">
                    <li><span class="icon"><i class="fas fa-id-card"></i></span><div><strong>Credencial de Elector (INE):</strong> 1 Copia por ambos lados, legible.</div></li>
                    <li><span class="icon"><i class="fas fa-user-shield"></i></span><div><strong>Número de Seguro Social (NSS):</strong> 1 Copia del documento oficial.</div></li>
                    <li><span class="icon"><i class="fas fa-fingerprint"></i></span><div><strong>CURP:</strong> 1 Copia del formato actualizado (SEGOB).</div></li>
                    <li><span class="icon"><i class="fas fa-file-invoice-dollar"></i></span><div><strong>Constancia de Situación Fiscal:</strong> 1 Copia de la hoja membretada del SAT.</div></li>
                    <li><span class="icon"><i class="fas fa-map-marker-alt"></i></span><div><strong>Comprobante de Domicilio:</strong> 1 Copia reciente (no mayor a 2 meses) de recibo de agua o luz.</div></li>
                    <li><span class="icon"><i class="fas fa-at"></i></span><div><strong>Correo Electrónico:</strong> Tu dirección de correo es obligatoria para la comunicación.</div></li>
                    <li><span class="icon"><i class="fas fa-baby"></i></span><div><strong>Acta de Nacimiento:</strong> 1 Copia.</div></li>
                    <li><span class="icon"><i class="fas fa-landmark"></i></span><div><strong>Cuenta Bancaria HSBC:</strong> 1 Copia del contrato si ya tienes una. Si no, la empresa la gestionará por ti.</div></li>
                    <li><span class="icon"><i class="fas fa-syringe"></i></span><div><strong>Comprobante de Vacunación COVID-19:</strong> 1 Copia.</div></li>
                    <li><span class="icon"><i class="fas fa-file-alt"></i></span><div><strong>CV Actualizado:</strong> Tu currículum vitae más reciente.</div></li>
                </ol>
            </div>

            <div class="alert-note">
                <strong>¡MUY IMPORTANTE!</strong>
                <p style="text-align: left; margin-bottom: 0;">Si falta alguno de los documentos mencionados, tu proceso de contratación no podrá continuar. Por favor, envía la documentación en el orden numérico indicado (Ej: 1. INE, 2. NSS, 3. CURP...).</p>
            </div>
            <!-- FIN: Nueva Sección de Documentación -->
        </div>
    </div>
</div>

<div class="footer">
    &copy; <?= date('Y') ?> Grammer Automotive de México. Todos los derechos reservados.
</div>

<script>
    // --- Lógica para las pestañas de documentación ---
    const tabsContainer = document.querySelector('.tabs-container');
    const tabLinks = document.querySelectorAll('.tab-link');
    const tabContents = document.querySelectorAll('.tab-content');

    tabsContainer.addEventListener('click', (e) => {
        const clicked = e.target.closest('.tab-link');
        if (!clicked) return;

        // Quitar clase 'active' de todos
        tabLinks.forEach(link => link.classList.remove('active'));
        tabContents.forEach(content => content.classList.remove('active'));

        // Añadir clase 'active' al clickeado y a su contenido
        const tabId = clicked.dataset.tab;
        const contentToShow = document.getElementById(tabId);

        clicked.classList.add('active');
        contentToShow.classList.add('active');
    });
</script>

</body>
</html>

