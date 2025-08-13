<?php
session_start();
$mysqli = new mysqli("localhost", "root", "", "inicio");
if ($mysqli->connect_errno) {
    die("Error de conexión: " . htmlspecialchars($mysqli->connect_error));
}

// Asegúrate de que la sesión tenga el nombre de usuario
$usuario_nino = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : null;

if ($_SERVER["REQUEST_METHOD"] == "POST" && $usuario_nino) {
    $juego = $_POST['juego'] ?? '';
    $puntaje = isset($_POST['puntaje']) && is_numeric($_POST['puntaje']) ? intval($_POST['puntaje']) : 0;
    $tiempo = isset($_POST['tiempo']) && is_numeric($_POST['tiempo']) ? intval($_POST['tiempo']) : 0;
    $dificultad = isset($_POST['dificultad']) && is_numeric($_POST['dificultad']) ? intval($_POST['dificultad']) : 1;

    if ($juego == '' || $puntaje < 0 || $tiempo < 0 || $dificultad < 1 || empty($usuario_nino)) {
        echo "Error: Datos inválidos.";
        exit;
    }

    if ($stmt = $mysqli->prepare("INSERT INTO juegos (Nombre, Puntaje, Tiempo, Dificultad, Juego, fecha_asignacion) VALUES (?, ?, ?, ?, ?, NOW())")) {
        $stmt->bind_param("siiis", $usuario_nino, $puntaje, $tiempo, $dificultad, $juego);
        $stmt->execute();
        if ($stmt->error) {
            echo "Error al guardar puntaje: " . htmlspecialchars($stmt->error);
        } else {
            echo "OK";
        }
        $stmt->close();
    } else {
        echo "Error interno al guardar puntaje.";
    }
} else {
    echo "Error: sesión no válida o datos incompletos";
}
?>