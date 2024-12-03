<?php
// Incluir conexión y PHPMailer
include_once('conexion.php');
require 'Phpmailer/Exception.php';
require 'Phpmailer/PHPMailer.php';
require 'Phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Configuración del correo del administrador
$correoAdministrador = 'extern.alejandro.torres@grammer.com'; // Cambia esto por el correo real
$nombreAdministrador = 'Edmundo'; // Nombre del administrador

function enviarCorreoAdministrador($correo, $asunto, $mensaje) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com'; // Configuración SMTP
        $mail->SMTPAuth = true;
        $mail->Username = 'tickets_enchulamelanave@grammermx.com';
        $mail->Password = 'ECHGrammer2024.';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;

        $mail->setFrom('tickets_enchulamelanave@grammermx.com', 'Sistema de Reportes');
        $mail->addAddress($correo);
        $mail->addAddress('hadbet.altamirano@grammer.com');

        // Configuración de UTF-8 para evitar problemas con acentos
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body = $mensaje;

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Conexión a la base de datos
$con = new LocalConector();
$conex = $con->conectar();

// Consulta para reportes no finalizados en 7 días
$sql = "
SELECT 
    IdReporte, 
    NumNomina, 
    DescripcionProblema, 
    DescripcionLugar, 
    FechaRegistro
FROM Reportes
WHERE IdEstatus != 3
  AND FechaFinalizado IS NULL
  AND FechaRegistro <= NOW() - INTERVAL 7 DAY;
";

$result = $conex->query($sql);

if ($result->num_rows > 0) {
    while ($reporte = $result->fetch_assoc()) {
        $idReporte = $reporte['IdReporte'];
        $numNomina = $reporte['NumNomina'];
        $descripcionProblema = $reporte['DescripcionProblema'];
        $descripcionLugar = $reporte['DescripcionLugar'];
        $fechaRegistro = $reporte['FechaRegistro'];

        // Mensaje del correo
        $mensaje = "
        <html>
        <head>
            <title>Reporte No Finalizado</title>
            <meta charset='UTF-8'>
        </head>
        <body>
            <p>Hola $nombreAdministrador,</p>
            <p>El siguiente reporte no se ha finalizado en 7 días:</p>
            <ul>
                <li><strong>ID del Reporte:</strong> $idReporte</li>
                <li><strong>Número de Nómina:</strong> $numNomina</li>
                <li><strong>Problema:</strong> $descripcionProblema</li>
                <li><strong>Lugar:</strong> $descripcionLugar</li>
                <li><strong>Fecha de Registro:</strong> $fechaRegistro</li>
            </ul>
            <p>Por favor, revisa este reporte lo antes posible.</p>
        </body>
        </html>";

        $asunto = "Reporte No Finalizado en Más de 7 Días";

        // Enviar correo
        $envioExitoso = enviarCorreoAdministrador($correoAdministrador, $asunto, $mensaje);

        if ($envioExitoso) {
            echo "Correo enviado al administrador para el reporte #$idReporte.<br>";
        } else {
            echo "Error al enviar correo para el reporte #$idReporte.<br>";
        }
    }
} else {
    echo "No hay reportes pendientes de más de 7 días.<br>";
}

$conex->close();
?>


