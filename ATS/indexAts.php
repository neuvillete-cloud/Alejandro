<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Siemens Energy Clone Mejorado</title>
    <link rel="stylesheet" href="css/indexAts.css">
</head>
<body>
<header>
    <div class="header-container">
        <div class="logo">
            <h1>SIEMENS</h1>
            <span>energy</span>
        </div>
        <nav>
            <a href="#">Buscar empleos</a>
            <a href="#">Acerca de nosotros</a>
            <a href="#">Programa de posgrado</a>
            <a href="#">Inclusión y diversidad</a>
            <a href="#">Inicio de sesión</a>
            <a href="#">🌐 Español ▾</a>
        </nav>
    </div>
</header>

<main>
    <section class="hero">
        <video autoplay muted loop playsinline class="hero-video">
            <source src="videos/Video2.mp4" type="video/mp4">
            Tu navegador no soporta la etiqueta de video.
        </video>
    </section>

    <div class="search-container">
        <h3>Hagamos que el mañana sea diferente, hoy mismo.</h3>
        <div class="search-box">
            <input type="text" placeholder="">
            <button>Search Jobs</button>
        </div>
    </div>

    <!-- Sección blanca agregada -->
    <div class="white-section">
        <p>En Grammer Querétaro, la innovación y la calidad nos impulsan cada día. Queremos redefinir la experiencia de confort y seguridad en la industria automotriz, y para ello, necesitamos tu talento. Únete a nuestro equipo y sé parte del futuro de la movilidad, donde juntos superamos límites y creamos soluciones que marcan la diferencia. La evolución de la industria comienza contigo.</p>

        <h2>Descubre cómo puedes encajar en nuestro equipo global</h2>
        <p>¿Sabías que Grammer está presente en 4 continentes con 48 compañías en 20 países? Nuestra presencia mundial nos permite estar siempre cerca de nuestros clientes, garantizando altos estándares de calidad en cada solución que ofrecemos. Únete a un equipo global donde la innovación y la excelencia marcan la diferencia. Déjate inspirar por nuestros equipos diversos y en constante crecimiento.</p>

        <div class="carousel-container">
            <button class="carousel-btn prev">&lt;</button>
            <div class="carousel">
                <div class="carousel-item">
                    <img src="imagenes/IMG_0076-min.JPG" alt="Alemania">
                    <h3>Alemania</h3>
                    <div class="carousel-icon">❯</div> <!-- Icono agregado -->
                </div>
                <div class="carousel-item">
                    <img src="imagenes/IMG_3238-min.JPG" alt="Estados Unidos">
                    <h3>Estados Unidos</h3>
                    <div class="carousel-icon">❯</div> <!-- Icono agregado -->
                </div>
                <div class="carousel-item">
                    <img src="imagenes/Sin%20título%20(250%20x%20375%20px)%20(250%20x%20380%20px)%20(250%20x%20385%20px).png" alt="Rumania">
                    <h3>Rumania</h3>
                    <div class="carousel-icon">❯</div> <!-- Icono agregado -->
                </div>
                <div class="carousel-item">
                    <img src="imagenes/HyM.png" alt="Reino Unido">
                    <h3>Reino Unido</h3>
                    <div class="carousel-icon">❯</div> <!-- Icono agregado -->
                </div>
                <!-- Nuevo país agregado -->
                <div class="carousel-item">
                    <img src="imagenes/8.png" alt="Japón">
                    <h3>Japón</h3>
                    <div class="carousel-icon">❯</div>
                </div>

            </div>
            <button class="carousel-btn next">&gt;</button>
        </div>

        <section class="impact-section">
            <h2>Donde puede crear un impacto</h2>
            <div class="impact-grid">
                <div class="impact-card">
                    <img src="imagenes/ingenieria.jpg" alt="Ingeniería">
                    <div class="impact-info">
                        <span>Ingeniería</span>
                        <span class="arrow">❯</span>
                    </div>
                </div>
                <div class="impact-card">
                    <img src="imagenes/proyectos.jpg" alt="Gestión de proyectos">
                    <div class="impact-info">
                        <span>Gestión de proyectos</span>
                        <span class="arrow">❯</span>
                    </div>
                </div>
                <div class="impact-card">
                    <img src="imagenes/ventas.jpg" alt="Ventas">
                    <div class="impact-info">
                        <span>Ventas</span>
                        <span class="arrow">❯</span>
                    </div>
                </div>
                <div class="impact-card">
                    <img src="imagenes/finanzas.jpg" alt="Finanzas">
                    <div class="impact-info">
                        <span>Finanzas</span>
                        <span class="arrow">❯</span>
                    </div>
                </div>
                <div class="impact-card">
                    <img src="imagenes/fabricacion.jpg" alt="Fabricación">
                    <div class="impact-info">
                        <span>Fabricación</span>
                        <span class="arrow">❯</span>
                    </div>
                </div>
                <div class="impact-card">
                    <img src="imagenes/servicio.jpg" alt="Servicios de atención al cliente">
                    <div class="impact-info">
                        <span>Servicios de atención al cliente</span>
                        <span class="arrow">❯</span>
                    </div>
                </div>
                <div class="impact-card">
                    <img src="imagenes/it.jpg" alt="Tecnología de la información">
                    <div class="impact-info">
                        <span>Tecnología de la información</span>
                        <span class="arrow">❯</span>
                    </div>
                </div>
                <div class="impact-card">
                    <img src="imagenes/graduados.jpg" alt="Oportunidades para estudiantes y graduados">
                    <div class="impact-info">
                        <span>Oportunidades para estudiantes y graduados</span>
                        <span class="arrow">❯</span>
                    </div>
                </div>
            </div>
        </section>

    </div>

</main>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const carousel = document.querySelector(".carousel");
        const prevBtn = document.querySelector(".carousel-btn.prev");
        const nextBtn = document.querySelector(".carousel-btn.next");

        const itemWidth = document.querySelector(".carousel-item").offsetWidth + 10; // 10 = gap
        let currentPosition = 0;

        nextBtn.addEventListener("click", () => {
            const maxScroll = carousel.scrollWidth - carousel.clientWidth;
            if (currentPosition + itemWidth <= maxScroll) {
                currentPosition += itemWidth;
            } else {
                currentPosition = maxScroll; // no pasarse
            }
            carousel.scrollTo({
                left: currentPosition,
                behavior: 'smooth'
            });
        });

        prevBtn.addEventListener("click", () => {
            if (currentPosition - itemWidth >= 0) {
                currentPosition -= itemWidth;
            } else {
                currentPosition = 0; // no ir más atrás del inicio
            }
            carousel.scrollTo({
                left: currentPosition,
                behavior: 'smooth'
            });
        });
    });
</script>

</body>
</html>
