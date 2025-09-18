<?php
// ... (Tu código PHP para obtener las solicitudes no cambia) ...
include_once("dao/verificar_sesion.php");
if (!isset($_SESSION['loggedin'])) { header('Location: acceso.html'); exit(); }
include_once("dao/conexionArca.php");
$con = new LocalConector();
$conex = $con->conectar();
$sql_base = "SELECT s.IdSolicitud, s.NumeroParte, s.FechaRegistro, p.NombreProvedor, e.NombreEstatus 
                 FROM Solicitudes s
                 JOIN Provedores p ON s.IdProvedor = p.IdProvedor
                 JOIN Estatus e ON s.IdEstatus = e.IdEstatus";
$where_clauses = []; $params = []; $types = "";
$where_clauses[] = "s.IdUsuario = ?"; $params[] = $_SESSION['user_id']; $types .= "i";
if (!empty($_GET['folio'])) { $where_clauses[] = "s.IdSolicitud = ?"; $params[] = $_GET['folio']; $types .= "i"; }
if (!empty($_GET['fecha'])) { $where_clauses[] = "DATE(s.FechaRegistro) = ?"; $params[] = $_GET['fecha']; $types .= "s"; }
if (count($where_clauses) > 0) { $sql_base .= " WHERE " . implode(" AND ", $where_clauses); }
$sql_base .= " ORDER BY s.IdSolicitud DESC";
$stmt = $conex->prepare($sql_base);
if (!empty($types)) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$solicitudes = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Solicitudes - ARCA</title>
    <link rel="stylesheet" href="css/estilosHistorial.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Montserrat:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<header class="header">
    <div class="header-left">
        <div class="logo"><i class="fa-solid fa-shield-halved"></i>ARCA</div>
        <nav class="main-nav">
            <a href="index.php">Dashboard</a>
            <a href="ver_solicitudes.php" class="active">Mis Solicitudes</a>
        </nav>
    </div>
    <div class="user-info">
        <span>Bienvenido, <?php echo htmlspecialchars($_SESSION['user_nombre']); ?></span>
        <button class="logout-btn" onclick="window.location.href='dao/logout.php'">Cerrar Sesión <i class="fa-solid fa-right-from-bracket"></i></button>
    </div>
</header>

<main class="container">
    <div class="page-header">
        <h1>Mis Solicitudes de Contención</h1>
        <a href="nueva_solicitud.php" class="btn-primary"><i class="fa-solid fa-plus"></i> Crear Nueva Solicitud</a>
    </div>

    <div class="filter-bar">
        <form action="ver_solicitudes.php" method="GET" class="filter-form">
            <div class="form-group"><label for="folio">Buscar por Folio</label><input type="number" name="folio" id="folio" placeholder="Ej: 123" value="<?php echo htmlspecialchars($_GET['folio'] ?? ''); ?>"></div>
            <div class="form-group"><label for="fecha">Buscar por Fecha</label><input type="date" name="fecha" id="fecha" value="<?php echo htmlspecialchars($_GET['fecha'] ?? ''); ?>"></div>
            <button type="submit" class="btn-secondary">Filtrar</button>
            <a href="ver_solicitudes.php" class="btn-tertiary">Limpiar</a>
        </form>
    </div>

    <div class="solicitud-list">
        <?php if ($solicitudes->num_rows > 0): ?>
            <?php while($row = $solicitudes->fetch_assoc()): ?>
                <div class="solicitud-card">
                    <div class="card-header">
                        <span class="folio">Folio: S-<?php echo str_pad($row['IdSolicitud'], 4, '0', STR_PAD_LEFT); ?></span>
                        <span class="status-badge open"><?php echo htmlspecialchars($row['NombreEstatus']); ?></span>
                    </div>
                    <div class="card-body">
                        <div class="info-block"><strong>No. Parte</strong><span><?php echo htmlspecialchars($row['NumeroParte']); ?></span></div>
                        <div class="info-block"><strong>Proveedor</strong><span><?php echo htmlspecialchars($row['NombreProvedor']); ?></span></div>
                        <div class="info-block"><strong>Fecha</strong><span><?php echo date("d/m/Y H:i", strtotime($row['FechaRegistro'])); ?></span></div>
                    </div>
                    <div class="card-footer">
                        <button class="btn-secondary btn-email" data-id="<?php echo $row['IdSolicitud']; ?>"><i class="fa-solid fa-envelope"></i> Enviar</button>
                        <button class="btn-primary btn-details" data-id="<?php echo $row['IdSolicitud']; ?>"><i class="fa-solid fa-eye"></i> Ver Detalles</button>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align:center;">No se encontraron solicitudes con los filtros aplicados.</p>
        <?php endif; ?>
    </div>
</main>

<div id="details-modal" class="modal-overlay">
    <div class="modal-content modal-content-large">
        <div class="modal-header">
            <h2>Detalles de la Solicitud <span id="modal-folio"></span></h2>
            <button id="modal-close" class="modal-close-btn">&times;</button>
        </div>
        <div class="modal-body">
            <div class="modal-grid">
                <div id="modal-main-info" class="modal-main-info">
                </div>
                <div id="modal-attachments" class="modal-attachments">
                </div>
            </div>
        </div>
    </div>
</div>

<script src="js/ver_solicitudes.js"></script>
</body>
</html>