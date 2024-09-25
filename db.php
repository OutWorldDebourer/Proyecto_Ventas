<?php
// db.php

$servername = "localhost";
$username = "ventas_user";
$password = "ventas_password"; // La contraseña que asignaste
$dbname = "ventas_db";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    // Implementar un manejo de errores más sofisticado
    error_log("Conexión fallida: " . $conn->connect_error);
    // Mostrar un mensaje amigable al usuario
    die("Error en la conexión. Por favor, inténtalo de nuevo más tarde.");
}

// Establecer el conjunto de caracteres a UTF-8 para evitar problemas de codificación
$conn->set_charset("utf8");
?>
