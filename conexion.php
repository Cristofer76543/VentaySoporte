<?php
$servername = "localhost";
$username = "techcomponents_ventas";
$password = "rTNP)LRSIh}M";
$dbname = "techcomponents_ventas";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
?>
