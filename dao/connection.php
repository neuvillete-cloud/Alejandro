<?php

class LocalConector{

    private $host = "127";
    private $usuario = "u909553968_Ale";

    private $clave = "Grammer2024a";

    private $db = "u909553968_testAle";

    private $conexion;

    public function conectar(){
        $this->conexion = mysqli_connect($this->host, $this->usuario, $this->clave, $this->db);

    }

}


