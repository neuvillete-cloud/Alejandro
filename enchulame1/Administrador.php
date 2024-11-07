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
            <a href="#" class="menu-item active">Dashboard</a>
            <a href="#" class="menu-item">Reportes</a>
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
                    <th>Correo</th>
                    <th>Área</th>
                    <th>Ubicación</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th>Foto</th>
                    <th>Acción</th>
                </tr>
                </thead>
                <tbody>
                <!-- Ejemplo de filas con datos ficticios -->
                <tr>
                    <td>#001</td>
                    <td>Ana Pérez</td>
                    <td>ana.perez@example.com</td>
                    <td>Producción</td>
                    <td>Edificio A, Piso 3</td>
                    <td>2024-10-01</td>
                    <td><span class="status pending">Pendiente</span></td>
                    <td><img src="foto-ejemplo.jpg" alt="Foto problema" class="report-photo"></td>
                    <td><button class="action-btn">Ver detalles</button></td>
                </tr>
                </tbody>
            </table>
        </section>
    </main>
</div>
</body>
</html>
