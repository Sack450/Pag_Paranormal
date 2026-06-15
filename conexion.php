<?php
$host = "localhost"; 

// Reemplaza esto con el usuario exacto que creaste en el panel de Hostinger
$usuario = "u734304115_Oxlack2026"; 

// Coloca aquí la contraseña exacta que le asignaste a la base de datos al crearla
$contrasena = "Oxlack2026"; 

// Este dato lo saqué directo de tu captura de phpMyAdmin:
$base_datos = "u734304115_Oxlack"; 

$conexion = new mysqli($host, $usuario, $contrasena, $base_datos);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$conexion->set_charset("utf8"); 
?>