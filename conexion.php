<?php
$host = '172.17.0.1';
$user = 'preventuser';
$pass = 'preventpass';
$db   = 'eventosdb';

// Crear conexion
$conn = new mysqli($host, $user, $pass, $db);

// Verificar conexion
if ($conn->connect_error) {
    die("Conexion fallida: " . $conn->connect_error);
}
?>
