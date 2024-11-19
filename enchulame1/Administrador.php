<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrador - Reportes</title>
    <link rel="stylesheet" href="css/estilosAdministrador.css"> <!-- Vincula tu archivo CSS -->
</head>
<body>
<div class="container">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo">
            <h2>eReport</h2>
        </div>
        <nav class="menu">
            <a href="#" class="menu-item active" id="reportes-tab">Reportes</a>
            <a href="#" class="menu-item" id="dashboard-tab">Dashboard</a> <!-- Enlace al Dashboard -->
            <a href="#" class="menu-item">Estadísticas</a>
            <a href="#" class="menu-item">Usuarios</a>
            <a href="#" class="menu-item">Configuración</a>
        </nav>
    </aside>

    <!-- Main content -->
    <main class="main-content">
        <header class="header">
            <h1 id="main-header">Reportes</h1>
            <div class="filters">
                <!-- Filtro de Buscar por ID -->
                <div class="filter-item">
                    <label for="search-id">Buscar por ID:</label>
                    <div class="search-container">
                        <input type="text" id="search-id" placeholder="ID del reporte">
                        <span id="search-icon" class="search-icon">&#128269;</span> <!-- Icono de lupa -->
                    </div>
                </div>

                <!-- Filtro de Filtrar por Nave -->
                <div class="filter-item">
                    <label for="nave">Filtrar por Nave:</label>
                    <select id="nave">
                        <option value="">Seleccionar Nave</option>
                        <option value="Nave 1">Nave 1</option>
                        <option value="Nave 2">Nave 2</option>
                        <option value="Nave 3">Nave 3</option>
                    </select>
                </div>

                <!-- Paginación -->
                <div class="filter-item">
                    <label for="report-count">Reportes por página:</label>
                    <select id="report-count">
                        <option value="5">5</option>
                        <option value="25">25</option>
                        <option value="100">100</option>
                        <option value="0">Todos</option>
                    </select>
                </div>
            </div>

            <!-- Contenedor de la imagen -->
            <div class="image-container">
                <img src="imagenes/Grammer_Logo_Original_Blue_sRGB_screen_transparent.png" alt="Imagen decorativa">
            </div>
        </header>

        <!-- Contenido Reportes (Visible por defecto) -->
        <section class="report-list" id="reportes-section">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Área</th>
                    <th>Ubicación</th>
                    <th>Fecha</th>
                    <th>Descripción del Problema</th>
                    <th>Fecha Finalizado</th>
                    <th>Estado</th>
                    <th>Acción</th>
                </tr>
                </thead>
                <tbody id="tablaReportes">
                <!-- Las filas se generarán dinámicamente desde el script -->
                </tbody>
            </table>
        </section>

        <!-- Contenido Dashboard (Oculto por defecto) -->
        <section class="dashboard-section" id="dashboard-section" style="display: none;">
            <h2>Dashboard</h2>
            <!-- Aquí puedes agregar los elementos de tu Dashboard -->
            <p>Bienvenido al Dashboard. Aquí van las métricas y estadísticas.</p>
        </section>
    </main>
</div>

<script src="js/tablaAdmin.js"></script> <!-- Enlaza el archivo JavaScript para cargar los datos -->

<script>
    // JavaScript para cambiar entre las secciones sin recargar la página
    document.getElementById("reportes-tab").addEventListener("click", function() {
        document.getElementById("reportes-section").style.display = "block";
        document.getElementById("dashboard-section").style.display = "none";
        document.getElementById("main-header").innerText = "Reportes"; // Cambiar el título
        // Cambiar el estilo activo
        document.getElementById("reportes-tab").classList.add("active");
        document.getElementById("dashboard-tab").classList.remove("active");
    });

    document.getElementById("dashboard-tab").addEventListener("click", function() {
        document.getElementById("reportes-section").style.display = "none";
        document.getElementById("dashboard-section").style.display = "block";
        document.getElementById("main-header").innerText = "Dashboard"; // Cambiar el título
        // Cambiar el estilo activo
        document.getElementById("dashboard-tab").classList.add("active");
        document.getElementById("reportes-tab").classList.remove("active");
    });
</script>
</body>
</html>

