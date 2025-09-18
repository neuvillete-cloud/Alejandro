<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ARCA - Portada</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Montserrat:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        /* --- Paleta de Colores y Variables --- */
        :root {
            --color-primario: #1a237e; /* Azul oscuro corporativo */
            --color-secundario: #3f51b5; /* Azul medio */
            --color-acento: #448aff; /* Azul brillante para acciones */
            --color-fondo: #f4f6f9; /* Gris muy claro */
            --color-texto: #333333; /* Gris oscuro para texto */
            --color-blanco: #ffffff;
            --sombra-suave: 0 4px 12px rgba(0,0,0,0.08);
        }

        /* --- Estilos Generales --- */
        body {
            font-family: 'Lato', sans-serif;
            background-color: var(--color-fondo);
            color: var(--color-texto);
            margin: 0;
            line-height: 1.6;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        /* --- Barra de Navegación Superior (Header) --- */
        .header {
            background-color: var(--color-blanco);
            box-shadow: var(--sombra-suave);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-family: 'Montserrat', sans-serif;
            font-size: 28px;
            font-weight: 700;
            color: var(--color-primario);
        }

        .logo i {
            margin-right: 10px;
        }

        .user-info {
            display: flex;
            align-items: center;
        }

        .user-info span {
            margin-right: 20px;
            font-weight: 700;
        }

        .logout-btn {
            background: none;
            border: none;
            color: var(--color-secundario);
            font-size: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
        }

        .logout-btn:hover {
            color: var(--color-primario);
        }

        .logout-btn i {
            margin-left: 8px;
        }

        /* --- Sección Principal (Hero) --- */
        .hero {
            background-color: var(--color-primario);
            color: var(--color-blanco);
            padding: 50px 40px;
            border-radius: 12px;
            margin-top: 20px;
            box-shadow: 0 8px 20px rgba(26, 35, 126, 0.2);
        }

        .hero h1 {
            font-family: 'Montserrat', sans-serif;
            font-size: 36px;
            margin-top: 0;
            margin-bottom: 10px;
        }

        .hero p {
            font-size: 18px;
            opacity: 0.9;
            max-width: 700px;
            margin-bottom: 30px;
        }

        .cta-button {
            background-color: var(--color-acento);
            color: var(--color-blanco);
            font-family: 'Montserrat', sans-serif;
            font-size: 18px;
            font-weight: 600;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .cta-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(68, 138, 255, 0.4);
        }

        .cta-button i {
            margin-right: 10px;
        }

        /* --- Panel de Métricas (Dashboard) --- */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .metric-card {
            background-color: var(--color-blanco);
            padding: 25px;
            border-radius: 12px;
            box-shadow: var(--sombra-suave);
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }

        .metric-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 18px rgba(0,0,0,0.1);
        }

        .metric-card .icon {
            font-size: 36px;
            color: var(--color-acento);
            margin-right: 20px;
            width: 60px;
            height: 60px;
            display: grid;
            place-items: center;
            background-color: #e3f2fd;
            border-radius: 50%;
        }

        .metric-card .info h3 {
            margin: 0;
            font-family: 'Montserrat', sans-serif;
            color: var(--color-secundario);
            font-size: 16px;
        }

        .metric-card .info p {
            margin: 0;
            font-size: 32px;
            font-weight: 700;
        }

        /* --- Tabla de Actividad Reciente --- */
        .recent-activity {
            margin-top: 40px;
            background-color: var(--color-blanco);
            padding: 30px;
            border-radius: 12px;
            box-shadow: var(--sombra-suave);
        }

        .recent-activity h2 {
            font-family: 'Montserrat', sans-serif;
            margin-top: 0;
        }

        .activity-table {
            width: 100%;
            border-collapse: collapse;
        }

        .activity-table th, .activity-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        .activity-table th {
            font-weight: 700;
            color: #666;
        }

        .status {
            padding: 5px 12px;
            border-radius: 15px;
            font-weight: 700;
            font-size: 12px;
        }

        .status.open { background-color: #fff3e0; color: #ff9800; }
        .status.review { background-color: #e3f2fd; color: #2196f3; }
        .status.closed { background-color: #e8f5e9; color: #4caf50; }

    </style>
</head>
<body>

<header class="header">
    <div class="logo">
        <i class="fa-solid fa-shield-halved"></i>
        ARCA
    </div>
    <div class="user-info">
        <span>Bienvenido, [Nombre de Usuario]</span>
        <button class="logout-btn">
            Cerrar Sesión
            <i class="fa-solid fa-right-from-bracket"></i>
        </button>
    </div>
</header>

<main class="container">

    <section class="hero">
        <h1>Panel de Control de Contenciones</h1>
        <p>Sistema de Administración y Respuesta para Contenciones en Almacén. Inicie un nuevo registro o revise el estado de las solicitudes activas.</p>
        <a href="nueva_solicitud.php" class="cta-button">
            <i class="fa-solid fa-plus"></i>
            Nueva Solicitud de Contención
        </a>
    </section>

    <section class="dashboard-grid">
        <div class="metric-card">
            <div class="icon"><i class="fa-solid fa-box-open"></i></div>
            <div class="info">
                <h3>Solicitudes Abiertas</h3>
                <p>12</p>
            </div>
        </div>
        <div class="metric-card">
            <div class="icon"><i class="fa-solid fa-user-clock"></i></div>
            <div class="info">
                <h3>Pendientes de Revisión</h3>
                <p>3</p>
            </div>
        </div>
        <div class="metric-card">
            <div class="icon"><i class="fa-solid fa-boxes-stacked"></i></div>
            <div class="info">
                <h3>Material Retenido (Pzs)</h3>
                <p>1,450</p>
            </div>
        </div>
        <div class="metric-card">
            <div class="icon"><i class="fa-solid fa-truck-fast"></i></div>
            <div class="info">
                <h3>Proveedores con Incidencias</h3>
                <p>5</p>
            </div>
        </div>
    </section>

    <section class="recent-activity">
        <h2>Actividad Reciente</h2>
        <table class="activity-table">
            <thead>
            <tr>
                <th>ID Solicitud</th>
                <th>Número de Parte</th>
                <th>Proveedor</th>
                <th>Fecha</th>
                <th>Estatus</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>S-0125</td>
                <td>PN-5874A</td>
                <td>Componentes Industriales S.A.</td>
                <td>15/09/2025</td>
                <td><span class="status open">Abierta</span></td>
            </tr>
            <tr>
                <td>S-0124</td>
                <td>PN-9912B</td>
                <td>Metales del Norte</td>
                <td>14/09/2025</td>
                <td><span class="status review">En Revisión</span></td>
            </tr>
            <tr>
                <td>S-0123</td>
                <td>PN-3010C</td>
                <td>Plásticos ABC</td>
                <td>12/09/2025</td>
                <td><span class="status closed">Cerrada</span></td>
            </tr>
            <tr>
                <td>S-0122</td>
                <td>PN-5874A</td>
                <td>Componentes Industriales S.A.</td>
                <td>11/09/2025</td>
                <td><span class="status closed">Cerrada</span></td>
            </tr>
            </tbody>
        </table>
    </section>

</main>

</body>
</html>
