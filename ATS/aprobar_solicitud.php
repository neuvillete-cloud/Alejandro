<?php
session_start();
if (!isset($_SESSION['NumNomina'])) {
    header('Location: login.php'); // Redirige al login si no está autenticado
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Solicitud</title>
    <link rel="stylesheet" href="css/estilosAprobarSolicitud.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php
// Pasamos el NumNomina de la sesión a una variable de JavaScript para usarla en las aprobaciones
echo "<script>const currentUserNomina = '" . htmlspecialchars($_SESSION['NumNomina']) . "';</script>";
?>

<header>
    <div class="header-container">
        <div class="logo">
            <img src="imagenes/logo_blanco.png" alt="Logo Grammer" class="logo-img">
            <div class="logo-texto">
                <h1>Grammer</h1>
                <span>Automotive</span>
            </div>
        </div>
        <nav>
            <a href="Administrador.php">Inicio</a>
            <a href="SAprobadas.php">S.Aprobadas</a>
            <?php if (isset($_SESSION['Nombre'])): ?>
                <div class="user-menu">
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span><?= htmlspecialchars($_SESSION['Nombre']) ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="dropdown-menu">
                        <a href="perfil.php">Perfil</a>
                        <a href="#" id="logout">Cerrar sesión</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php">Inicio de sesión</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<section class="section-title">
    <h1>Solicitudes de Personal</h1>
    <img src="imagenes/demanda%20(1).png" alt="Imagen decorativa" class="imagen-banner">
</section>

<section class="area-blanca">
    <div class="contenido-blanco">

        <h2 class="titulo-detalle">Detalles de la Solicitud</h2>

        <div class="card-solicitud-detalle">
            <div class="card-header">
                <h3 id="puesto-solicitud">Cargando...</h3>
                <span class="estatus" id="estatus-solicitud"></span>
            </div>
            <div class="card-body">
                <div class="info-item" id="nombre-solicitante"><strong>Solicitante:</strong><div class="valor-con-icono"><i class="fas fa-user"></i><span>Cargando...</span></div></div>
                <div class="info-item" id="nomina-solicitante"><strong>Nómina:</strong><div class="valor-con-icono"><i class="fas fa-id-card"></i><span>Cargando...</span></div></div>
                <div class="info-item" id="area-solicitud"><strong>Área:</strong><span>Cargando...</span></div>
                <div class="info-item" id="folio-solicitud"><strong>Folio:</strong><span>Cargando...</span></div>
                <div class="info-item" id="tipo-contratacion"><strong>Contratación:</strong><span>Cargando...</span></div>
                <div class="info-item" id="fecha-solicitud"><strong>Fecha Solicitud:</strong><span>Cargando...</span></div>
                <div class="info-item" id="reemplazo-solicitud" style="display: none;"><strong>Reemplaza a:</strong><div class="valor-con-icono"><i class="fas fa-people-arrows"></i><span></span></div></div>
            </div>
            <div class="card-actions">
                <button id="btn-rechazar" class="btn-accion rechazar">
                    <i class="fas fa-times"></i> Rechazar
                </button>
                <button id="btn-aceptar" class="btn-accion aceptar">
                    <i class="fas fa-check"></i> Aceptar
                </button>
            </div>
        </div>
    </div>
</section>

<div id="rejectModal" class="custom-modal">
    <div class="custom-modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-comment-dots"></i> Motivo del Rechazo</h2>
            <button class="close-reject-modal">&times;</button>
        </div>
        <div class="modal-body">
            <label for="rejectComment">Por favor, proporciona un comentario claro para el solicitante.</label>
            <textarea id="rejectComment" placeholder="Ej: La vacante se ha puesto en pausa..." rows="5"></textarea>
        </div>
        <div class="modal-footer">
            <button id="confirmRejectBtn" class="btn-accion rechazar">
                <i class="fas fa-times-circle"></i> Confirmar Rechazo
            </button>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        let folioActual = null;
        const urlParams = new URLSearchParams(window.location.search);
        folioActual = urlParams.get("folio");

        if (folioActual) {
            obtenerSolicitud(folioActual);
        } else {
            document.querySelector(".card-solicitud-detalle").innerHTML = `<p style="text-align:center; color:red;">No se proporcionó un folio de solicitud.</p>`;
        }

        document.getElementById('btn-aceptar').addEventListener('click', () => {
            handleAceptar(folioActual);
        });

        document.getElementById('btn-rechazar').addEventListener('click', () => {
            handleRechazar();
        });
    });

    function obtenerSolicitud(folio) {
        fetch(`dao/daoAprobarSolicitud.php?folio=${folio}`)
            .then(response => {
                if (!response.ok) throw new Error('La respuesta del servidor no fue exitosa.');
                return response.json();
            })
            .then(data => {
                if (data.status === "success" && data.data) {
                    const solicitud = data.data;

                    // Rellenamos los datos (esto se queda igual)
                    document.getElementById("puesto-solicitud").textContent = solicitud.Puesto || "N/A";
                    const estatusSpan = document.getElementById("estatus-solicitud");
                    estatusSpan.textContent = solicitud.NombreEstatus || "Desconocido";
                    estatusSpan.className = 'estatus ' + (solicitud.NombreEstatus || '').toLowerCase().replace(/\s+/g, '');

                    document.querySelector("#nombre-solicitante .valor-con-icono span").textContent = solicitud.Nombre || "N/A";
                    document.querySelector("#nomina-solicitante .valor-con-icono span").textContent = solicitud.NumNomina || "N/A";
                    document.querySelector("#area-solicitud span").textContent = solicitud.NombreArea || "N/A";
                    document.querySelector("#folio-solicitud span").textContent = solicitud.FolioSolicitud || "N/A";
                    document.querySelector("#tipo-contratacion span").textContent = solicitud.TipoContratacion || "N/A";
                    document.querySelector("#fecha-solicitud span").textContent = solicitud.FechaSolicitud || "N/A";

                    const reemplazoItem = document.getElementById("reemplazo-solicitud");
                    if (solicitud.NombreReemplazo) {
                        reemplazoItem.style.display = 'block';
                        document.querySelector("#reemplazo-solicitud .valor-con-icono span").textContent = solicitud.NombreReemplazo;
                    } else {
                        reemplazoItem.style.display = 'none';
                    }

                    // --- INICIO: LÓGICA AÑADIDA PARA OCULTAR BOTONES ---
                    // Verificamos la nueva variable que nos envía el PHP
                    if (data.usuario_ya_voto) {
                        // Si es 'true', encontramos el contenedor de los botones...
                        const actionsContainer = document.querySelector('.card-solicitud-detalle .card-actions');

                        // ...y lo reemplazamos con un mensaje de confirmación.
                        if (actionsContainer) {
                            actionsContainer.innerHTML = '<p class="mensaje-accion-realizada">Usted ya ha registrado una acción para esta solicitud.</p>';
                        }
                    }
                    // --- FIN: LÓGICA AÑADIDA ---

                } else {
                    document.querySelector(".card-solicitud-detalle").innerHTML = `<p style="text-align:center; color:red;">Error: ${data.message || 'No se encontró la solicitud.'}</p>`;
                }
            })
            .catch(error => {
                console.error("Error al cargar la solicitud:", error);
                document.querySelector(".card-solicitud-detalle").innerHTML = `<p style="text-align:center; color:red;">Hubo un error al cargar los datos. Por favor, intente más tarde.</p>`;
            });
    }

    function handleAceptar(folio) {
        Swal.fire({
            title: "¿Estás seguro?",
            text: `¿Deseas APROBAR la solicitud con folio: ${folio}?`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Sí, aprobar",
            cancelButtonText: "Cancelar"
        }).then(async (result) => {
            if (result.isConfirmed) {
                const formData = new URLSearchParams();
                formData.append("folio", folio);
                formData.append("accion", 'aprobar');
                formData.append("num_nomina_aprobador", currentUserNomina);

                try {
                    // ⚠️ REEMPLAZA ESTA URL POR LA RUTA A TU SCRIPT PHP
                    const response = await fetch('dao/daoAprobacionS.php', {
                        method: 'POST',
                        body: formData
                    });
                    const jsonResponse = await response.json();

                    if (jsonResponse.success) {
                        Swal.fire("Aprobado", "La solicitud fue aprobada con éxito.", "success").then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire("Error", jsonResponse.message || "No se pudo aprobar la solicitud.", "error");
                    }
                } catch (error) {
                    Swal.fire("Error", "No se pudo conectar con el servidor.", "error");
                }
            }
        });
    }

    function handleRechazar() {
        document.getElementById('rejectComment').value = '';
        document.getElementById('rejectModal').classList.add('show');
    }

    document.getElementById('confirmRejectBtn').addEventListener('click', () => {
        const comentario = document.getElementById('rejectComment').value.trim();
        if (!comentario) {
            Swal.fire("Atención", "Debes ingresar un comentario para rechazar.", "warning");
            return;
        }
        document.getElementById('rejectModal').classList.remove('show');

        const folio = new URLSearchParams(window.location.search).get("folio");

        Swal.fire({
            title: "¿Estás seguro?",
            text: `¿Deseas RECHAZAR la solicitud con folio: ${folio}? Esta acción es final.`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Sí, rechazar",
            cancelButtonText: "Cancelar"
        }).then(async (result) => {
            if (result.isConfirmed) {
                const formData = new URLSearchParams();
                formData.append("folio", folio);
                formData.append("accion", 'rechazar');
                formData.append("comentario", comentario);
                formData.append("num_nomina_aprobador", currentUserNomina);

                try {
                    // ⚠️ REEMPLAZA ESTA URL POR LA RUTA A TU SCRIPT PHP
                    const response = await fetch('dao/daoAprobacionS.php', {
                        method: 'POST',
                        body: formData
                    });
                    const jsonResponse = await response.json();

                    if (jsonResponse.success) {
                        Swal.fire("Rechazado", "La solicitud ha sido rechazada con éxito.", "success").then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire("Error", jsonResponse.message || "No se pudo rechazar la solicitud.", "error");
                    }
                } catch (error) {
                    Swal.fire("Error", "No se pudo conectar con el servidor.", "error");
                }
            }
        });
    });

    document.querySelector('.close-reject-modal')?.addEventListener('click', () => {
        document.getElementById('rejectModal').classList.remove('show');
    });

    // Lógica de Cerrar Sesión
    document.getElementById('logout')?.addEventListener('click', (e) => {
        e.preventDefault();
        fetch('dao/logout.php', { method: 'POST' })
            .then(response => {
                if (response.ok) window.location.href = 'login.php';
            });
    });
</script>
</body>
</html>