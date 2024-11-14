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
            <a href="#" class="menu-item active">Reportes</a>
            <a href="#" class="menu-item">Dashboard</a>
            <a href="#" class="menu-item">Estadísticas</a>
            <a href="#" class="menu-item">Usuarios</a>
            <a href="#" class="menu-item">Configuración</a>
        </nav>
    </aside>

    <!-- Main content -->
    <main class="main-content">
        <header class="header">
            <h1>Reportes</h1>
            <div class="filters">
                <!-- Buscador por ID -->
                <label for="search-id">Buscar por ID:</label>
                <div class="search-container">
                    <input type="text" id="search-id" placeholder="ID del reporte">
                    <span id="search-icon" class="search-icon">&#128269;</span> <!-- Lupa dentro del input -->
                </div>

                <!-- Filtro por Nave -->
                <label for="nave">Filtrar por Nave:</label>
                <select id="nave">
                    <option value="">Seleccionar Nave</option>
                    <option value="Nave 1">nave 1</option>
                    <option value="Nave 2">nave 2</option>
                    <option value="Nave 3">nave 3</option>
                </select>

                <!-- Paginación -->
                <label for="report-count">Reportes por página:</label>
                <select id="report-count">
                    <option value="5">5</option>
                    <option value="25">25</option>
                    <option value="100">100</option>
                    <option value="0">Todos</option>
                </select>

                <button id="apply-filters">Aplicar filtros</button>
            </div>
        </header>

        <!-- Tabla de reportes -->
        <section class="report-list">
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
    </main>
</div>

<script src="js/tablaAdmin.js"></script> <!-- Enlaza el archivo JavaScript para cargar los datos -->
</body>
</html>


