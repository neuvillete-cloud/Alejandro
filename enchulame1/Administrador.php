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
            <div class="date-filter">
                <label for="start-date">Desde:</label>
                <input type="date" id="start-date">
                <label for="end-date">Hasta:</label>
                <input type="date" id="end-date">
            </div>
        </header>
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
                    <th>Fecha Finalizado</th> <!-- Nueva columna -->
                    <th>Estado</th>
                    <th>Acción</th>
                </tr>
                </thead>
                <tbody id="tablaReportes">
                <!-- Las filas se generarán dinámicamente desde scripts.js -->
                </tbody>
            </table>
        </section>
    </main>
</div>

<script src="js/tablaAdmin.js"></script> <!-- Enlaza el archivo JavaScript para cargar los datos -->
</body>
</html>
