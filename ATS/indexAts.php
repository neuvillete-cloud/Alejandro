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
            </div>
            <button class="carousel-btn next">&gt;</button>
        </div>
    </div>

</main>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const carousel = document.querySelector(".carousel");
        const prevButton = document.querySelector(".prev");
        const nextButton = document.querySelector(".next");
        const itemWidth = document.querySelector(".carousel-item").offsetWidth + 10;
        let scrollPosition = 0;

        nextButton.addEventListener("click", function() {
            if (scrollPosition > -(carousel.scrollWidth - itemWidth * 4)) {
                scrollPosition -= itemWidth;
                carousel.style.transform = `translateX(${scrollPosition}px)`;
            }
        });

        prevButton.addEventListener("click", function() {
            if (scrollPosition < 0) {
                scrollPosition += itemWidth;
                carousel.style.transform = `translateX(${scrollPosition}px)`;
            }
        });
    });
</script>

</body>
</html>
