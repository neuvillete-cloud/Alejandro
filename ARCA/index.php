<?php
// Incluye el script que verifica si hay una sesión activa
include_once("dao/verificar_sesion.php");
if (!isset($_SESSION['loggedin'])) { header('Location: acceso.php'); exit(); }

// =========================================================================================================
// LÓGICA DE BASE DE DATOS (USANDO TU CLASE DE CONEXIÓN)
// =========================================================================================================

// Incluimos tu archivo de conexión
include_once("dao/conexionArca.php");
$con = new LocalConector();
$conex = $con->conectar();

// =========================================================================================================
// VALIDACIÓN DE DOMINIO GRAMMER (NUEVA LÓGICA)
// =========================================================================================================
// Como no tenemos el correo en la sesión, lo buscamos usando el ID del usuario
$esGrammer = false; // Por defecto asumimos que NO es Grammer por seguridad

if (isset($_SESSION['user_id'])) {
    $idUsuario = $_SESSION['user_id'];

    // Preparamos la consulta para buscar el correo de este usuario específico
    // NOTA: Asegúrate de que tu columna en la BD se llame 'Correo'. Si se llama 'email', cámbialo aquí.
    $stmtCorreo = $conex->prepare("SELECT Correo FROM Usuarios WHERE IdUsuario = ?");

    if ($stmtCorreo) {
        $stmtCorreo->bind_param("i", $idUsuario);
        $stmtCorreo->execute();
        $resultadoCorreo = $stmtCorreo->get_result();

        if ($fila = $resultadoCorreo->fetch_assoc()) {
            $correoUsuario = $fila['Correo'];

            // Verificamos si el correo contiene @grammer.com
            if (strpos(strtolower($correoUsuario), '@grammer.com') !== false) {
                $esGrammer = true;
            }
        }
        $stmtCorreo->close();
    }
}
// =========================================================================================================


// --- 1. MÉTRICAS ---

// A. Solicitudes Abiertas (Estatus 1=Recibido, 2=Asignado, 3=En Proceso)
$sqlAbiertas = "SELECT COUNT(*) AS total FROM Solicitudes WHERE IdEstatus IN (1, 2, 3)";
$resAbiertas = $conex->query($sqlAbiertas);
$metricas['abiertas'] = ($resAbiertas) ? ($resAbiertas->fetch_assoc()['total'] ?? 0) : 0;

// B. Pendientes de Revisión (Métodos)
// Tabla: Metodos, Columna: EstatusAprobacion = 'Pendiente'
$sqlPendientes = "SELECT COUNT(*) AS total FROM Metodos WHERE EstatusAprobacion = 'Pendiente'";
$resPendientes = $conex->query($sqlPendientes);
$metricas['pendientes'] = ($resPendientes) ? ($resPendientes->fetch_assoc()['total'] ?? 0) : 0;

// C. Material Retenido (Piezas)
// Sumamos la 'Cantidad' total de las solicitudes que están abiertas (en proceso de contención)
$sqlRetenido = "SELECT SUM(Cantidad) AS total FROM Solicitudes WHERE IdEstatus IN (1, 2, 3)";
$resRetenido = $conex->query($sqlRetenido);
$metricas['material_retenido'] = ($resRetenido) ? ($resRetenido->fetch_assoc()['total'] ?? 0) : 0;

// D. Proveedores con Incidencias Activas
// Contamos proveedores distintos involucrados en solicitudes abiertas
$sqlProveedores = "SELECT COUNT(DISTINCT IdProvedor) AS total FROM Solicitudes WHERE IdEstatus IN (1, 2, 3)";
$resProveedores = $conex->query($sqlProveedores);
$metricas['proveedores_activos'] = ($resProveedores) ? ($resProveedores->fetch_assoc()['total'] ?? 0) : 0;

// --- 2. ACTIVIDAD RECIENTE ---
// Obtenemos las últimas 5 solicitudes para la tabla inferior
$sqlRecientes = "
    SELECT 
        s.IdSolicitud, 
        s.NumeroParte, 
        p.NombreProvedor, 
        s.FechaRegistro, 
        e.NombreEstatus
    FROM Solicitudes s
    LEFT JOIN Provedores p ON s.IdProvedor = p.IdProvedor
    LEFT JOIN Estatus e ON s.IdEstatus = e.IdEstatus
    ORDER BY s.FechaRegistro DESC 
    LIMIT 5
";
$resultRecientes = $conex->query($sqlRecientes);

// Función auxiliar para clases CSS de estatus
function getStatusClass($statusName) {
    $statusName = strtolower($statusName);
    if (in_array($statusName, ['recibido', 'asignado'])) {
        return 'open';
    } elseif ($statusName === 'en proceso') {
        return 'review';
    } elseif ($statusName === 'cerrado') {
        return 'closed';
    }
    return 'open';
}
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
        <!-- MODIFICACIÓN: Nuevo título -->
        <h1>Panel de Control de Contenciones / Safe-launch</h1>
        <p>Sistema de Administración y Respuesta para Contenciones y Safe-launch. Inicie un nuevo registro o revise el estado de las solicitudes activas.</p>
        <div class="hero-buttons">

            <!-- VALIDACIÓN GRAMMER: Solo si es grammer ve el botón de crear -->
            <?php if ($esGrammer): ?>
                <!-- MODIFICACIÓN: Texto cambiado de "Nueva Solicitud" a "Contención" -->
                <a href="nueva_solicitud.php" class="cta-button">
                    <i class="fa-solid fa-plus"></i>
                    Contención
                </a>
            <?php endif; ?>

            <a href="Historial.php" class="cta-button secondary">
                <i class="fa-solid fa-list-check"></i>
                Ver mis Solicitudes
            </a>

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

                <!-- VALIDACIÓN GRAMMER para Admin: Safe Launch -->
                <?php if ($esGrammer): ?>
                    <!-- MODIFICACIÓN: Safe Launch (Admin) mismo color y signo + -->
                    <!-- Se eliminó la clase 'secondary' y se cambió fa-rocket por fa-plus -->
                    <a href="safe_launch.php" class="cta-button">
                        <i class="fa-solid fa-plus"></i>
                        Safe Launch
                    </a>
                    <a href="historial_safe_launch.php" class="cta-button secondary">
                        <i class="fa-solid fa-clipboard-list"></i>
                        Historial Safe Launch
                    </a>
                    <a href="dashboard_reporte_sl.php" class="cta-button secondary">
                        <i class="fa-solid fa-chart-bar"></i>
                        Reportes Safe Launch
                    </a>
                <?php endif; ?>

            <?php else: ?>
                <!-- Botones Usuario Estandard -->

                <!-- VALIDACIÓN GRAMMER para Usuario Estándar: Safe Launch -->
                <?php if ($esGrammer): ?>
                    <!-- MODIFICACIÓN: Safe Launch (Usuario) mismo color y signo + -->
                    <!-- Se eliminó la clase 'secondary' y se cambió fa-rocket por fa-plus -->
                    <a href="safe_launch.php" class="cta-button">
                        <i class="fa-solid fa-plus"></i>
                        Safe Launch
                    </a>
                    <a href="historial_safe_launch.php" class="cta-button secondary">
                        <i class="fa-solid fa-clipboard-list"></i>
                        Historial Safe Launch
                    </a>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </section>

    <section class="dashboard-grid">
        <div class="metric-card">
            <div class="icon"><i class="fa-solid fa-box-open"></i></div>
            <div class="info">
                <h3>Solicitudes Abiertas</h3>
                <p><?php echo number_format($metricas['abiertas']); ?></p>
            </div>
        </div>
        <div class="metric-card">
            <div class="icon"><i class="fa-solid fa-user-clock"></i></div>
            <div class="info">
                <h3>Pendientes de Revisión</h3>
                <p><?php echo number_format($metricas['pendientes']); ?></p>
            </div>
        </div>
        <div class="metric-card">
            <div class="icon"><i class="fa-solid fa-boxes-stacked"></i></div>
            <div class="info">
                <h3>Material Retenido (Pzs)</h3>
                <p><?php echo number_format($metricas['material_retenido']); ?></p>
            </div>
        </div>
        <div class="metric-card">
            <div class="icon"><i class="fa-solid fa-truck-fast"></i></div>
            <div class="info">
                <h3>Proveedores con Incidencias</h3>
                <p><?php echo number_format($metricas['proveedores_activos']); ?></p>
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
            <?php if ($resultRecientes && $resultRecientes->num_rows > 0): ?>
                <?php while($row = $resultRecientes->fetch_assoc()): ?>
                    <tr>
                        <td>S-<?php echo str_pad($row['IdSolicitud'], 4, '0', STR_PAD_LEFT); ?></td>
                        <td><?php echo htmlspecialchars($row['NumeroParte']); ?></td>
                        <td><?php echo htmlspecialchars($row['NombreProvedor'] ?? 'No Asignado'); ?></td>
                        <td><?php echo date("d/m/Y", strtotime($row['FechaRegistro'])); ?></td>
                        <td>
                            <span class="status <?php echo getStatusClass($row['NombreEstatus']); ?>">
                                <?php echo htmlspecialchars($row['NombreEstatus']); ?>
                            </span>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align: center; color: #777;">No hay actividad reciente registrada.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </section>
</main>

<?php
// Cerramos la conexión si el método close() existe en tu objeto mysqli
if(isset($conex) && method_exists($conex, 'close')) {
    $conex->close();
}
?>
</body>
</html>