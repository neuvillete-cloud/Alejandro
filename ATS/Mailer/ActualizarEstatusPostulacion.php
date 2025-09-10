<?php
header('Content-Type: application/json');
session_start();
date_default_timezone_set('America/Mexico_City');

include_once("ConexionBD.php");
// Se necesita PHPMailer para enviar las notificaciones
require 'Phpmailer/Exception.php';
require 'Phpmailer/PHPMailer.php';
require 'Phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'], $_POST['status'])) {
    echo json_encode(["success" => false, "message" => "Datos inválidos o método incorrecto"]);
    exit;
}

$idPostulacion = intval($_POST['id']);
$nuevoEstado = intval($_POST['status']);

$con = new LocalConector();
$conex = $con->conectar();
$conex->begin_transaction();

try {
    // Lógica para actualizar el estado
    if ($nuevoEstado === 9) { // Seleccionado para contratación
        $fechaSeleccion = date("Y-m-d H:i:s");
        $sql = "UPDATE Postulaciones 
                SET IdEstatus = ?, 
                    fechaSeleccion = IF(fechaSeleccion = '0000-00-00 00:00:00', ?, fechaSeleccion) 
                WHERE IdPostulacion = ?";
        $stmt = $conex->prepare($sql);
        $stmt->bind_param("isi", $nuevoEstado, $fechaSeleccion, $idPostulacion);
    } else {
        $sql = "UPDATE Postulaciones SET IdEstatus = ? WHERE IdPostulacion = ?";
        $stmt = $conex->prepare($sql);
        $stmt->bind_param("ii", $nuevoEstado, $idPostulacion);
    }

    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        // --- INICIO DE LA LÓGICA DE NOTIFICACIONES ---

        // Notificación para el ADMINISTRADOR cuando un candidato es SELECCIONADO (IdEstatus = 9)
        if ($nuevoEstado === 9) {
            $infoSql = "SELECT CONCAT(c.Nombre, ' ', c.Apellidos) AS NombreCandidato, v.TituloVacante
                        FROM Postulaciones p
                        JOIN Candidatos c ON p.IdCandidato = c.IdCandidato
                        JOIN Vacantes v ON p.IdVacante = v.IdVacante
                        WHERE p.IdPostulacion = ?";
            $infoStmt = $conex->prepare($infoSql);
            $infoStmt->bind_param("i", $idPostulacion);
            $infoStmt->execute();
            $infoResult = $infoStmt->get_result()->fetch_assoc();

            if ($infoResult) {
                $adminSql = "SELECT Correo FROM Usuario WHERE IdRol = 1";
                $adminResult = $conex->query($adminSql);
                $correosAdmins = [];
                while ($row = $adminResult->fetch_assoc()) { $correosAdmins[] = $row['Correo']; }
                if (!empty($correosAdmins)) {
                    enviarCorreoAdminSeleccionado($correosAdmins, $infoResult['NombreCandidato'], $infoResult['TituloVacante']);
                }
            }
        }
        // Notificación para el CANDIDATO cuando es RECHAZADO (IdEstatus = 3)
        elseif ($nuevoEstado === 3) {
            $infoSql = "SELECT CONCAT(c.Nombre, ' ', c.Apellidos) AS NombreCandidato, c.Correo, v.TituloVacante
                        FROM Postulaciones p
                        JOIN Candidatos c ON p.IdCandidato = c.IdCandidato
                        JOIN Vacantes v ON p.IdVacante = v.IdVacante
                        WHERE p.IdPostulacion = ?";
            $infoStmt = $conex->prepare($infoSql);
            $infoStmt->bind_param("i", $idPostulacion);
            $infoStmt->execute();
            $infoResult = $infoStmt->get_result()->fetch_assoc();

            if ($infoResult && !empty($infoResult['Correo'])) {
                enviarCorreoRechazoCandidato($infoResult['Correo'], $infoResult['NombreCandidato'], $infoResult['TituloVacante']);
            }
        }
        // --- FIN DE LA LÓGICA DE NOTIFICACIONES ---

        $conex->commit();
        echo json_encode(["success" => true, "message" => "Estado actualizado correctamente"]);

    } else {
        $conex->rollback();
        echo json_encode(["success" => false, "message" => "No se encontró la postulación o ya tenía ese estado"]);
    }

    $stmt->close();
    $conex->close();

} catch (Exception $e) {
    if ($conex) $conex->rollback();
    echo json_encode(["success" => false, "message" => "Ocurrió un error: " . $e->getMessage()]);
}


/**
 * Envía correo al Admin cuando un candidato es SELECCIONADO.
 */
function enviarCorreoAdminSeleccionado($correosAdmins, $nombreCandidato, $nombreVacante) {
    $asunto = "Acción Requerida: Nuevo Candidato Seleccionado para Contratación";
    $url_sitio = "https://grammermx.com/AleTest/ATS";
    $logoUrl = $url_sitio . '/imagenes/logo_blanco.png';
    $linkCandidatos = $url_sitio . "/candidatoSeleccionado.php";

    $cuerpoMensaje = "
        <h2 style='color: #005195; font-family: Arial, sans-serif; font-size: 24px;'>Nuevo Candidato Seleccionado</h2>
        <p style='font-family: Arial, sans-serif; font-size: 16px; color: #333;'>Hola Administrador,</p>
        <p style='font-family: Arial, sans-serif; font-size: 16px; color: #333;'>Te informamos que un nuevo candidato ha sido marcado como 'Seleccionado para Contratación' y está listo para el siguiente paso en el proceso.</p>
        <p style='font-family: Arial, sans-serif; font-size: 16px; color: #333; background-color: #f8f9fa; padding: 15px; border-left: 4px solid #0d6efd;'>
            <strong>Candidato:</strong> " . htmlspecialchars($nombreCandidato) . "<br>
            <strong>Vacante:</strong> " . htmlspecialchars($nombreVacante) . "
        </p>
        <p style='font-family: Arial, sans-serif; font-size: 16px; color: #333;'>Por favor, visita el panel de candidatos seleccionados para continuar con el proceso de envío de oferta y contratación.</p>
        
        <table width='100%' border='0' cellspacing='0' cellpadding='0' style='margin-top: 25px; margin-bottom: 25px;'>
          <tr><td><table border='0' cellspacing='0' cellpadding='0' align='center'><tr>
          <td align='center' style='border-radius: 8px; background-color: #198754;'>
            <a href='" . $linkCandidatos . "' target='_blank' style='font-size: 16px; font-family: Arial, sans-serif; color: #ffffff; text-decoration: none; border-radius: 8px; padding: 14px 28px; border: 1px solid #198754; display: inline-block; font-weight: bold;'>
              Ir a Candidatos Seleccionados
            </a>
          </td>
          </tr></table></td></tr>
        </table>
    ";

    $contenidoHTML = "
    <!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'></head>
    <body style='margin: 0; padding: 0; background-color: #f4f7fc; font-family: Arial, sans-serif;'>
        <table border='0' cellpadding='0' cellspacing='0' width='100%'><tr_><td style='padding: 20px 0;'>
            <table align='center' border='0' cellpadding='0' cellspacing='0' width='600' style='border-collapse: collapse; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);'>
                <tr><td align='center' style='background-color: #005195; padding: 30px; border-top-left-radius: 12px; border-top-right-radius: 12px;'><img src='$logoUrl' alt='Logo Grammer' width='150' style='display: block;'></td></tr>
                <tr><td style='padding: 40px 30px;'>
                    $cuerpoMensaje
                    <p style='font-family: Arial, sans-serif; font-size: 16px; color: #333;'>Saludos,<br><strong>Sistema ATS Grammer</strong></p>
                </td></tr>
                <tr><td align='center' style='background-color: #f8f9fa; padding: 20px; font-size: 12px; color: #6c757d; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;'>
                    <p style='margin: 0;'>&copy; " . date('Y') . " Grammer Automotive de México. Este es un correo automatizado.</p>
                </td></tr>
            </table>
        </td></tr></table>
    </body></html>";

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'sistema_ats@grammermx.com';
        $mail->Password = 'SATSGrammer2024.';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;
        $mail->setFrom('sistema_ats@grammermx.com', 'Sistema ATS Grammer');

        foreach($correosAdmins as $correo) {
            if(!empty($correo)) $mail->addAddress($correo);
        }

        $mail->addBCC('sistema_ats@grammermx.com');
        $mail->addBCC('extern.alejandro.torres@grammer.com');
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $asunto;
        $mail->Body = $contenidoHTML;
        $mail->send();
    } catch (Exception $e) {
        error_log("Error al enviar correo de candidato seleccionado al admin: " . $mail->ErrorInfo);
    }
}


/**
 * Envía correo al Candidato cuando es RECHAZADO.
 */
function enviarCorreoRechazoCandidato($correoCandidato, $nombreCandidato, $nombreVacante) {
    $asunto = "Actualización sobre tu postulación en Grammer Automotive";
    $url_sitio = "https://grammermx.com/AleTest/ATS";
    $logoUrl = $url_sitio . '/imagenes/logo_blanco.png';

    $cuerpoMensaje = "
        <h2 style='color: #005195; font-family: Arial, sans-serif; font-size: 24px;'>Actualización de tu Proceso de Selección</h2>
        <p style='font-family: Arial, sans-serif; font-size: 16px; color: #333;'>Estimado(a) " . htmlspecialchars($nombreCandidato) . ",</p>
        <p style='font-family: Arial, sans-serif; font-size: 16px; color: #333;'>Te escribimos en seguimiento a tu postulación para la vacante de <strong>" . htmlspecialchars($nombreVacante) . "</strong> en Grammer Automotive.</p>
        <p style='font-family: Arial, sans-serif; font-size: 16px; color: #333;'>Agradecemos sinceramente tu tiempo e interés en formar parte de nuestro equipo. Hemos revisado cuidadosamente tu perfil, y aunque tus habilidades y experiencia son muy valiosas, en esta ocasión hemos decidido continuar el proceso con otros candidatos cuyos perfiles se ajustan más a los requerimientos actuales del puesto.</p>
        <p style='font-family: Arial, sans-serif; font-size: 16px; color: #333;'>Te animamos a que sigas atento(a) a nuestras futuras oportunidades. Guardaremos tu información en nuestra base de datos para considerarte en futuras vacantes que puedan ser de tu interés.</p>
        <p style='font-family: Arial, sans-serif; font-size: 16px; color: #333;'>Te deseamos el mayor de los éxitos en tu búsqueda profesional.</p>
    ";

    $contenidoHTML = "
    <!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'></head>
    <body style='margin: 0; padding: 0; background-color: #f4f7fc; font-family: Arial, sans-serif;'>
        <table border='0' cellpadding='0' cellspacing='0' width='100%'><tr_><td style='padding: 20px 0;'>
            <table align='center' border='0' cellpadding='0' cellspacing='0' width='600' style='border-collapse: collapse; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);'>
                <tr><td align='center' style='background-color: #005195; padding: 30px; border-top-left-radius: 12px; border-top-right-radius: 12px;'><img src='$logoUrl' alt='Logo Grammer' width='150' style='display: block;'></td></tr>
                <tr><td style='padding: 40px 30px;'>
                    $cuerpoMensaje
                    <p style='font-family: Arial, sans-serif; font-size: 16px; color: #333;'>Atentamente,<br><strong>Equipo de Adquisición de Talento<br>Grammer Automotive</strong></p>
                </td></tr>
                <tr><td align='center' style='background-color: #f8f9fa; padding: 20px; font-size: 12px; color: #6c757d; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;'>
                    <p style='margin: 0;'>&copy; " . date('Y') . " Grammer Automotive de México. Este es un correo automatizado.</p>
                </td></tr>
            </table>
        </td></tr></table>
    </body></html>";

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'sistema_ats@grammermx.com';
        $mail->Password = 'SATSGrammer2024.';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;
        $mail->setFrom('sistema_ats@grammermx.com', 'Reclutamiento Grammer');
        $mail->addAddress($correoCandidato, $nombreCandidato);
        $mail->addBCC('sistema_ats@grammermx.com');
        $mail->addBCC('extern.alejandro.torres@grammer.com');
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $asunto;
        $mail->Body = $contenidoHTML;
        $mail->send();
    } catch (Exception $e) {
        // No se lanza una excepción para no detener el flujo principal, solo se registra el error.
        error_log("Error al enviar correo de rechazo al candidato: " . $mail->ErrorInfo);
    }
}
?>


