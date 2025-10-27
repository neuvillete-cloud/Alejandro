<?php
// Incluye el script que verifica si hay una sesión activa
include_once("dao/verificar_sesion.php");
if (!isset($_SESSION['loggedin'])) { header('Location: acceso.php'); exit(); }
?>
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
        :root {
            --color-primario: #4a6984;
            --color-secundario: #5c85ad;
            --color-acento: #8ab4d7;
            --color-fondo: #f4f6f9;
            --color-texto: #333333;
            --color-blanco: #ffffff;
            --sombra-suave: 0 4px 12px rgba(0,0,0,0.08);
        }
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
        .header {
            background-color: var(--color-blanco);
            box-shadow: var(--sombra-suave);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header-left {
            display: flex;
            align-items: center;
            gap: 40px;
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
        .main-nav a {
            font-family: 'Montserrat', sans-serif;
            text-decoration: none;
            color: #555;
            font-weight: 600;
            margin: 0 15px;
            padding-bottom: 5px;
            border-bottom: 2px solid transparent;
            transition: all 0.3s ease;
        }
        .main-nav a.active, .main-nav a:hover {
            color: var(--color-primario);
            border-bottom-color: var(--color-primario);
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
            gap: 8px;
        }
        .hero {
            background: linear-gradient(135deg, var(--color-primario), var(--color-secundario));
            color: var(--color-blanco);
            padding: 50px 40px;
            border-radius: 12px;
            margin-top: 20px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
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
        .hero-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
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
            display: inline-flex;
            align-items: center;
        }
        .cta-button i { margin-right: 10px; }
        .cta-button:hover { transform: translateY(-3px); box-shadow: 0 6px 15px rgba(0,0,0,0.2); }
        .cta-button.secondary {
            background-color: rgba(255, 255, 255, 0.2);
        }
        .cta-button.secondary:hover {
            background-color: rgba(255, 255, 255, 0.3);
        }
        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px; margin-top: 30px; }
        .metric-card { background-color: var(--color-blanco); padding: 25px; border-radius: 12px; box-shadow: var(--sombra-suave); display: flex; align-items: center; transition: all 0.3s ease; }
        .metric-card:hover { transform: translateY(-5px); box-shadow: 0 8px 18px rgba(0,0,0,0.1); }
        .metric-card .icon { font-size: 36px; color: var(--color-secundario); margin-right: 20px; width: 60px; height: 60px; display: grid; place-items: center; background-color: #e3f2fd; border-radius: 50%; }
        .metric-card .info h3 { margin: 0; font-family: 'Montserrat', sans-serif; color: var(--color-secundario); font-size: 16px; }
        .metric-card .info p { margin: 0; font-size: 32px; font-weight: 700; }
        .recent-activity { margin-top: 40px; background-color: var(--color-blanco); padding: 30px; border-radius: 12px; box-shadow: var(--sombra-suave); }
        .recent-activity h2 { font-family: 'Montserrat', sans-serif; margin-top: 0; }
        .activity-table { width: 100%; border-collapse: collapse; }
        .activity-table th, .activity-table td { padding: 15px; text-align: left; border-bottom: 1px solid #e0e0e0; }
        .activity-table th { font-weight: 700; color: #666; }
        .status { padding: 5px 12px; border-radius: 15px; font-weight: 700; font-size: 12px; }
        .status.open { background-color: #fff3e0; color: #ff9800; }
        .status.review { background-color: #e3f2fd; color: #2196f3; }
        .status.closed { background-color: #e8f5e9; color: #4caf50; }
    </style>
</head>
<body>

<header class="header">
    <div class="header-left">
        <div class="logo">
            <i class="fa-solid fa-shield-halved"></i>
            ARCA
        </div>
        <nav class="main-nav">
            <a href="index.php" class="active">Dashboard</a>
            <a href="Historial.php">Mis Solicitudes</a>
        </nav>
    </div>
    <div class="user-info">
        <span>Bienvenido, <?php echo htmlspecialchars($_SESSION['user_nombre']); ?></span>
        <button class="logout-btn" onclick="window.location.href='dao/logout.php'">
            Cerrar Sesión
            <i class="fa-solid fa-right-from-bracket"></i>
        </button>
    </div>
</header>

<main class="container">
    <section class="hero">
        <h1>Panel de Control de Contenciones</h1>
        <p>Sistema de Administración y Respuesta para Contenciones en Almacén. Inicie un nuevo registro o revise el estado de las solicitudes activas.</p>
        <div class="hero-buttons">
            <a href="nueva_solicitud.php" class="cta-button">
                <i class="fa-solid fa-plus"></i>
                Nueva Solicitud
            </a>
            <a href="Historial.php" class="cta-button secondary">
                <i class="fa-solid fa-list-check"></i>
                Ver mis Solicitudes
            </a>

            <!-- ==== INICIO: NUEVO BOTÓN HISTORIAL SAFE LAUNCH (PARA TODOS) ==== -->
            <a href="historial_safe_launch.php" class="cta-button secondary">
                <i class="fa-solid fa-clipboard-list"></i>
                Historial Safe Launch
            </a>
            <!-- ==== FIN: NUEVO BOTÓN ==== -->


            <!-- ==== INICIO: LÓGICA DE BOTONES CONDICIONAL ==== -->
            <?php if (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] == 1): ?>

                <!-- Botones de Admin -->
                <a href="aprobar_metodos.php" class="cta-button secondary">
                    <i class="fa-solid fa-check-double"></i>
                    Aprobar Métodos
                </a>
                <a href="seguimiento_contenidos.php" class="cta-button secondary">
                    <i class="fa-solid fa-chart-line"></i>
                    Seguimiento Contenidos
                </a>
                <a href="dashboard_reportes.php" class="cta-button secondary">
                    <i class="fa-solid fa-chart-pie"></i>
                    Reportes
                </a>

                <!-- Botón Safe Launch (AL FINAL para Admin) -->
                <a href="safe_launch.php" class="cta-button secondary">
                    <i class="fa-solid fa-rocket"></i>
                    Safe Launch
                </a>

            <?php else: ?>

                <!-- Botón Safe Launch (Posición normal para usuarios) -->
                <a href="safe_launch.php" class="cta-button secondary">
                    <i class="fa-solid fa-rocket"></i>
                    Safe Launch
                </a>

            <?php endif; ?>
            <!-- ==== FIN: LÓGICA DE BOTONES CONDICIONAL ==== -->

        </div>
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