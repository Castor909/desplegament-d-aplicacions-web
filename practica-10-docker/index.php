<?php
/*
Requiere crear la tabla en base de datos:
CREATE TABLE mensajes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL,
    mensaje TEXT NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

*/

session_start();

// Configuración de la conexión a la base de datos
$host = "localhost:8080";    // A DEFINIR POR EL DESARROLLADOR
$user = "nombre"; // A DEFINIR POR EL DESARROLLADOR
$pass = "password";     // A DEFINIR POR EL DESARROLLADOR
$db   = "nombre_db"; // A DEFINIR POR EL DESARROLLADOR

// Conexión a MySQL
$conn = new mysqli($host, $user, $pass, $db);

// Verifica la conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// crea tabla mensajes
$sql_create ="CREATE TABLE IF NOT EXISTS mensajes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL,
    mensaje TEXT NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$result = $conn->query($sql_create);

// Lógica para insertar mensajes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!empty($_SESSION['usuario'])){
        $usuario = $_SESSION['usuario'];
    }else{
        $usuario = $_POST['usuario'];
        $_SESSION['usuario'] = $usuario;
    }  

    $mensaje = $_POST['mensaje'];

    // Evita entradas vacías
    if (!empty($usuario) && !empty($mensaje)) {
        $sql = "INSERT INTO mensajes (usuario, mensaje) VALUES ('$usuario', '$mensaje')";
        if (!$conn->query($sql)) {
            echo "Error al enviar el mensaje: " . $conn->error;
        }
    }
}

// Recupera los mensajes de la base de datos
$sql = "SELECT * FROM mensajes ORDER BY fecha DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Chat Básico con PHP y MySQL empleando contenedores Docker</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 20px; }
        .chat-box { background: #fff; padding: 15px; border-radius: 8px; max-width: 600px; margin: 0 auto; }
        .mensaje { margin-bottom: 10px; border-bottom: 1px solid #ccc; padding-bottom: 5px; }
        .mensaje strong { color: #333; }
        form { margin-top: 20px; }
        input, textarea { width: 98%; margin-bottom: 10px;  } /* padding: 8px; */
        button { background-color: #28a745; color: white; border: none; padding: 10px; cursor: pointer; }
        button:hover { background-color: #218838; }
    </style>
</head>
<body>
    <div class="chat-box">
        <h2>Bienvenido a mi sala de Chat - desplegada con Docker</h2>
        <!-- Lista de mensajes -->
        <?php if ($result->num_rows > 0){ 
            while ($row = $result->fetch_assoc()){ ?>
                <div class="mensaje">
                    <strong><?php echo htmlspecialchars($row['usuario']); ?>:</strong>
                    <p><?php echo htmlspecialchars($row['mensaje']); ?></p>
                    <small><?php echo $row['fecha']; ?></small>
                </div>
            <?php } 
        }else{ 
            echo "<p>Aún no hay mensajes. ¡Escribe el primero!</p>";
        } ?>

        <!-- Formulario para enviar mensajes -->
        <form method="POST" action="index.php">
            <?php 
                if (!empty($_SESSION['usuario'])){
                    echo '<input type="text" name="usuario" value="'.$_SESSION['usuario'].'" required disabled>';
                }else{
                    echo '<input type="text" name="usuario" placeholder="Tu nombre" required>';
                }
            ?>
            <textarea name="mensaje" placeholder="Escribe tu mensaje aquí..." rows="3" required></textarea>
            <button type="submit">Enviar</button>
            <!-- Boton para forzar reload, mediante javascript! realmente no forma parte del FORM -->
            <button onclick="window.location.reload();" style="float: right;">Recargar</button>
        </form>
    </div>
</body>
</html>

<?php
// Cierra la conexión a la base de datos
$conn->close();
?>
