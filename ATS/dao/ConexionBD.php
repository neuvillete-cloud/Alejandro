<?php

class LocalConector{

    private $host = "127.0.0.1:3306";
    private $usuario = "u909553968_Ale2";

    private $clave = "Grammer2024a";

    private $db = "u909553968_ATS";

    private $conexion;

    public function conectar(){
        $this->conexion = mysqli_connect($this->host, $this->usuario, $this->clave, $this->db);
        if($this->conexion->connect_error){
            die("Error al conectar con la base de datos".$this->conexion->connect_error);
        }
        return $this->conexion;

    }

}


