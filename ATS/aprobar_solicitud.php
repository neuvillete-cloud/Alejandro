<?php
session_start();
if (!isset($_SESSION['NumNomina'])) {
    // We save the URL the user wanted to go to and encode it
    $redirectUrl = urlencode($_SERVER['REQUEST_URI']);
    header('Location: login.php?redirect_url=' . $redirectUrl);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Details</title>
    <link rel="stylesheet" href="css/estilosAprobarSolicitud.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php
// We pass the session NumNomina to a JavaScript variable for use in approvals
echo "<script>const currentUserNomina = '" . htmlspecialchars($_SESSION['NumNomina']) . "';</script>";
?>

<header>
    <div class="header-container">
        <div class="logo">
            <img src="imagenes/logo_blanco.png" alt="Grammer Logo" class="logo-img">
            <div class="logo-texto">
                <h1>Grammer</h1>
                <span>Automotive</span>
            </div>
        </div>
        <nav>
            <?php if (isset($_SESSION['Nombre'])): ?>
                <div class="user-menu">
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span><?= htmlspecialchars($_SESSION['Nombre']) ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="dropdown-menu">
                        <a href="perfil.php">Profile</a>
                        <a href="#" id="logout">Log out</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php">Login</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<section class="section-title">
    <h1>Personnel Requests</h1>
    <img src="imagenes/demanda%20(1).png" alt="Decorative image" class="imagen-banner">
</section>

<section class="area-blanca">
    <div class="contenido-blanco">

        <h2 class="titulo-detalle">Request Details</h2>

        <div class="card-solicitud-detalle">
            <div class="card-header">
                <h3 id="puesto-solicitud">Loading...</h3>
                <span class="estatus" id="estatus-solicitud"></span>
            </div>
            <div class="card-body">
                <div class="info-item" id="nombre-solicitante"><strong>Requester:</strong><div class="valor-con-icono"><i class="fas fa-user"></i><span>Loading...</span></div></div>
                <div class="info-item" id="nomina-solicitante"><strong>ID Number:</strong><div class="valor-con-icono"><i class="fas fa-id-card"></i><span>Loading...</span></div></div>
                <div class="info-item" id="area-solicitud"><strong>Area:</strong><span>Loading...</span></div>
                <div class="info-item" id="folio-solicitud"><strong>Folio:</strong><span>Loading...</span></div>
                <div class="info-item" id="tipo-contratacion"><strong>Hiring Type:</strong><span>Loading...</span></div>
                <div class="info-item" id="fecha-solicitud"><strong>Request Date:</strong><span>Loading...</span></div>
                <div class="info-item" id="reemplazo-solicitud" style="display: none;"><strong>Replaces:</strong><div class="valor-con-icono"><i class="fas fa-people-arrows"></i><span></span></div></div>
            </div>
            <div class="card-actions">
                <button id="btn-rechazar" class="btn-accion rechazar">
                    <i class="fas fa-times"></i> Reject
                </button>
                <button id="btn-aceptar" class="btn-accion aceptar">
                    <i class="fas fa-check"></i> Approve
                </button>
            </div>
        </div>
    </div>
</section>

<div id="rejectModal" class="custom-modal">
    <div class="custom-modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-comment-dots"></i> Reason for Rejection</h2>
            <button class="close-reject-modal">&times;</button>
        </div>
        <div class="modal-body">
            <label for="rejectComment">Please provide a clear comment for the requester.</label>
            <textarea id="rejectComment" placeholder="e.g., The vacancy has been put on hold..." rows="5"></textarea>
        </div>
        <div class="modal-footer">
            <button id="confirmRejectBtn" class="btn-accion rechazar">
                <i class="fas fa-times-circle"></i> Confirm Rejection
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
            document.querySelector(".card-solicitud-detalle").innerHTML = `<p style="text-align:center; color:red;">No request folio was provided.</p>`;
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
                if (!response.ok) throw new Error('The server response was not successful.');
                return response.json();
            })
            .then(data => {
                if (data.status === "success" && data.data) {
                    const solicitud = data.data;

                    document.getElementById("puesto-solicitud").textContent = solicitud.Puesto || "N/A";
                    const estatusSpan = document.getElementById("estatus-solicitud");
                    estatusSpan.textContent = solicitud.NombreEstatus || "Unknown";
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

                    if (data.usuario_ya_voto) {
                        const actionsContainer = document.querySelector('.card-solicitud-detalle .card-actions');
                        if (actionsContainer) {
                            actionsContainer.innerHTML = '<p class="mensaje-accion-realizada">You have already registered an action for this request.</p>';
                        }
                    }

                } else {
                    document.querySelector(".card-solicitud-detalle").innerHTML = `<p style="text-align:center; color:red;">Error: ${data.message || 'Request not found.'}</p>`;
                }
            })
            .catch(error => {
                console.error("Error loading the request:", error);
                document.querySelector(".card-solicitud-detalle").innerHTML = `<p style="text-align:center; color:red;">There was an error loading the data. Please try again later.</p>`;
            });
    }

    function handleAceptar(folio) {
        Swal.fire({
            title: "Are you sure?",
            text: `Do you want to APPROVE the request with folio: ${folio}?`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, approve",
            cancelButtonText: "Cancel"
        }).then(async (result) => {
            if (result.isConfirmed) {
                const formData = new URLSearchParams();
                formData.append("folio", folio);
                formData.append("accion", 'aprobar');
                formData.append("num_nomina_aprobador", currentUserNomina);

                try {
                    const response = await fetch('https://grammermx.com/Mailer/mailerAprobacionS.php', {
                        method: 'POST',
                        body: formData
                    });
                    const jsonResponse = await response.json();

                    if (jsonResponse.success) {
                        Swal.fire("Approved", "The request was approved successfully.", "success").then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire("Error", jsonResponse.message || "Could not approve the request.", "error");
                    }
                } catch (error) {
                    Swal.fire("Error", "Could not connect to the server.", "error");
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
            Swal.fire("Attention", "You must enter a comment to reject.", "warning");
            return;
        }
        document.getElementById('rejectModal').classList.remove('show');

        const folio = new URLSearchParams(window.location.search).get("folio");

        Swal.fire({
            title: "Are you sure?",
            text: `Do you want to REJECT the request with folio: ${folio}? This action is final.`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, reject",
            cancelButtonText: "Cancel"
        }).then(async (result) => {
            if (result.isConfirmed) {
                const formData = new URLSearchParams();
                formData.append("folio", folio);
                formData.append("accion", 'rechazar');
                formData.append("comentario", comentario);
                formData.append("num_nomina_aprobador", currentUserNomina);

                try {
                    const response = await fetch('https://grammermx.com/Mailer/mailerAprobacionS.php', {
                        method: 'POST',
                        body: formData
                    });
                    const jsonResponse = await response.json();

                    if (jsonResponse.success) {
                        Swal.fire("Rejected", "The request has been rejected successfully.", "success").then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire("Error", jsonResponse.message || "Could not reject the request.", "error");
                    }
                } catch (error) {
                    Swal.fire("Error", "Could not connect to the server.", "error");
                }
            }
        });
    });

    document.querySelector('.close-reject-modal')?.addEventListener('click', () => {
        document.getElementById('rejectModal').classList.remove('show');
    });

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
