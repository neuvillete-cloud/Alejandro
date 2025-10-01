<?php
// --- INICIO DE LÓGICA DE SESIÓN MEJORADA ---
// Iniciar la sesión al principio de todo es crucial.
session_start();

// CASO 1: Un usuario NO logueado llega con un token.
// Lo mandamos a iniciar sesión, pero guardamos a dónde debe volver.
if (isset($_GET['token']) && !isset($_SESSION['loggedin'])) {
    // El destino incluye el token para que podamos recuperarlo después del login.
    $_SESSION['url_destino_post_login'] = 'Historial.php?token=' . urlencode($_GET['token']);
    header('Location: acceso.php');
    exit();
}

// CASO 2: Un usuario YA logueado recibe un nuevo token (ej. abre otro link).
// Actualizamos el token que debe ver y limpiamos la URL.
if (isset($_GET['token']) && isset($_SESSION['loggedin'])) {
    $_SESSION['vista_token_actual'] = $_GET['token'];
    header('Location: Historial.php'); // Redirigimos para limpiar la URL.
    exit();
}

// CASO 3: El usuario quiere salir del modo invitado y ver sus propias solicitudes.
if (isset($_GET['modo']) && $_GET['modo'] === 'propio') {
    unset($_SESSION['vista_token_actual']);
    header('Location: Historial.php');
    exit();
}

// CASO 4: Intento de acceso sin estar logueado y sin token.
if (!isset($_SESSION['loggedin'])) {
    header('Location: acceso.php');
    exit();
}
// --- FIN DE LÓGICA DE SESIÓN MEJORADA ---

include_once("dao/verificar_sesion.php");


// --- LÓGICA DE IDIOMA Y CONEXIÓN (sin cambios) ---
$idioma_actual = 'es';
if (isset($_GET['lang']) && $_GET['lang'] == 'en') {
    $idioma_actual = 'en';
}
include_once("dao/conexionArca.php");
$con = new LocalConector();
$conex = $con->conectar();


// --- LÓGICA DE VISTA MODIFICADA PARA SER PERSISTENTE ---
$modoVista = 'usuario_logueado'; // Por defecto
$tituloPagina = "Mis Solicitudes de Contención";
$params = [];
$types = "";

if (isset($_SESSION['vista_token_actual'])) {
    $modoVista = 'invitado';
    $tituloPagina = "Solicitud Compartida";
    $token = $_SESSION['vista_token_actual'];

    // Consulta para el modo invitado (no necesita cambios)
    $sql_base = "SELECT s.IdSolicitud, s.NumeroParte, s.FechaRegistro, p.NombreProvedor, e.NombreEstatus 
                 FROM Solicitudes s
                 JOIN SolicitudesCompartidas sc ON s.IdSolicitud = sc.IdSolicitud
                 JOIN Provedores p ON s.IdProvedor = p.IdProvedor
                 JOIN Estatus e ON s.IdEstatus = e.IdEstatus
                 WHERE sc.Token = ?";
    $params[] = $token;
    $types = "s";

} else {
    // --- MODO NORMAL: CONSULTA MODIFICADA PARA PERSISTENCIA ---
    // Se añade un LEFT JOIN a SolicitudesCompartidas para saber si ya se envió un correo.
    $sql_base = "SELECT 
                    s.IdSolicitud, 
                    s.NumeroParte, 
                    s.FechaRegistro, 
                    p.NombreProvedor, 
                    e.NombreEstatus,
                    MAX(sc.IdCompartido) as IdCompartido
                 FROM Solicitudes s
                 JOIN Provedores p ON s.IdProvedor = p.IdProvedor
                 JOIN Estatus e ON s.IdEstatus = e.IdEstatus
                 LEFT JOIN SolicitudesCompartidas sc ON s.IdSolicitud = sc.IdSolicitud";

    $where_clauses = [];

    // --- INICIO DE LÓGICA DE ROLES ---
    // Si el usuario NO es un administrador (ej. IdRol = 1), se filtra por su ID.
    // Si es administrador, esta condición se omite, permitiéndole ver todo.
    if (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] != 1) { // <-- ¡CAMBIA EL '1' SI TU ID DE ADMIN ES OTRO!
        $where_clauses[] = "s.IdUsuario = ?";
        $params[] = $_SESSION['user_id'];
        $types .= "i";
    }
    // --- FIN DE LÓGICA DE ROLES ---


    if (!empty($_GET['folio'])) {
        $where_clauses[] = "s.IdSolicitud = ?";
        $params[] = $_GET['folio'];
        $types .= "i";
    }

    if (!empty($_GET['fecha'])) {
        $where_clauses[] = "DATE(s.FechaRegistro) = ?";
        $params[] = $_GET['fecha'];
        $types .= "s";
    }

    if (count($where_clauses) > 0) {
        $sql_base .= " WHERE " . implode(" AND ", $where_clauses);
    }

    // Agrupamos para evitar filas duplicadas si una solicitud se comparte varias veces.
    $sql_base .= " GROUP BY s.IdSolicitud";
}

$sql_base .= " ORDER BY s.IdSolicitud DESC";
$stmt = $conex->prepare($sql_base);
if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$solicitudes = $stmt->get_result();
$conex->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($tituloPagina); ?> - ARCA</title>
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
            <?php if ($modoVista === 'usuario_logueado'): ?>
                <a href="index.php" data-translate-key="nav_dashboard">Dashboard</a>
            <?php endif; ?>
            <a href="Historial.php" class="active" data-translate-key="nav_myRequests">Mis Solicitudes</a>
        </nav>
    </div>
    <div class="user-info">
        <div class="language-selector">
            <button type="button" class="lang-btn active" data-lang="es">ES</button>
            <button type="button" class="lang-btn" data-lang="en">EN</button>
        </div>
        <span><span data-translate-key="welcome">Bienvenido</span>, <?php echo htmlspecialchars($_SESSION['user_nombre']); ?></span>
        <button class="logout-btn" onclick="window.location.href='dao/logout.php'"><span data-translate-key="logout">Cerrar Sesión</span> <i class="fa-solid fa-right-from-bracket"></i></button>
    </div>
</header>

<main class="container">
    <div class="page-header">
        <h1><i class="fa-solid fa-list-check"></i><span data-translate-key="mainTitle"><?php echo htmlspecialchars($tituloPagina); ?></span></h1>

        <?php if ($modoVista === 'invitado'): ?>
            <a href="Historial.php?modo=propio" class="btn-primary" style="background-color: #6c757d;"><i class="fa-solid fa-arrow-left"></i> Volver a mis solicitudes</a>
        <?php elseif ($modoVista === 'usuario_logueado'): ?>
            <a href="nueva_solicitud.php" class="btn-primary"><i class="fa-solid fa-plus"></i> <span data-translate-key="btn_createNewRequest">Crear Nueva Solicitud</span></a>
        <?php endif; ?>
    </div>

    <?php if ($modoVista === 'usuario_logueado'): ?>
        <div class="filter-bar">
            <form action="Historial.php" method="GET" class="filter-form">
                <div class="form-group">
                    <label for="folio" data-translate-key="label_searchByFolio">Buscar por Folio</label>
                    <input type="number" name="folio" id="folio" placeholder="Ej: 123" value="<?php echo htmlspecialchars($_GET['folio'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="fecha" data-translate-key="label_searchByDate">Buscar por Fecha</label>
                    <input type="date" name="fecha" id="fecha" value="<?php echo htmlspecialchars($_GET['fecha'] ?? ''); ?>">
                </div>
                <button type="submit" class="btn-primary" data-translate-key="btn_filter">Filtrar</button>
                <a href="Historial.php" class="btn-tertiary" data-translate-key="btn_clear">Limpiar</a>
            </form>
        </div>
    <?php endif; ?>

    <div class="results-container">
        <table class="results-table">
            <thead>
            <tr>
                <th><i class="fa-solid fa-hashtag"></i><span data-translate-key="table_folio">Folio</span></th>
                <th><i class="fa-solid fa-barcode"></i><span data-translate-key="table_partNumber">No. Parte</span></th>
                <th><i class="fa-solid fa-truck-fast"></i><span data-translate-key="table_supplier">Proveedor</span></th>
                <th><i class="fa-solid fa-calendar-days"></i><span data-translate-key="table_date">Fecha de Registro</span></th>
                <th><i class="fa-solid fa-circle-info"></i><span data-translate-key="table_status">Estatus</span></th>
                <th><i class="fa-solid fa-cogs"></i><span data-translate-key="table_actions">Acciones</span></th>
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
                        <td>
                            <?php $estatus_clase = "status-" . strtolower(str_replace(' ', '-', $row['NombreEstatus'])); ?>
                            <span class="status <?php echo $estatus_clase; ?>"><?php echo htmlspecialchars($row['NombreEstatus']); ?></span>
                        </td>
                        <td class="actions-cell">
                            <button class="btn-icon btn-details" data-id="<?php echo $row['IdSolicitud']; ?>" data-translate-key-title="title_viewDetails" title="Ver Detalles"><i class="fa-solid fa-eye"></i></button>

                            <?php if ($modoVista === 'usuario_logueado'): ?>

                                <?php // --- INICIO DE LA LÓGICA DE BOTÓN PERSISTENTE --- ?>
                                <?php if (isset($row['IdCompartido']) && $row['IdCompartido'] !== null): ?>
                                    <?php // Si ya fue compartida, muestra el botón desactivado con la palomita. ?>
                                    <button class="btn-icon btn-email sent" data-translate-key-title="title_emailSent" title="Correo Enviado" disabled>
                                        <i class="fa-solid fa-check"></i>
                                    </button>
                                <?php else: ?>
                                    <?php // Si no ha sido compartida, muestra el botón normal para enviar. ?>
                                    <button class="btn-icon btn-email" data-id="<?php echo $row['IdSolicitud']; ?>" data-translate-key-title="title_sendByEmail" title="Enviar por Correo">
                                        <i class="fa-solid fa-envelope"></i>
                                    </button>
                                <?php endif; ?>
                                <?php // --- FIN DE LA LÓGICA DE BOTÓN PERSISTENTE --- ?>

                            <?php else: // Modo Invitado ?>
                                <a href="trabajar_solicitud.php?id=<?php echo $row['IdSolicitud']; ?>" class="btn-icon" title="Empezar a Trabajar" style="text-decoration: none; background-color: #5c85ad; color: #ffffff;"><i class="fa-solid fa-hammer"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="no-results-cell">
                        <div class="no-results-content">
                            <i class="fa-solid fa-folder-open"></i>
                            <p data-translate-key="noResults">
                                <?php
                                if ($modoVista === 'invitado') {
                                    echo "No se encontró la solicitud compartida o el enlace ha expirado.";
                                } else {
                                    echo "No se encontraron solicitudes";
                                }
                                ?>
                            </p>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<div id="details-modal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h2><span data-translate-key="modal_title">Detalles de la Solicitud</span> <span id="modal-folio"></span></h2>
            <button id="modal-close" class="modal-close-btn">&times;</button>
        </div>
        <div id="modal-body" class="modal-body view-mode">
        </div>
    </div>
</div>

<script src="js/ver_solicitudes.js"></script>
</body>
</html>

