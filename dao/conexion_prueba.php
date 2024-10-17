<?php
$conexion = mysqli_connect($host = "127.0.0.1:3306", $usuario = "u909553968_Nomina",$clave = "RRHHGrammer2024#",$db = "u909553968_CajitaGrammer");
if ($conexion) {
    echo 'conexion exitosa';
} else {
    echo 'conexion fallida';
}
?>