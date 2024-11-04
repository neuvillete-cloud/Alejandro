<?php
header('Content-Type: application/json');
include_once('conexion.php');

if(isset($_POST['nuevaContrasena'], $_POST['Token'], $_POST['NumNomina']) ){
    $token = $_POST['Token'];
    $nomina = $_POST['NumNomina'];
    $estokenValido = validarToken($token, $nomina);

    if($estokenValido === true){
        $newPassword = $_POST['nuevaContrasena'];
        $passwordH = sha1($newPassword);
        $response = actualizarPassword($nomina, $passwordH);
    }else{
        $response = $estokenValido;
    }
}else {
    $response = array('status' => 'error', 'message' => 'Error:Enlace no válido');
}

echo json_encode($response);

function validarToken($token, $nomina){
    $con = new LocalConector();
    $conexion=$con->conectar();

    $datos = mysqli_query($conexion, "SELECT TokenValido
                                            FROM restablecerContrasena
                                            WHERE NumNomina = '$nomina'
                                            AND Token = '$token'
                                            AND Expira > NOW()");
    if ($datos) {
        $resultado = mysqli_fetch_assoc($datos);
        if ($resultado && $resultado['TokenValido'] == 1) {
            $conexion->close();
            return true;
        } else if ($resultado && $resultado['TokenValido'] == 0) {
            $conexion->close();
            return array('status' => 'error', 'message' => 'Error: Token no válido.');
        }else{
            return array('status' => 'error', 'message' => 'Error: No existe solicitud.');
        }
    } else {
        $conexion->close();
        return array('status' => 'error', 'message' => 'Error: No se pudo consultar token.');
    }
}

function actualizarPassword($nomina, $newPassword)
{
    $con = new LocalConector();
    $conex = $con->conectar();

    $conex->begin_transaction();

    // Actualizar la contraseña
    $actPassword = $conex->prepare("UPDATE Usuario SET Contrasena = ? WHERE NumNomina = ?");
    $actPassword->bind_param("ss", $newPassword, $nomina);
    $resActPassword = $actPassword->execute();

    // Cerrar la sentencia preparada
    $actPassword->close();

    // Invalidar el token
    $actToken = $conex->prepare("UPDATE restablecerContrasena SET TokenValido = 0 WHERE NumNomina = ?");
    $actToken->bind_param("s", $nomina);
    $resActToken = $actToken->execute();

    // Cerrar la sentencia preparada
    $actToken->close();

    // Confirmar o hacer rollback de la transacción
    if(!$resActPassword || !$resActToken) {
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
