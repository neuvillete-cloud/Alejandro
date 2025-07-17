<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Inclusion y Diversidad</title>
    <link rel="stylesheet" href="css/practicantes.css" />
</head>
<body>
<div class="container">
    <aside class="sidebar">
        <!-- Contenedor de íconos que no se mueve -->
        <div class="sidebar-icons">
            <div class="top-icon">
                <!-- Icono arriba -->
                <svg xmlns="http://www.w3.org/2000/svg" class="icon-img" viewBox="0 0 24 24" fill="white" width="24" height="24">
                    <path d="M10 2a8 8 0 105.293 14.293l4.707 4.707 1.414-1.414-4.707-4.707A8 8 0 0010 2zm0 2a6 6 0 110 12 6 6 0 010-12z"/>
                </svg>
            </div>

            <div class="middle-icon" id="toggleSidebar">
                <!-- Botón de menú -->
                <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" viewBox="0 0 100 80" fill="white">
                    <rect width="100" height="10" rx="3"></rect>
                    <rect y="30" width="100" height="10" rx="3"></rect>
                    <rect y="60" width="100" height="10" rx="3"></rect>
                </svg>
            </div>

            <div class="bottom-icon">
                <!-- Icono abajo -->
                <svg class="icon-img" xmlns="http://www.w3.org/2000/svg" fill="white" width="24" height="24" viewBox="0 0 24 24">
                    <path d="M12 1C6.48 1 2 5.48 2 11v5a3 3 0 003 3h1v-8H5v-2c0-3.86 3.14-7 7-7s7 3.14 7 7v2h-1v8h1a3 3 0 003-3v-5c0-5.52-4.48-10-10-10zm-7 17a1 1 0 01-1-1v-1h2v2H5zm14-1a1 1 0 01-1 1h-1v-2h2v1z"/>
                </svg>
            </div>
        </div>

        <!-- Contenido adicional que se muestra solo al expandir -->
        <div class="sidebar-content">
            <!-- Aquí puedes meter el contenido que aparece cuando se expande -->
        </div>
    </aside>


    <main class="main-content">
        <header>
            <div class="global">
                <svg xmlns="http://www.w3.org/2000/svg" fill="white" width="30" height="30" viewBox="0 0 24 24" style="vertical-align: middle; margin-right: 8px;">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10
        10-4.48 10-10S17.52 2 12 2zm5.93 6h-2.95a13.48
        13.48 0 00-1.08-3.26A8.027 8.027 0 0117.93
        8zM12 4c.72 0 1.95 1.58 2.43 4H9.57C10.05
        5.58 11.28 4 12 4zM6.07 8a8.027 8.027 0
        013.03-3.26A13.48 13.48 0 008.02 8H6.07zM4.22
        10h3.22c-.09.65-.14 1.32-.14 2s.05 1.35.14
        2H4.22a7.88 7.88 0 010-4zm1.85 6h2.95a13.48
        13.48 0 001.08 3.26A8.027 8.027 0 016.07
        16zM12 20c-.72 0-1.95-1.58-2.43-4h4.86C13.95
        18.42 12.72 20 12 20zm2.9-1.26A13.48 13.48 0
        0015.98 16h2.95a8.027 8.027 0 01-3.03
        3.26zM16.56 14c.09-.65.14-1.32.14-2s-.05-1.35-.14-2h3.22a7.88
        7.88 0 010 4h-3.22z"/>
                </svg>
                <span style="color: white; font-size: 18px;">Global</span>
            </div>



        </header>

        <section class="hero">
            <!-- Logo dentro del hero -->
            <img src="imagenes/Grammer_Logo_Original_White_sRGB_screen_transparent.png" class="logo" alt="Siemens Logo" />

            <div class="text">
                <h1>Inclusion Y Diversidad<br />Grammer</h1>
            </div>
        </section>
        <section class="diversidad-inclusion">
            <div class="titulo-diversidad">
                <h2>Diversidad e Inclusión en Grammer</h2>
                <p>En Grammer valoramos a cada persona por lo que es. Nuestro compromiso con la diversidad y la inclusión es parte fundamental de nuestra cultura.</p>
            </div>

            <!-- Frase inspiradora -->
            <div class="frase-inspiradora">
                <blockquote>“Aquí cabemos todos. Aquí crecemos todos.”</blockquote>
            </div>

            <!-- Tarjetas de datos -->
            <div class="cards-diversidad">
                <div class="card">
                    <img src="imagenes/icono_lgbti.svg" alt="LGBTI+" />
                    <h3>Comunidad LGBTI+</h3>
                    <p><strong>41 personas</strong> forman parte activa de nuestra comunidad.</p>
                </div>
                <div class="card">
                    <img src="imagenes/icono_auditiva.svg" alt="Discapacidad Auditiva" />
                    <h3>Discapacidad Auditiva</h3>
                    <p>Incluimos a <strong>21 personas</strong> con discapacidad auditiva.</p>
                </div>
                <div class="card">
                    <img src="imagenes/icono_tercera_edad.svg" alt="Tercera edad" />
                    <h3>Personas de la tercera edad</h3>
                    <p><strong>14 personas</strong> activas en nuestra operación diaria.</p>
                </div>
                <div class="card">
                    <img src="imagenes/icono_ascendencia.svg" alt="Ascendencia nacional" />
                    <h3>Ascendencia Nacional</h3>
                    <p><strong>4 personas</strong> de diversas raíces culturales enriquecen nuestro equipo.</p>
                </div>
            </div>

            <!-- Galería visual -->
            <div class="galeria-inclusion">
                <img src="imagenes/inclusion1.jpg" alt="Equipo Grammer" />
                <img src="imagenes/inclusion2.jpg" alt="Taller de inclusión" />
                <img src="imagenes/inclusion3.jpg" alt="Personas trabajando" />
                <img src="imagenes/inclusion4.jpg" alt="Evento especial" />
            </div>

            <!-- Botón de unirse -->
            <div class="boton-unirse">
                <a href="vacantes.php" class="btn-unete">Quiero ser parte</a>
            </div>
        </section>
    </main>
</div>
<script>
    const sidebar = document.querySelector('.sidebar');
    const toggleBtn = document.querySelector('.middle-icon');

    toggleBtn.addEventListener('click', () => {
        // Alternar clase para expandir
        sidebar.classList.toggle('expanded');

        // Activar animación de rebote
        sidebar.classList.add('bounce');

        // Quitar rebote después de la animación
        setTimeout(() => {
            sidebar.classList.remove('bounce');
        }, 600);
    });
</script>

<script>
    function showTab(event, tabId) {
        // Quitar 'active' de todas
        document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(panel => panel.classList.add('hidden'));

        // Agregar 'active' a la clickeada y mostrar su panel
        event.currentTarget.classList.add('active');
        document.getElementById(tabId).classList.remove('hidden');
    }

</script>


<script>
    const imagenes = document.querySelectorAll('.imagen');
    const galeria = document.querySelector('.galeria');

    imagenes.forEach(imagen => {
        const btnCerrar = imagen.querySelector('.cerrar');
        const frase = imagen.querySelector('.frase');

        imagen.addEventListener('click', () => {
            const yaActiva = imagen.classList.contains('activa');
            imagenes.forEach(img => {
                img.classList.remove('activa');
                img.querySelector('.frase').textContent = '';
            });
            galeria.classList.remove('activa');

            if (!yaActiva) {
                imagen.classList.add('activa');
                frase.textContent = imagen.getAttribute('data-frase');
                galeria.classList.add('activa');
            }
        });

        btnCerrar.addEventListener('click', (e) => {
            e.stopPropagation(); // Evita el click en imagen
            imagen.classList.remove('activa');
            frase.textContent = '';
            galeria.classList.remove('activa');
        });
    });
</script>
</body>
</html>

