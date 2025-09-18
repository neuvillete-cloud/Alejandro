<?php
include_once("dao/verificar_sesion.php");
if (!isset($_SESSION['loggedin'])) {
    header('Location: acceso.php');
    exit();
}

include_once("dao/conexionArca.php");
$con = new LocalConector();
$conex = $con->conectar();

// --- Lógica de Filtros ---
$sql_base = "SELECT s.IdSolicitud, s.NumeroParte, s.FechaRegistro, p.NombreProvedor, e.NombreEstatus 
                 FROM Solicitudes s
                 JOIN Provedores p ON s.IdProvedor = p.IdProvedor
                 JOIN Estatus e ON s.IdEstatus = e.IdEstatus";

$where_clauses = [];
$params = [];
$types = "";

// Filtrar por el ID del usuario que hizo la solicitud
$where_clauses[] = "s.IdUsuario = ?";
$params[] = $_SESSION['user_id'];
$types .= "i";

// Filtrar por Folio (IdSolicitud)
if (!empty($_GET['folio'])) {
    $where_clauses[] = "s.IdSolicitud = ?";
    $params[] = $_GET['folio'];
    $types .= "i";
}

// Filtrar por Fecha
if (!empty($_GET['fecha'])) {
    $where_clauses[] = "DATE(s.FechaRegistro) = ?";
    $params[] = $_GET['fecha'];
    $types .= "s";
}

if (count($where_clauses) > 0) {
    $sql_base .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql_base .= " ORDER BY s.IdSolicitud DESC";

$stmt = $conex->prepare($sql_base);
if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}
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
            <a href="Historial.php" class="active">Mis Solicitudes</a>
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
        <form action="Historial.php" method="GET" class="filter-form">
            <div class="form-group">
                <label for="folio">Buscar por Folio</label>
                <input type="number" name="folio" id="folio" placeholder="Ej: 123" value="<?php echo htmlspecialchars($_GET['folio'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="fecha">Buscar por Fecha</label>
                <input type="date" name="fecha" id="fecha" value="<?php echo htmlspecialchars($_GET['fecha'] ?? ''); ?>">
            </div>
            <button type="submit" class="btn-secondary">Filtrar</button>
            <a href="Historial.php" class="btn-tertiary">Limpiar</a>
        </form>
    </div>

    <div class="results-container">
        <table class="results-table">
            <thead>
            <tr>
                <th>Folio</th>
                <th>No. Parte</th>
                <th>Proveedor</th>
                <th>Fecha de Registro</th>
                <th>Estatus</th>
                <th>Acciones</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($solicitudes->num_rows > 0): ?>
                <?php while($row = $solicitudes->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo "S-" . str_pad($row['IdSolicitud'], 4, '0', STR_PAD_LEFT); ?></td>
                        <td><?php echo htmlspecialchars($row['NumeroParte']); ?></td>
                        <td><?php echo htmlspecialchars($row['NombreProvedor']); ?></td>
                        <td><?php echo date("d/m/Y H:i", strtotime($row['FechaRegistro'])); ?></td>
                        <td><span class="status open"><?php echo htmlspecialchars($row['NombreEstatus']); ?></span></td>
                        <td class="actions-cell">
                            <button class="btn-icon btn-details" data-id="<?php echo $row['IdSolicitud']; ?>" title="Ver Detalles"><i class="fa-solid fa-eye"></i></button>
                            <button class="btn-icon btn-email" data-id="<?php echo $row['IdSolicitud']; ?>" title="Enviar por Correo"><i class="fa-solid fa-envelope"></i></button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align:center;">No se encontraron solicitudes con los filtros aplicados.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<div id="details-modal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Detalles de la Solicitud <span id="modal-folio"></span></h2>
            <button id="modal-close" class="modal-close-btn">&times;</button>
        </div>
        <div id="modal-body" class="modal-body view-mode">
        </div>
    </div>
</div>

<script src="js/ver_solicitudes.js"></script>
</body>
</html>
