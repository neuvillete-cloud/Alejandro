<?php
session_start();

// --- INICIO DE LÓGICA DE SEGURIDAD ---
// Si el usuario no está logueado o no es administrador (Rol != 1), se le redirige al index.
if (!isset($_SESSION['loggedin']) || $_SESSION['user_rol'] != 1) {
    header('Location: index.php');
    exit();
}
// --- FIN DE LÓGICA DE SEGURIDAD ---

include_once("dao/verificar_sesion.php");

// --- LÓGICA DE IDIOMA Y CONEXIÓN ---
$idioma_actual = 'es';
if (isset($_GET['lang']) && $_GET['lang'] == 'en') {
    $idioma_actual = 'en';
}
include_once("dao/conexionArca.php");
$con = new LocalConector();
$conex = $con->conectar();


// --- LÓGICA DE CONSULTA PARA ADMINISTRADORES ---
$tituloPagina = "Seguimiento de Contenciones";
$params = [];
$types = "";

$sql_base = "SELECT 
                s.IdSolicitud, 
                s.NumeroParte, 
                s.FechaRegistro, 
                p.NombreProvedor, 
                e.NombreEstatus,
                m.EstatusAprobacion
             FROM Solicitudes s
             JOIN Provedores p ON s.IdProvedor = p.IdProvedor
             JOIN Estatus e ON s.IdEstatus = e.IdEstatus
             LEFT JOIN Metodos m ON s.IdMetodo = m.IdMetodo";

// Cláusula WHERE base para administradores
$where_clauses = ["s.IdEstatus IN (3, 4)"];

// Aplicar filtros si existen
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

$sql_base .= " GROUP BY s.IdSolicitud ORDER BY s.IdSolicitud DESC";
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
    <!-- --- INICIO DE CAMBIO: Librería para exportar a Excel --- -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
    <!-- --- FIN DE CAMBIO --- -->
</head>
<body>
<header class="header">
    <div class="header-left">
        <div class="logo"><i class="fa-solid fa-shield-halved"></i>ARCA</div>
        <nav class="main-nav">
            <a href="index.php">Dashboard</a>
            <a href="Historial.php">Mis Solicitudes</a>
            <a href="seguimiento_contenidos.php" class="active">Seguimiento</a>
        </nav>
    </div>
    <div class="user-info">
        <div class="language-selector">
            <button type="button" class="lang-btn active" data-lang="es">ES</button>
            <button type="button" class="lang-btn" data-lang="en">EN</button>
        </div>
        <span>Bienvenido, <?php echo htmlspecialchars($_SESSION['user_nombre']); ?></span>
        <button class="logout-btn" onclick="window.location.href='dao/logout.php'">Cerrar Sesión <i class="fa-solid fa-right-from-bracket"></i></button>
    </div>
</header>

<main class="container">
    <div class="page-header">
        <h1><i class="fa-solid fa-tv"></i><?php echo htmlspecialchars($tituloPagina); ?></h1>
    </div>

    <div class="filter-bar">
        <form action="seguimiento_contenidos.php" method="GET" class="filter-form">
            <div class="form-group">
                <label for="folio">Buscar por Folio</label>
                <input type="number" name="folio" id="folio" placeholder="Ej: 123" value="<?php echo htmlspecialchars($_GET['folio'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="fecha">Buscar por Fecha</label>
                <input type="date" name="fecha" id="fecha" value="<?php echo htmlspecialchars($_GET['fecha'] ?? ''); ?>">
            </div>
            <button type="submit" class="btn-primary">Filtrar</button>
            <a href="seguimiento_contenidos.php" class="btn-tertiary">Limpiar</a>
        </form>
    </div>

    <div class="results-container">
        <table class="results-table">
            <thead>
            <tr>
                <th><i class="fa-solid fa-hashtag"></i>Folio</th>
                <th><i class="fa-solid fa-barcode"></i>No. Parte</th>
                <th><i class="fa-solid fa-truck-fast"></i>Proveedor</th>
                <th><i class="fa-solid fa-calendar-days"></i>Fecha de Registro</th>
                <th><i class="fa-solid fa-circle-info"></i>Estatus</th>
                <th><i class="fa-solid fa-cogs"></i>Acciones</th>
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
                            <button class="btn-icon btn-details" data-id="<?php echo $row['IdSolicitud']; ?>" title="Ver Detalles"><i class="fa-solid fa-eye"></i></button>
                            <!-- --- INICIO DE CAMBIO: Botón de historial --- -->
                            <button class="btn-icon btn-history" data-id="<?php echo $row['IdSolicitud']; ?>" title="Ver Historial de Inspección"><i class="fa-solid fa-history"></i></button>
                            <!-- --- FIN DE CAMBIO --- -->
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="no-results-cell">
                        <div class="no-results-content">
                            <i class="fa-solid fa-folder-open"></i>
                            <p>No se encontraron solicitudes en proceso o finalizadas</p>
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
            <h2>Detalles de la Solicitud <span id="modal-folio"></span></h2>
            <button id="modal-close" class="modal-close-btn">&times;</button>
        </div>
        <div id="modal-body" class="modal-body view-mode"></div>
    </div>
</div>

<!-- --- INICIO DE CAMBIO: Nuevo modal para el historial --- -->
<div id="history-modal" class="modal-overlay">
    <div class="modal-content" style="max-width: 1200px;">
        <div class="modal-header">
            <h2>Historial de Inspección <span id="history-modal-folio"></span></h2>
            <button id="history-modal-close" class="modal-close-btn">&times;</button>
        </div>
        <div id="history-modal-body" class="modal-body">
            <!-- El contenido de la tabla se cargará aquí vía AJAX -->
        </div>
        <div class="modal-footer" style="padding: 15px 30px; border-top: 1px solid var(--color-borde); text-align: right;">
            <button id="btn-export-excel" class="btn-primary" style="background-color: #1D6F42;"><i class="fa-solid fa-file-excel"></i> Descargar Excel</button>
        </div>
    </div>
</div>
<!-- --- FIN DE CAMBIO --- -->

<script src="js/ver_solicitudes.js"></script>

<!-- --- INICIO DE CAMBIO: Nuevo script para el modal de historial y exportación --- -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const historyModal = document.getElementById('history-modal');
        if (!historyModal) return;

        const historyModalCloseBtn = document.getElementById('history-modal-close');
        const historyModalBody = document.getElementById('history-modal-body');
        const historyModalFolio = document.getElementById('history-modal-folio');
        const btnExportExcel = document.getElementById('btn-export-excel');

        document.querySelector('.results-table').addEventListener('click', function(e) {
            const historyBtn = e.target.closest('.btn-history');
            if (historyBtn) {
                const idSolicitud = historyBtn.dataset.id;
                historyModalFolio.textContent = `S-${idSolicitud.padStart(4, '0')}`;
                historyModalBody.innerHTML = '<p style="text-align:center; padding: 40px 0;">Cargando historial...</p>';
                historyModal.classList.add('visible');

                fetch(`dao/api_obtener_historial.php?id=${idSolicitud}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Error al cargar el historial.');
                        }
                        return response.text();
                    })
                    .then(html => {
                        historyModalBody.innerHTML = html;
                    })
                    .catch(error => {
                        historyModalBody.innerHTML = `<p style="text-align:center; color: red; padding: 40px 0;">${error.message}</p>`;
                    });
            }
        });

        function closeHistoryModal() {
            historyModal.classList.remove('visible');
            historyModalBody.innerHTML = '';
        }

        historyModalCloseBtn.addEventListener('click', closeHistoryModal);
        historyModal.addEventListener('click', (e) => {
            if (e.target === historyModal) {
                closeHistoryModal();
            }
        });

        btnExportExcel.addEventListener('click', function() {
            const table = document.getElementById('tabla-historial-exportar');
            if (!table) {
                Swal.fire('Error', 'No se encontró la tabla para exportar.', 'error');
                return;
            }

            const wb = XLSX.utils.table_to_book(table, { sheet: "Historial" });
            const folio = historyModalFolio.textContent;
            XLSX.writeFile(wb, `Historial_${folio}.xlsx`);
        });
    });
</script>
<!-- --- FIN DE CAMBIO --- -->

</body>
</html>


