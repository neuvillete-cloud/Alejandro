<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial - Reportes</title>
    <link rel="stylesheet" href="css/estilosAdministrador.css"> <!-- Vincula tu archivo CSS -->
</head>
<body>
<div class="container">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo">
            <h2>eSolicitante</h2>
        </div>
        <nav class="menu">
            <a href="#" class="menu-item active" id="reportes-tab">Tus Reportes</a>
        </nav>
    </aside>

    <!-- Main content -->
    <main class="main-content">
        <header class="header">
            <h1 id="main-header">Historial de Reportes</h1>
            <div class="filters" id="filters-section">
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
                        <option value="*">Todos</option>
                        <option value="5">5</option>
                        <option value="25">25</option>
                        <option value="100">100</option>
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
                    <th>Nombre</th>
                    <th>Área</th>
                    <th>Ubicación</th>
                    <th>Fecha</th>
                    <th>Descripción del Problema</th>
                    <th>Fecha Compromiso</th>
                    <th>Estado</th>
                </tr>
                </thead>
                <tbody id="tablaReportes">
                <!-- Las filas se generarán dinámicamente desde el script -->
                </tbody>
            </table>
        </section>
    </main>
</div>

<script src="js/tablaSolicitante.js"></script> <!-- Enlaza el archivo JavaScript para cargar los datos -->
</body>
</html>
