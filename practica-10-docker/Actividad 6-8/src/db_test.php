<?php
$conn = new mysqli("db", "stepan", "stepanpass", "stepan_db");
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
echo "Conexión establecida con MySQL!";
?>
