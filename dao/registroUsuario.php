<?php
include_once ("connection.php");


header('Content-type: application/json');

if(isset($_POST['nomina'], $_POST['nombre'], $_POST['email'], $_POST['password'])) {
    $nomina = $_POST['nomina'];
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $response = array('status' => 'error', 'message' => 'Error faltan datos');
} else {
    $response = array('status' => 'error', 'message' => 'Error faltan datos');
}

echo json_encode($response);
exit;

function RegistrarUsuario($nomina, $nombre, $email, $password)
{
    /*$password = sha1($password);
    $nomina = str_pad($nomina, 10, '0', STR_PAD_LEFT);
    $nombre = str_pad($nombre, 10, '0', STR_PAD_LEFT);
    $email =;*/

    $con = new LocalConector();
    $conex = $con -> conectar();

    $insertUsuario = $conex -> prepare("INSERT INTO usuario (nomina, nombre, email, password)
                                      VALUES (:?, :?, :?, :?)");
    $insertUsuario -> bind_param("ssss", $nomina, $nombre, $email, $password);
    $resultado = $insertUsuario -> execute();

    $conex -> close();

    if ($resultado) {
        $response = array('status' => 'error', 'message' => 'error al registrar usuario');
    } else {
        $response = array('status' => 'success', 'message' => 'usuario registrado exitosamente');
    }
    return $response;
}