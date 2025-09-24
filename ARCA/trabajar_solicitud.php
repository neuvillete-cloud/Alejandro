<?php
include_once("dao/verificar_sesion.php");
if (!isset($_SESSION['loggedin'])) { header('Location: acceso.php'); exit(); }
$esSuperUsuario = (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] == 1);

// --- LÓGICA PRINCIPAL DE LA PÁGINA ---
// Asumimos que el ID de la solicitud llega por GET
if (!isset($_GET['id'])) { die("Error: ID de solicitud no proporcionado."); }
$idSolicitud = intval($_GET['id']);

include_once("dao/conexionArca.php");
$con = new LocalConector();
$conex = $con->conectar();

// Obtenemos los datos de la solicitud y el estatus de su método
$stmt = $conex->prepare("SELECT s.*, m.EstatusAprobacion FROM Solicitudes s LEFT JOIN Metodos m ON s.IdMetodo = m.IdMetodo WHERE s.IdSolicitud = ?");
$stmt->bind_param("i", $idSolicitud);
$stmt->execute();
$solicitud = $stmt->get_result()->fetch_assoc();

if (!$solicitud) { die("Error: Solicitud no encontrada."); }

// Cargamos catálogos necesarios para el formulario
$catalogo_defectos = $conex->query("SELECT IdDefectoCatalogo, NombreDefecto FROM CatalogoDefectos ORDER BY NombreDefecto ASC");
$razones_tiempo_muerto = $conex->query("SELECT IdTiempoMuerto, Razon FROM CatalogoTiempoMuerto ORDER BY Razon ASC");
$defectos_originales = $conex->query("SELECT d.IdDefecto, cd.NombreDefecto FROM Defectos d JOIN CatalogoDefectos cd ON d.IdDefectoCatalogo = cd.IdDefectoCatalogo WHERE d.IdSolicitud = $idSolicitud");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Trabajar en Solicitud S-<?php echo $idSolicitud; ?> - ARCA</title>
    <link rel="stylesheet" href="css/estilos.css">
    <!-- Tus links a Fonts, FontAwesome, SweetAlert2, etc. -->
</head>
<body>
<header class="header"><!-- Tu header --></header>

<main class="container">
    <div class="form-container">
        <h1><i class="fa-solid fa-hammer"></i> Reporte de Inspección - Folio S-<?php echo str_pad($solicitud['IdSolicitud'], 4, '0', STR_PAD_LEFT); ?></h1>
        <p><strong>No. de Parte:</strong> <?php echo htmlspecialchars($solicitud['NumeroParte']); ?></p>

        <?php
        // --- LÓGICA DE VISTA CONDICIONAL ---
        $mostrarFormularioPrincipal = false;
        if ($solicitud['IdMetodo'] === NULL) {
            // CASO 1: No se ha subido un método de trabajo
            echo "<div class='warning-box'><i class='fa-solid fa-triangle-exclamation'></i> <strong>Acción Requerida:</strong> Para continuar, por favor, sube el método de trabajo para esta solicitud.</div>";
            // Aquí mostraríamos solo el formulario para subir el método
        } elseif ($solicitud['EstatusAprobacion'] === 'Rechazado') {
            // CASO 2: El método fue rechazado
            echo "<div class='error-box'><i class='fa-solid fa-circle-xmark'></i> <strong>Método Rechazado:</strong> El método de trabajo anterior fue rechazado por el administrador. Por favor, sube una versión corregida para continuar.</div>";
            // Aquí mostraríamos el formulario para subir el método de nuevo
        } else {
            // CASO 3: El método está Aprobado o Pendiente, o no se requería
            if ($solicitud['EstatusAprobacion'] === 'Pendiente') {
                echo "<div class='info-box'><i class='fa-solid fa-clock'></i> <strong>Aviso:</strong> El método de trabajo está pendiente de aprobación por el administrador. Puedes continuar con el registro, pero el proceso podría ser detenido si el método es rechazado.</div>";
            }
            $mostrarFormularioPrincipal = true;
        }
        ?>

        <?php if ($mostrarFormularioPrincipal): ?>
            <form id="reporteForm" action="dao/guardar_reporte.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="idSolicitud" value="<?php echo $idSolicitud; ?>">

                <fieldset><legend><i class="fa-solid fa-chart-simple"></i> Resumen de Inspección</legend>
                    <div class="form-row">
                        <div class="form-group"><label>Piezas Inspeccionadas</label><input type="number" name="piezasInspeccionadas" required></div>
                        <div class="form-group"><label>Piezas Rechazadas</label><input type="number" name="piezasRechazadas" required></div>
                        <div class="form-group"><label>Piezas Retrabajadas</label><input type="number" name="piezasRetrabajadas" required></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Nombre del Inspector</label><input type="text" name="nombreInspector" value="<?php echo htmlspecialchars($_SESSION['user_nombre']); ?>" required></div>
                        <div class="form-group"><label>Fecha de Inspección</label><input type="date" name="fechaInspeccion" required></div>
                    </div>
                </fieldset>

                <fieldset><legend><i class="fa-solid fa-clipboard-check"></i> Clasificación de Defectos Originales</legend>
                    <?php while($defecto = $defectos_originales->fetch_assoc()): ?>
                        <div class="form-group">
                            <label><?php echo htmlspecialchars($defecto['NombreDefecto']); ?></label>
                            <input type="text" name="lotes[<?php echo $defecto['IdDefecto']; ?>]" placeholder="Ingresa el Bach/Lote para este defecto...">
                        </div>
                    <?php endwhile; ?>
                </fieldset>

                <fieldset><legend><i class="fa-solid fa-magnifying-glass-plus"></i> Nuevos Defectos Encontrados</legend>
                    <div id="nuevos-defectos-container"></div>
                    <button type="button" id="btn-add-nuevo-defecto" class="btn-secondary"><i class="fa-solid fa-plus"></i> Añadir Nuevo Defecto</button>
                </fieldset>

                <fieldset><legend><i class="fa-solid fa-stopwatch"></i> Tiempos y Comentarios</legend>
                    <div class="form-group"><label>Tiempo Total de Inspección</label><input type="text" name="tiempoInspeccion" placeholder="Ej: 2 horas 30 minutos"></div>
                    <div class="form-group">
                        <label>Tiempo Muerto (Opcional)</label>
                        <div class="select-with-button">
                            <select name="idTiempoMuerto">
                                <option value="">Ninguno</option>
                                <?php while($razon = $razones_tiempo_muerto->fetch_assoc()): ?>
                                    <option value="<?php echo $razon['IdTiempoMuerto']; ?>"><?php echo htmlspecialchars($razon['Razon']); ?></option>
                                <?php endwhile; ?>
                            </select>
                            <?php if ($esSuperUsuario): ?><button type="button" class="btn-add" data-tipo="tiempomuerto">+</button><?php endif; ?>
                        </div>
                    </div>
                    <div class="form-group"><label>Comentarios Adicionales</label><textarea name="comentarios" rows="4"></textarea></div>
                </fieldset>

                <div class="form-actions"><button type="submit" class="btn-primary">Guardar Reporte</button></div>
            </form>
        <?php endif; ?>
    </div>
</main>

<script>
    // Tu JS para manejar la adición dinámica de nuevos defectos
</script>
</body>
</html>
