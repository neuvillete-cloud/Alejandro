<?php
// --- INICIO DE LÓGICA DE SESIÓN (RE-INTEGRADA) ---
session_start();

// CASO 1: Un usuario NO logueado llega con un token.
if (isset($_GET['token']) && !isset($_SESSION['loggedin'])) {
    $_SESSION['url_destino_post_login'] = 'historial_safe_launch.php?token=' . urlencode($_GET['token']);
    header('Location: acceso.php');
    exit();
}

// CASO 2: Un usuario YA logueado recibe un nuevo token.
if (isset($_GET['token']) && isset($_SESSION['loggedin'])) {
    $_SESSION['vista_token_actual_sl'] = $_GET['token']; // Usamos una variable de sesión diferente
    header('Location: historial_safe_launch.php'); // Redirigimos para limpiar la URL.
    exit();
}

// CASO 3: El usuario quiere salir del modo invitado.
if (isset($_GET['modo']) && $_GET['modo'] === 'propio') {
    unset($_SESSION['vista_token_actual_sl']);
    header('Location: historial_safe_launch.php');
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


// --- LÓGICA DE VISTA MODIFICADA (CON MODO INVITADO) ---
$modoVista = 'usuario_logueado'; // Por defecto
$tituloPagina = "Historial de Safe Launch";
$params = [];
$types = "";

if (isset($_SESSION['vista_token_actual_sl'])) {
    // --- MODO INVITADO (VISTA POR TOKEN) ---
    $modoVista = 'invitado';
    $tituloPagina = "Safe Launch Compartido";
    $token = $_SESSION['vista_token_actual_sl'];

    $sql_base = "SELECT 
                    sl.IdSafeLaunch, 
                    sl.NombreProyecto, 
                    sl.Cliente, 
                    sl.FechaRegistro, 
                    sl.Estatus,
                    u.Nombre as NombreResponsable,
                    sl.RutaInstruccion,
                    sl.EstatusInstruccion
                 FROM SafeLaunchSolicitudes sl
                 JOIN SafeLaunchSolicitudesCompartidas slsc ON sl.IdSafeLaunch = slsc.IdSafeLaunch
                 JOIN Usuarios u ON sl.IdUsuario = u.IdUsuario
                 WHERE slsc.Token = ?";
    $params[] = $token;
    $types = "s";

} else {
    // --- MODO USUARIO LOGUEADO (VISTA NORMAL) ---
    $sql_base = "SELECT 
                    sl.IdSafeLaunch, 
                    sl.NombreProyecto, 
                    sl.Cliente, 
                    sl.FechaRegistro, 
                    sl.Estatus,
                    u.Nombre as NombreResponsable,
                    sl.RutaInstruccion,
                    sl.EstatusInstruccion,
                    MAX(slsc.IdSLCompartido) as IdCompartido
                 FROM SafeLaunchSolicitudes sl
                 JOIN Usuarios u ON sl.IdUsuario = u.IdUsuario
                 LEFT JOIN SafeLaunchSolicitudesCompartidas slsc ON sl.IdSafeLaunch = slsc.IdSafeLaunch";

    $where_clauses = [];

    // Lógica de Roles: Si no es Admin (Rol 1), filtra por su ID de usuario
    if (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] != 1) {
        $where_clauses[] = "sl.IdUsuario = ?";
        $params[] = $_SESSION['user_id'];
        $types .= "i";
    }

    // Filtro por Folio (IdSafeLaunch)
    if (!empty($_GET['folio'])) {
        $where_clauses[] = "sl.IdSafeLaunch = ?";
        $params[] = $_GET['folio'];
        $types .= "i";
    }

    // Filtro por Fecha
    if (!empty($_GET['fecha'])) {
        $where_clauses[] = "DATE(sl.FechaRegistro) = ?";
        $params[] = $_GET['fecha'];
        $types .= "s";
    }

    if (count($where_clauses) > 0) {
        $sql_base .= " WHERE " . implode(" AND ", $where_clauses);
    }

    // --- CORRECCIÓN AQUÍ ---
    // Se añaden todas las columnas no agregadas (sl.*, u.Nombre) al GROUP BY
    // para que la consulta sea compatible con todos los modos de SQL.
    $sql_base .= " GROUP BY sl.IdSafeLaunch, sl.NombreProyecto, sl.Cliente, sl.FechaRegistro, sl.Estatus, u.Nombre, sl.RutaInstruccion, sl.EstatusInstruccion";
}

$sql_base .= " ORDER BY sl.IdSafeLaunch DESC";
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

    <style>
        /* Estilo para el botón de resubir (ELIMINADO, pero se mantiene el CSS por si acaso) */
        .btn-icon.resubir { background-color: #fff3e0; color: #b75c09; }
        .btn-icon.resubir:hover { background-color: #ff9800; color: var(--color-blanco); }

        /* Estilo para estatus 'Rechazado' (rojo) */
        .status.status-rechazado { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; }
        .status.status-rechazado::before { background-color: #dc3545; }

        /* Estilo para el botón 'Revisar' (azul) */
        .btn-icon.revisar { background-color: #e3f2fd; color: #0d47a1; }
        .btn-icon.revisar:hover { background-color: #2196f3; color: var(--color-blanco); }
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
            <a href="historial_safe_launch.php" class="active">Historial Safe Launch</a>
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
        <h1><i class="fa-solid fa-clipboard-list"></i><span data-translate-key="mainTitle"><?php echo htmlspecialchars($tituloPagina); ?></span></h1>

        <?php if ($modoVista === 'invitado'): ?>
            <a href="historial_safe_launch.php?modo=propio" class="btn-primary" style="background-color: #6c757d;"><i class="fa-solid fa-arrow-left"></i> Volver a mi historial</a>
        <?php elseif ($modoVista === 'usuario_logueado'): ?>
            <a href="safe_launch.php" class="btn-primary"><i class="fa-solid fa-rocket"></i> <span data-translate-key="btn_createNewSL">Crear Nuevo Safe Launch</span></a>
        <?php endif; ?>
    </div>

    <?php if ($modoVista === 'usuario_logueado'): ?>
        <div class="filter-bar">
            <form action="historial_safe_launch.php" method="GET" class="filter-form">
                <div class="form-group">
                    <label for="folio" data-translate-key="label_searchByFolio">Buscar por Folio</label>
                    <input type="number" name="folio" id="folio" placeholder="Ej: 123" value="<?php echo htmlspecialchars($_GET['folio'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="fecha" data-translate-key="label_searchByDate">Buscar por Fecha</label>
                    <input type="date" name="fecha" id="fecha" value="<?php echo htmlspecialchars($_GET['fecha'] ?? ''); ?>">
                </div>
                <button type="submit" class="btn-primary" data-translate-key="btn_filter">Filtrar</button>
                <a href="historial_safe_launch.php" class="btn-tertiary" data-translate-key="btn_clear">Limpiar</a>
            </form>
        </div>
    <?php endif; ?>

    <div class="results-container">
        <table class="results-table">
            <thead>
            <tr>
                <th><i class="fa-solid fa-hashtag"></i><span data-translate-key="table_folio">Folio</span></th>
                <th><i class="fa-solid fa-file-signature"></i><span data-translate-key="table_project">Proyecto</span></th>
                <th><i class="fa-solid fa-user-tie"></i><span data-translate-key="table_client">Cliente</span></th>
                <th><i class="fa-solid fa-calendar-days"></i><span data-translate-key="table_date">Fecha de Registro</span></th>
                <th><i class="fa-solid fa-circle-info"></i><span data-translate-key="table_status">Estatus</span></th>
                <th><i class="fa-solid fa-cogs"></i><span data-translate-key="table_actions">Acciones</span></th>
            </tr>
            </thead>
            <tbody>
            <?php if ($solicitudes->num_rows > 0): ?>
                <?php while($row = $solicitudes->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo "SL-" . str_pad($row['IdSafeLaunch'], 4, '0', STR_PAD_LEFT); ?></td>
                        <td><?php echo htmlspecialchars($row['NombreProyecto']); ?></td>
                        <td><?php echo htmlspecialchars($row['Cliente']); ?></td>
                        <td><?php echo date("d/m/Y H:i", strtotime($row['FechaRegistro'])); ?></td>
                        <td>
                            <?php
                            $estatus_clase = '';
                            switch (strtolower($row['Estatus'])) {
                                case 'pendiente': $estatus_clase = 'status-en-proceso'; break;
                                case 'aprobado': $estatus_clase = 'status-cerrado'; break;
                                case 'rechazado': $estatus_clase = 'status-rechazado'; break;
                                default: $estatus_clase = 'status-recibido';
                            }
                            ?>
                            <span class="status <?php echo $estatus_clase; ?>"><?php echo htmlspecialchars($row['Estatus']); ?></span>
                        </td>
                        <td class="actions-cell">
                            <!-- Botón de Detalles (apunta a un nuevo JS) -->
                            <button class="btn-icon btn-details" data-id="<?php echo $row['IdSafeLaunch']; ?>" data-translate-key-title="title_viewDetails" title="Ver Detalles"><i class="fa-solid fa-eye"></i></button>

                            <?php if ($modoVista === 'usuario_logueado'): ?>

                                <!-- INICIO: Botón de Enviar Correo (RE-INTEGRADO) -->
                                <?php if (isset($row['IdCompartido']) && $row['IdCompartido'] !== null): ?>
                                    <button class="btn-icon btn-email sent" data-translate-key-title="title_emailSent" title="Correo Enviado" disabled>
                                        <i class="fa-solid fa-check"></i>
                                    </button>
                                <?php else: ?>
                                    <button class="btn-icon btn-email" data-id="<?php echo $row['IdSafeLaunch']; ?>" data-translate-key-title="title_sendByEmail" title="Enviar por Correo">
                                        <i class="fa-solid fa-envelope"></i>
                                    </button>
                                <?php endif; ?>
                                <!-- FIN: Botón de Enviar Correo -->

                                <!-- Botón de Revisar (Solo Admin y si está Pendiente) -->
                                <?php if (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] == 1 && $row['Estatus'] === 'Pendiente'): ?>
                                    <a href="revisar_safe_launch.php?id=<?php echo $row['IdSafeLaunch']; ?>" class="btn-icon revisar" data-translate-key-title="title_reviewSL" title="Revisar Safe Launch">
                                        <i class="fa-solid fa-check-to-slot"></i>
                                    </a>
                                <?php endif; ?>

                                <!-- Botón Resubir (ELIMINADO) -->

                            <?php else: // Modo Invitado ?>
                                <!-- Puedes añadir botones específicos para invitados aquí si es necesario -->
                                <a href="trabajar_safe_launch.php?id=<?php echo $row['IdSafeLaunch']; ?>" class="btn-icon" title="Empezar a Trabajar" style="text-decoration: none; background-color: #5c85ad; color: #ffffff;"><i class="fa-solid fa-hammer"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="no-results-cell">
                        <div class="no-results-content">
                            <i class="fa-solid fa-folder-open"></i>
                            <p data-translate-key="noResultsSL">No se encontraron solicitudes de Safe Launch</p>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<!-- Modal de Detalles (no funcional hasta crear ver_safe_launch.js y el DAO) -->
<div id="details-modal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h2><span data-translate-key="modal_title_SL">Detalles del Safe Launch</span> <span id="modal-folio"></span></h2>
            <button id="modal-close" class="modal-close-btn">&times;</button>
        </div>
        <div id="modal-body" class="modal-body view-mode">
            <!-- El contenido se cargará por AJAX -->
        </div>
    </div>
</div>

<!-- Modal para Resubir Instrucción (ELIMINADO) -->


<!-- Apunta a un NUEVO JS que debe ser creado -->
<script src="js/ver_safe_launch.js"></script>

<!-- Script para el modal de resubir (ELIMINADO) -->

<!-- INICIO: NUEVO SCRIPT PARA EL BOTÓN DE ENVIAR CORREO -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- TRADUCCIÓN (Simplificada para esta página) ---
        const translations = {
            'es': {
                'nav_dashboard': 'Dashboard', 'nav_myRequests': 'Mis Contenciones',
                'welcome': 'Bienvenido', 'logout': 'Cerrar Sesión',
                'mainTitle': 'Historial de Safe Launch', 'btn_createNewSL': 'Crear Nuevo Safe Launch',
                'label_searchByFolio': 'Buscar por Folio', 'label_searchByDate': 'Buscar por Fecha',
                'btn_filter': 'Filtrar', 'btn_clear': 'Limpiar',
                'table_folio': 'Folio', 'table_project': 'Proyecto', 'table_client': 'Cliente',
                'table_date': 'Fecha de Registro', 'table_status': 'Estatus', 'table_actions': 'Acciones',
                'title_viewDetails': 'Ver Detalles', 'title_reviewSL': 'Revisar Safe Launch',
                'title_sendByEmail': 'Enviar por Correo', 'title_emailSent': 'Correo Enviado',
                'noResultsSL': 'No se encontraron solicitudes de Safe Launch',
                'modal_title_SL': 'Detalles del Safe Launch',
                'swal_share_title': 'Compartir Safe Launch',
                'swal_share_text': 'Ingrese el correo al que desea enviar el enlace de invitado:',
                'swal_share_placeholder': 'correo@ejemplo.com',
                'swal_share_confirm': 'Enviar',
                'swal_share_cancel': 'Cancelar',
                'swal_share_invalid_email': 'Por favor, ingrese un correo válido.',
                'swal_share_sending': 'Enviando...',
                'swal_share_success_title': '¡Enviado!',
                'swal_share_success_text': 'El enlace de invitado ha sido enviado.',
                'swal_share_error_title': 'Error',
                'swal_share_error_text': 'No se pudo enviar el correo.',

                // --- INICIO DE TRADUCCIONES AÑADIDAS (para el modal) ---
                'loadingData': 'Cargando datos...',
                'errorLoadingData': 'Error al cargar los datos.',
                'section_generalData': 'Datos Generales',
                'label_personInCharge_SL': 'Responsable',
                'label_projectName_SL': 'Proyecto',
                'label_client_SL': 'Cliente',
                'section_instruction': 'Instrucción de Trabajo',
                'section_defects': 'Defectos Registrados',
                'defect': 'Defecto',
                'noDefects': 'No se registraron defectos.'
                // --- FIN DE TRADUCCIONES AÑADIDAS ---
            },
            'en': {
                'nav_dashboard': 'Dashboard', 'nav_myRequests': 'My Contentions',
                'welcome': 'Welcome', 'logout': 'Log Out',
                'mainTitle': 'Safe Launch History', 'btn_createNewSL': 'Create New Safe Launch',
                'label_searchByFolio': 'Search by Folio', 'label_searchByDate': 'Search by Date',
                'btn_filter': 'Filter', 'btn_clear': 'Clear',
                'table_folio': 'Folio', 'table_project': 'Project', 'table_client': 'Client',
                'table_date': 'Registration Date', 'table_status': 'Status', 'table_actions': 'Actions',
                'title_viewDetails': 'View Details', 'title_reviewSL': 'Review Safe Launch',
                'title_sendByEmail': 'Send by Email', 'title_emailSent': 'Email Sent',
                'noResultsSL': 'No Safe Launch requests found',
                'modal_title_SL': 'Safe Launch Details',
                'swal_share_title': 'Share Safe Launch',
                'swal_share_text': 'Enter the email to send the guest link to:',
                'swal_share_placeholder': 'email@example.com',
                'swal_share_confirm': 'Send',
                'swal_share_cancel': 'Cancel',
                'swal_share_invalid_email': 'Please enter a valid email.',
                'swal_share_sending': 'Sending...',
                'swal_share_success_title': 'Sent!',
                'swal_share_success_text': 'The guest link has been sent.',
                'swal_share_error_title': 'Error',
                'swal_share_error_text': 'Could not send the email.',

                // --- INICIO DE TRADUCCIONES AÑADIDAS (para el modal) ---
                'loadingData': 'Loading data...',
                'errorLoadingData': 'Error loading data.',
                'section_generalData': 'General Data',
                'label_personInCharge_SL': 'Person in Charge',
                'label_projectName_SL': 'Project',
                'label_client_SL': 'Client',
                'section_instruction': 'Work Instruction',
                'section_defects': 'Registered Defects',
                'defect': 'Defect',
                'noDefects': 'No defects were registered.'
                // --- FIN DE TRADUCCIONES AÑADIDAS ---
            }
        };

        let currentLang = '<?php echo $idioma_actual; ?>';

        function translatePage(lang) {
            currentLang = lang;
            document.documentElement.lang = lang;
            document.querySelectorAll('[data-translate-key]').forEach(el => {
                const key = el.dataset.translateKey;
                if (translations[lang] && translations[lang][key]) {
                    el.innerText = translations[lang][key];
                }
            });
            document.querySelectorAll('[data-translate-key-title]').forEach(el => {
                const key = el.dataset.translateKeyTitle;
                if(translations[lang] && translations[lang][key]) {
                    el.title = translations[lang][key];
                }
            });
        }

        const langButtons = document.querySelectorAll('.lang-btn');
        langButtons.forEach(button => {
            button.addEventListener('click', function() {
                langButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                const selectedLang = this.dataset.lang;
                translatePage(selectedLang);
                localStorage.setItem('userLanguage', selectedLang);
            });
        });

        const savedLang = localStorage.getItem('userLanguage') || '<?php echo $idioma_actual; ?>';
        if (savedLang) {
            const langBtnToActivate = document.querySelector(`.lang-btn[data-lang="${savedLang}"]`);
            if (langBtnToActivate) langBtnToActivate.click();
        }
        translatePage(currentLang);


        // --- LÓGICA DEL BOTÓN DE ENVIAR CORREO (AÑADIDA) ---
        document.querySelectorAll('.btn-icon.btn-email').forEach(button => {
            button.addEventListener('click', function() {
                const idSafeLaunch = this.dataset.id;
                const emailButton = this;

                Swal.fire({
                    title: translations[currentLang].swal_share_title,
                    text: translations[currentLang].swal_share_text,
                    input: 'email',
                    inputPlaceholder: translations[currentLang].swal_share_placeholder,
                    showCancelButton: true,
                    confirmButtonText: translations[currentLang].swal_share_confirm,
                    cancelButtonText: translations[currentLang].swal_share_cancel,
                    preConfirm: (email) => {
                        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                            Swal.showValidationMessage(translations[currentLang].swal_share_invalid_email);
                            return false;
                        }
                        return email;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: translations[currentLang].swal_share_sending,
                            allowOutsideClick: false,
                            didOpen: () => Swal.showLoading()
                        });

                        const formData = new FormData();
                        formData.append('idSafeLaunch', idSafeLaunch);
                        formData.append('email', result.value);

                        // Este DAO necesita ser creado
                        fetch('dao/compartir_safe_launch.php', {
                            method: 'POST',
                            body: formData
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.status === 'success') {
                                    Swal.fire(translations[currentLang].swal_share_success_title, translations[currentLang].swal_share_success_text, 'success');
                                    // Cambiar el botón a "enviado"
                                    emailButton.innerHTML = '<i class="fa-solid fa-check"></i>';
                                    emailButton.classList.add('sent');
                                    emailButton.disabled = true;
                                    emailButton.title = translations[currentLang].title_emailSent;
                                } else {
                                    Swal.fire(translations[currentLang].swal_share_error_title, data.message || translations[currentLang].swal_share_error_text, 'error');
                                }
                            })
                            .catch(error => {
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



