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
            <div class="filters" id="filters-section">
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
                        <option value="Nave 4">Nave 4</option>
                        <option value="Nave 5">Nave 5</option>
                        <option value="Nave 6">Nave 6</option>
                        <option value="Nave 7">Nave 7</option>
                        <option value="Nave 8">Nave 8</option>
                        <option value="Nave 9">Nave 9</option>
                        <option value="Nave 10">Nave 10</option>
                        <option value="Nave 11">Nave 11</option>
                        <option value="Nave 12">Nave 12</option>

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
                    <th>Fecha Compromiso</th>
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
            <h2>Graficas de estadisticas de reportes por meses</h2>
            <!-- Gráficas del Dashboard -->
            <div class="chart-container">
                <canvas id="reporteChart"></canvas>
            </div>

        </section>
    </main>
</div>
<script src="js/tablaAdmin.js"></script> <!-- Enlaza el archivo JavaScript para cargar los datos -->


<script src="js/dashboard.js"></script> <!-- Enlazamos el archivo JavaScript para cargar los datos y las gráficas -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <!-- Cargamos la librería Chart.js -->
<script>
    // JavaScript para cambiar entre las secciones sin recargar la página
    document.getElementById("reportes-tab").addEventListener("click", function() {
        document.getElementById("reportes-section").style.display = "block";
        document.getElementById("filters-section").style.display = "flex"; // Usar flex aquí
        document.getElementById("dashboard-section").style.display = "none";
        document.getElementById("main-header").innerText = "Reportes";
        document.getElementById("reportes-tab").classList.add("active");
        document.getElementById("dashboard-tab").classList.remove("active");
    });

    document.getElementById("dashboard-tab").addEventListener("click", function() {
        document.getElementById("reportes-section").style.display = "none";
        document.getElementById("filters-section").style.display = "none"; // Ocultar los filtros
        document.getElementById("dashboard-section").style.display = "block";
        document.getElementById("main-header").innerText = "Dashboard";
        document.getElementById("dashboard-tab").classList.add("active");
        document.getElementById("reportes-tab").classList.remove("active");
    });

</script>
</body>
</html>

