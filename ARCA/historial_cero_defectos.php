<?php
// --- INICIO DE LÓGICA DE SESIÓN ---
session_start();

// CASO 1: Un usuario NO logueado llega con un token.
if (isset($_GET['token']) && !isset($_SESSION['loggedin'])) {
    $_SESSION['url_destino_post_login'] = 'historial_cero_defectos.php?token=' . urlencode($_GET['token']);
    header('Location: acceso.php');
    exit();
}

// CASO 2: Un usuario YA logueado recibe un nuevo token.
if (isset($_GET['token']) && isset($_SESSION['loggedin'])) {
    $_SESSION['vista_token_actual_zd'] = $_GET['token']; // Cambiado sufijo a _zd (Zero Defects)
    header('Location: historial_cero_defectos.php');
    exit();
}

// CASO 3: El usuario quiere salir del modo invitado.
if (isset($_GET['modo']) && $_GET['modo'] === 'propio') {
    unset($_SESSION['vista_token_actual_zd']);
    header('Location: historial_cero_defectos.php');
    exit();
}

// CASO 4: Intento de acceso sin estar logueado y sin token.
if (!isset($_SESSION['loggedin'])) {
    header('Location: acceso.php');
    exit();
}
// --- FIN DE LÓGICA DE SESIÓN ---

include_once("dao/verificar_sesion.php");

// --- LÓGICA DE IDIOMA Y CONEXIÓN ---
$idioma_actual = 'es';
if (isset($_GET['lang']) && $_GET['lang'] == 'en') {
    $idioma_actual = 'en';
}
include_once("dao/conexionArca.php");
$con = new LocalConector();
$conex = $con->conectar();


// --- LÓGICA DE VISTA ---
$modoVista = 'usuario_logueado';
$tituloPagina = "Historial de Cero Defectos";
$params = [];
$types = "";

if (isset($_SESSION['vista_token_actual_zd'])) {
    // --- MODO INVITADO ---
    $modoVista = 'invitado';
    $tituloPagina = "Cero Defectos Compartido";
    $token = $_SESSION['vista_token_actual_zd'];

    $sql_base = "SELECT 
                    cd.IdCeroDefectos, 
                    cd.Linea,
                    cd.Cliente, 
                    oem.NombreOEM,
                    cd.FechaRegistro, 
                    cd.IdEstatus,
                    e.NombreEstatus,
                    u.Nombre as NombreResponsable,
                    cd.RutaInstruccion
                 FROM CeroDefectosSolicitudes cd
                 JOIN CeroDefectosSolicitudesCompartidas cdc ON cd.IdCeroDefectos = cdc.IdCeroDefectos
                 JOIN Usuarios u ON cd.IdUsuario = u.IdUsuario
                 JOIN Estatus e ON cd.IdEstatus = e.IdEstatus
                 JOIN CeroDefectosOEM oem ON cd.IdOEM = oem.IdOEM
                 WHERE cdc.Token = ?";
    $params[] = $token;
    $types = "s";

} else {
    // --- MODO USUARIO LOGUEADO ---
    $sql_base = "SELECT 
                    cd.IdCeroDefectos, 
                    cd.Linea,
                    cd.Cliente,
                    oem.NombreOEM,
                    cd.FechaRegistro, 
                    cd.IdEstatus,
                    e.NombreEstatus,
                    u.Nombre as NombreResponsable,
                    cd.RutaInstruccion,
                    (SELECT cdc.IdCDCompartido 
                     FROM CeroDefectosSolicitudesCompartidas cdc 
                     WHERE cdc.IdCeroDefectos = cd.IdCeroDefectos 
                     LIMIT 1) as IdCompartido
                 FROM CeroDefectosSolicitudes cd
                 JOIN Usuarios u ON cd.IdUsuario = u.IdUsuario
                 JOIN Estatus e ON cd.IdEstatus = e.IdEstatus
                 JOIN CeroDefectosOEM oem ON cd.IdOEM = oem.IdOEM";

    $where_clauses = [];

    // Filtro de Rol (Si no es SuperUsuario - Rol 1, solo ve lo suyo)
    if (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] != 1) {
        $where_clauses[] = "cd.IdUsuario = ?";
        $params[] = $_SESSION['user_id'];
        $types .= "i";
    }

    // Filtros de búsqueda
    if (!empty($_GET['folio'])) {
        $where_clauses[] = "cd.IdCeroDefectos = ?";
        $params[] = $_GET['folio'];
        $types .= "i";
    }
    if (!empty($_GET['fecha'])) {
        $where_clauses[] = "DATE(cd.FechaRegistro) = ?";
        $params[] = $_GET['fecha'];
        $types .= "s";
    }
    // Filtro por Línea (Opcional, agregado por utilidad)
    if (!empty($_GET['linea'])) {
        $where_clauses[] = "cd.Linea LIKE ?";
        $params[] = "%" . $_GET['linea'] . "%";
        $types .= "s";
    }

    if (count($where_clauses) > 0) {
        $sql_base .= " WHERE " . implode(" AND ", $where_clauses);
    }
}

$sql_base .= " ORDER BY cd.IdCeroDefectos DESC";
$stmt = $conex->prepare($sql_base);
if (!$stmt) { die("Error preparing statement: " . $conex->error); }

if (!empty($types)) { $stmt->bind_param($types, ...$params); }

if (!$stmt->execute()) { die("Error executing statement: " . $stmt->error); }

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

    <style>
        .btn-icon.resubir { background-color: #fff3e0; color: #b75c09; }
        .btn-icon.resubir:hover { background-color: #ff9800; color: var(--color-blanco); }
        .status.status-rechazado { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; }
        .status.status-rechazado::before { background-color: #dc3545; }

        /* Estilos para la lista de correos en SweetAlert */
        .swal-email-list-container {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #eee;
            border-radius: 8px;
            margin-top: 10px;
            background-color: #fafafa;
        }
        .swal-email-item {
            background: #ffffff;
            margin: 5px;
            padding: 8px 12px;
            border-radius: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid #e0e0e0;
            font-size: 0.9em;
        }
        .swal-email-item span { color: #555; }
        .swal-email-remove {
            background: none;
            border: none;
            color: #dc3545;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 4px;
            transition: background 0.2s;
        }
        .swal-email-remove:hover { background-color: #ffeaea; }
        .swal-input-group { display: flex; gap: 10px; margin-bottom: 10px; align-items: stretch; }
        .swal-btn-add {
            background-color: #4a6984;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 0 15px;
            cursor: pointer;
            font-size: 1.2em;
        }
        .swal-btn-add:hover { background-color: #3b546a; }
    </style>
</head>
<body>
<header class="header">
    <div class="header-left">
        <div class="logo"><i class="fa-solid fa-shield-halved"></i>ARCA</div>
        <nav class="main-nav">
            <?php if ($modoVista === 'usuario_logueado'): ?>
                <a href="index.php" data-translate-key="nav_dashboard">Dashboard</a>
            <?php endif; ?>
            <a href="Historial.php" data-translate-key="nav_myRequests">Mis Contenciones</a>
            <a href="historial_safe_launch.php">Safe Launch</a>
            <a href="historial_cero_defectos.php" class="active">Cero Defectos</a>
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
        <h1><i class="fa-solid fa-clipboard-check"></i><span data-translate-key="mainTitle"><?php echo htmlspecialchars($tituloPagina); ?></span></h1>

        <?php if ($modoVista === 'invitado'): ?>
            <a href="historial_cero_defectos.php?modo=propio" class="btn-primary" style="background-color: #6c757d;"><i class="fa-solid fa-arrow-left"></i> Volver a mi historial</a>
        <?php elseif ($modoVista === 'usuario_logueado'): ?>
            <a href="nuevo_cero_defectos.php" class="btn-primary"><i class="fa-solid fa-plus-circle"></i> <span data-translate-key="btn_createNewZD">Crear Nuevo Cero Defectos</span></a>
        <?php endif; ?>
    </div>

    <?php if ($modoVista === 'usuario_logueado'): ?>
        <div class="filter-bar">
            <form action="historial_cero_defectos.php" method="GET" class="filter-form">
                <div class="form-group">
                    <label for="folio" data-translate-key="label_searchByFolio">Buscar por Folio</label>
                    <input type="number" name="folio" id="folio" placeholder="Ej: 123" value="<?php echo htmlspecialchars($_GET['folio'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="linea" data-translate-key="label_searchByLine">Buscar por Línea</label>
                    <input type="text" name="linea" id="linea" placeholder="Ej: BMW" value="<?php echo htmlspecialchars($_GET['linea'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="fecha" data-translate-key="label_searchByDate">Buscar por Fecha</label>
                    <input type="date" name="fecha" id="fecha" value="<?php echo htmlspecialchars($_GET['fecha'] ?? ''); ?>">
                </div>
                <button type="submit" class="btn-primary" data-translate-key="btn_filter">Filtrar</button>
                <a href="historial_cero_defectos.php" class="btn-tertiary" data-translate-key="btn_clear">Limpiar</a>
            </form>
        </div>
    <?php endif; ?>

    <div class="results-container">
        <table class="results-table">
            <thead>
            <tr>
                <th><i class="fa-solid fa-hashtag"></i><span data-translate-key="table_folio">Folio</span></th>
                <!-- CAMBIO: Linea en vez de Proyecto -->
                <th><i class="fa-solid fa-industry"></i><span data-translate-key="table_line">Línea</span></th>
                <!-- CAMBIO: OEM añadido -->
                <th><i class="fa-solid fa-car"></i><span data-translate-key="table_oem">OEM</span></th>
                <th><i class="fa-solid fa-user-tie"></i><span data-translate-key="table_client">Cliente</span></th>
                <th><i class="fa-solid fa-calendar-days"></i><span data-translate-key="table_date">Fecha</span></th>
                <th><i class="fa-solid fa-circle-info"></i><span data-translate-key="table_status">Estatus</span></th>
                <th><i class="fa-solid fa-cogs"></i><span data-translate-key="table_actions">Acciones</span></th>
            </tr>
            </thead>
            <tbody>
            <?php if ($solicitudes->num_rows > 0): ?>
                <?php while($row = $solicitudes->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo "ZD-" . str_pad($row['IdCeroDefectos'], 4, '0', STR_PAD_LEFT); ?></td>
                        <td><?php echo htmlspecialchars($row['Linea']); ?></td>
                        <td><?php echo htmlspecialchars($row['NombreOEM']); ?></td>
                        <td><?php echo htmlspecialchars($row['Cliente']); ?></td>
                        <td><?php echo date("d/m/Y H:i", strtotime($row['FechaRegistro'])); ?></td>
                        <td>
                            <?php
                            $estatus_clase = '';
                            switch ($row['IdEstatus']) {
                                case 1: $estatus_clase = 'status-recibido'; break;
                                case 2: $estatus_clase = 'status-recibido'; break;
                                case 3: $estatus_clase = 'status-en-proceso'; break;
                                case 4: $estatus_clase = 'status-cerrado'; break;
                                default: $estatus_clase = 'status-recibido';
                            }
                            ?>
                            <span id="status-span-<?php echo $row['IdCeroDefectos']; ?>" class="status <?php echo $estatus_clase; ?>">
                                <?php echo htmlspecialchars($row['NombreEstatus']); ?>
                            </span>
                        </td>
                        <td class="actions-cell">
                            <!-- JS apuntando al archivo de detalle de ZD -->
                            <button class="btn-icon btn-details" data-id="<?php echo $row['IdCeroDefectos']; ?>" data-translate-key-title="title_viewDetails" title="Ver Detalles"><i class="fa-solid fa-eye"></i></button>

                            <?php if ($modoVista === 'usuario_logueado'): ?>

                                <?php if (isset($row['IdCompartido']) && $row['IdCompartido'] !== null): ?>
                                    <button class="btn-icon btn-email sent" data-translate-key-title="title_emailSent" title="Asignado (Correos Enviados)" disabled>
                                        <i class="fa-solid fa-check"></i>
                                    </button>
                                <?php else: ?>
                                    <button class="btn-icon btn-email" data-id="<?php echo $row['IdCeroDefectos']; ?>" data-translate-key-title="title_sendByEmail" title="Asignar a Múltiples Usuarios">
                                        <i class="fa-solid fa-users"></i>
                                    </button>
                                <?php endif; ?>

                            <?php else: ?>
                                <a href="trabajar_cero_defectos.php?id=<?php echo $row['IdCeroDefectos']; ?>" class="btn-icon" title="Empezar a Trabajar" style="text-decoration: none; background-color: #5c85ad; color: #ffffff;"><i class="fa-solid fa-hammer"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="no-results-cell">
                        <div class="no-results-content">
                            <i class="fa-solid fa-folder-open"></i>
                            <p data-translate-key="noResultsZD">No se encontraron registros de Cero Defectos</p>
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
            <h2><span data-translate-key="modal_title_ZD">Detalles de Cero Defectos</span> <span id="modal-folio"></span></h2>
            <button id="modal-close" class="modal-close-btn">&times;</button>
        </div>
        <div id="modal-body" class="modal-body view-mode">
            <!-- El contenido se cargará por AJAX -->
        </div>
    </div>
</div>

<!-- Archivo JS Específico para ZD (Debes crearlo o duplicar el de Safe Launch) -->
<script src="js/ver_cero_defectos.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- TRADUCCIONES ---
        const translations = {
            'es': {
                'nav_dashboard': 'Dashboard', 'nav_myRequests': 'Mis Contenciones',
                'welcome': 'Bienvenido', 'logout': 'Cerrar Sesión',
                'mainTitle': 'Historial de Cero Defectos', 'btn_createNewZD': 'Crear Nuevo Cero Defectos',
                'label_searchByFolio': 'Buscar por Folio', 'label_searchByLine': 'Buscar por Línea', 'label_searchByDate': 'Buscar por Fecha',
                'btn_filter': 'Filtrar', 'btn_clear': 'Limpiar',
                'table_folio': 'Folio', 'table_line': 'Línea', 'table_oem': 'OEM', 'table_client': 'Cliente',
                'table_date': 'Fecha', 'table_status': 'Estatus', 'table_actions': 'Acciones',
                'title_viewDetails': 'Ver Detalles',
                'title_sendByEmail': 'Asignar a Usuarios', 'title_emailSent': 'Asignado (Correos Enviados)',
                'noResultsZD': 'No se encontraron registros de Cero Defectos',
                'modal_title_ZD': 'Detalles de Cero Defectos',

                'swal_share_title': 'Asignar Cero Defectos',
                'swal_share_intro': 'Ingresa los correos electrónicos de los destinatarios:',
                'swal_share_placeholder': 'ejemplo@grammer.com',
                'swal_share_confirm': 'Enviar Invitaciones',
                'swal_share_cancel': 'Cancelar',
                'swal_share_invalid_email': 'Por favor ingresa un correo válido.',
                'swal_share_duplicate': 'Este correo ya fue agregado.',
                'swal_share_empty': 'Debes agregar al menos un destinatario.',
                'swal_share_no_emails': 'Ningún correo agregado aún',
                'swal_share_sending': 'Enviando...',
                'swal_share_success_title': '¡Enviado!',
                'swal_share_success_text': 'Las invitaciones han sido enviadas correctamente.',
                'swal_share_error_title': 'Error',
                'swal_share_error_text': 'No se pudo completar el envío.'
            },
            'en': {
                'nav_dashboard': 'Dashboard', 'nav_myRequests': 'My Requests',
                'welcome': 'Welcome', 'logout': 'Log Out',
                'mainTitle': 'Zero Defects History', 'btn_createNewZD': 'Create New Zero Defects',
                'label_searchByFolio': 'Search by Folio', 'label_searchByLine': 'Search by Line', 'label_searchByDate': 'Search by Date',
                'btn_filter': 'Filter', 'btn_clear': 'Clear',
                'table_folio': 'Folio', 'table_line': 'Line', 'table_oem': 'OEM', 'table_client': 'Client',
                'table_date': 'Date', 'table_status': 'Status', 'table_actions': 'Actions',
                'title_viewDetails': 'View Details',
                'title_sendByEmail': 'Assign to Users', 'title_emailSent': 'Assigned (Emails Sent)',
                'noResultsZD': 'No Zero Defects records found',
                'modal_title_ZD': 'Zero Defects Details',

                'swal_share_title': 'Assign Zero Defects',
                'swal_share_intro': 'Enter recipient email addresses:',
                'swal_share_placeholder': 'example@grammer.com',
                'swal_share_confirm': 'Send Invitations',
                'swal_share_cancel': 'Cancel',
                'swal_share_invalid_email': 'Please enter a valid email address.',
                'swal_share_duplicate': 'This email has already been added.',
                'swal_share_empty': 'You must add at least one recipient.',
                'swal_share_no_emails': 'No emails added yet',
                'swal_share_sending': 'Sending...',
                'swal_share_success_title': 'Sent!',
                'swal_share_success_text': 'Invitations have been sent successfully.',
                'swal_share_error_title': 'Error',
                'swal_share_error_text': 'Could not complete sending.'
            }
        };

        let currentLang = '<?php echo $idioma_actual; ?>';

        function translatePage(lang) {
            currentLang = lang;
            document.documentElement.lang = lang;
            document.querySelectorAll('[data-translate-key]').forEach(el => {
                const key = el.dataset.translateKey;
                if (translations[lang] && translations[lang][key]) el.innerText = translations[lang][key];
            });
            document.querySelectorAll('[data-translate-key-title]').forEach(el => {
                const key = el.dataset.translateKeyTitle;
                if(translations[lang] && translations[lang][key]) el.title = translations[lang][key];
            });
        }

        document.querySelectorAll('.lang-btn').forEach(button => {
            button.addEventListener('click', function() {
                document.querySelectorAll('.lang-btn').forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                translatePage(this.dataset.lang);
                localStorage.setItem('userLanguage', this.dataset.lang);
            });
        });

        const savedLang = localStorage.getItem('userLanguage') || '<?php echo $idioma_actual; ?>';
        if (savedLang) {
            const btn = document.querySelector(`.lang-btn[data-lang="${savedLang}"]`);
            if (btn) btn.click();
        } else {
            translatePage(currentLang);
        }

        // --- LÓGICA DE CORREO MÚLTIPLE ---
        document.querySelectorAll('.btn-icon.btn-email').forEach(button => {
            button.addEventListener('click', function() {
                const idCeroDefectos = this.dataset.id; // Cambiado ID
                const emailButton = this;
                let correosLista = [];

                const renderEmailList = () => {
                    const container = document.getElementById('email-list-container');
                    if (!container) return;

                    if (correosLista.length === 0) {
                        container.innerHTML = `<div style="color: #999; font-style: italic; padding: 15px; text-align: center;">${translations[currentLang].swal_share_no_emails}</div>`;
                        return;
                    }

                    let html = '<div style="display: flex; flex-direction: column;">';
                    correosLista.forEach((email, index) => {
                        html += `
                            <div class="swal-email-item">
                                <span><i class="fa-solid fa-user-envelope" style="margin-right: 8px; color: #4a6984;"></i> ${email}</span>
                                <button type="button" class="swal-email-remove" data-index="${index}" title="Eliminar">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>`;
                    });
                    html += '</div>';
                    container.innerHTML = html;

                    document.querySelectorAll('.swal-email-remove').forEach(btn => {
                        btn.addEventListener('click', (e) => {
                            const idx = parseInt(e.currentTarget.dataset.index);
                            correosLista.splice(idx, 1);
                            renderEmailList();
                        });
                    });
                };

                Swal.fire({
                    title: translations[currentLang].swal_share_title,
                    html: `
                        <p style="margin-bottom: 15px; color: #555;">${translations[currentLang].swal_share_intro}</p>
                        <div class="swal-input-group">
                            <input type="email" id="swal-input-email" class="swal2-input" placeholder="${translations[currentLang].swal_share_placeholder}" style="margin: 0; flex: 1;">
                            <button type="button" id="btn-add-email" class="swal-btn-add"><i class="fa-solid fa-plus"></i></button>
                        </div>
                        <div id="email-list-container" class="swal-email-list-container"></div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: translations[currentLang].swal_share_confirm,
                    cancelButtonText: translations[currentLang].swal_share_cancel,
                    didOpen: () => {
                        renderEmailList();
                        const input = document.getElementById('swal-input-email');
                        const addBtn = document.getElementById('btn-add-email');

                        const addEmail = () => {
                            const email = input.value.trim();
                            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                            if (!emailRegex.test(email)) { Swal.showValidationMessage(translations[currentLang].swal_share_invalid_email); return; }
                            if (correosLista.includes(email)) { Swal.showValidationMessage(translations[currentLang].swal_share_duplicate); return; }
                            correosLista.push(email);
                            input.value = '';
                            Swal.resetValidationMessage();
                            renderEmailList();
                            input.focus();
                        };

                        addBtn.addEventListener('click', addEmail);
                        input.addEventListener('keypress', (e) => { if (e.key === 'Enter') { e.preventDefault(); addEmail(); } });
                    },
                    preConfirm: () => {
                        if (correosLista.length === 0) { Swal.showValidationMessage(translations[currentLang].swal_share_empty); return false; }
                        return correosLista;
                    }
                }).then((result) => {
                    if (result.isConfirmed && result.value) {
                        const emailsToSend = result.value;

                        Swal.fire({
                            title: translations[currentLang].swal_share_sending,
                            allowOutsideClick: false,
                            didOpen: () => Swal.showLoading()
                        });

                        const formData = new FormData();
                        // Importante: Asegurar que el backend (compartir_cero_defectos.php) espere 'idCeroDefectos'
                        formData.append('idCeroDefectos', idCeroDefectos);
                        formData.append('emails', JSON.stringify(emailsToSend));

                        // CAMBIO: Apuntando a nuevo mailer
                        fetch('https://grammermx.com/Mailer/compartir_cero_defectos.php', {
                            method: 'POST',
                            body: formData
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.status === 'success') {
                                    Swal.fire(translations[currentLang].swal_share_success_title, data.message, 'success');
                                    // Actualizar UI para bloquear botón
                                    emailButton.innerHTML = '<i class="fa-solid fa-check"></i>';
                                    emailButton.classList.add('sent');
                                    emailButton.disabled = true;
                                    emailButton.title = translations[currentLang].title_emailSent;
                                } else {
                                    Swal.fire(translations[currentLang].swal_share_error_title, data.message, 'error');
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                Swal.fire(translations[currentLang].swal_share_error_title, translations[currentLang].swal_share_error_text, 'error');
                            });
                    }
                });
            });
        });

    });
</script>

</body>
</html>