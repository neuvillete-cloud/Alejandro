<?php
header('Content-Type: application/json');
include_once('conexion.php');

if (isset($_POST['nuevaContrasena'], $_POST['Token'], $_POST['NumNomina'])) {
    $Token = $_POST['Token'];
    $NumNomina = $_POST['NumNomina'];
    $TokenValido = validarToken($Token, $NumNomina);

    if ($TokenValido === true) {
        $nuevaContrasena = $_POST['nuevaContrasena'];
        $Contrasena = sha1($nuevaContrasena); // Encriptamos la nueva contraseña
        $response = actualizarPassword($NumNomina, $Contrasena);
    } else {
        // Token no válido, error en validación
        $response = $TokenValido;
    }
} else {
    // Error en el enlace o en los parámetros recibidos
    $response = array('status' => 'error', 'message' => 'Error: Enlace no válido');
}

echo json_encode($response);

// Validar el token en la base de datos
function validarToken($Token, $NumNomina) {
    $con = new LocalConector();
    $conexion = $con->conectar();

    // Verificamos la existencia y validez del token
    $datos = mysqli_query($conexion, "SELECT TokenValido
                                      FROM restablecerContrasena
                                      WHERE NumNomina = '$NumNomina'
                                      AND Token = '$Token'
                                      AND Expira > NOW()");
    if ($datos) {
        $resultado = mysqli_fetch_assoc($datos);
        $conexion->close();

        if ($resultado) {
            if ($resultado['TokenValido'] == 1) {
                return true; // Token válido
            } else {
                return array('status' => 'error', 'message' => 'Error: Token no válido.');
            }
        } else {
            return array('status' => 'error', 'message' => 'Error: No existe solicitud.');
        }
    } else {
        $conexion->close();
        return array('status' => 'error', 'message' => 'Error: No se pudo consultar el token.');
    }
}

// Función para actualizar la contraseña
function actualizarPassword($NumNomina, $nuevaContrasena) {
    $con = new LocalConector();
    $conex = $con->conectar();

    $conex->begin_transaction();

    // Actualizar la contraseña
    $actPassword = $conex->prepare("UPDATE Usuario SET Contrasena = ? WHERE NumNomina = ?");
    $actPassword->bind_param("ss", $nuevaContrasena, $NumNomina);
    $resActPassword = $actPassword->execute();
    $actPassword->close();

    // Invalidar el token
    $actToken = $conex->prepare("UPDATE restablecerContrasena SET TokenValido = 0 WHERE NumNomina = ?");
    $actToken->bind_param("s", $NumNomina); // Aquí debe ir $NumNomina en lugar de $nomina
    $resActToken = $actToken->execute();
    $actToken->close();

    // Confirmar o revertir la transacción según el resultado
    if (!$resActPassword || !$resActToken) {
        $conex->rollback();
        $conex->close();
        return array('status' => 'error', 'message' => 'Error: No se pudo actualizar la información.');
    } else {
        $conex->commit();
        $conex->close();
        return array('status' => 'success', 'message' => 'Contraseña actualizada.');
    }
}
?>
