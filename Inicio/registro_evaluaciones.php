<?php
// Conexión a la base de datos
$mysqli = new mysqli("localhost", "root", "", "inicio");
if ($mysqli->connect_errno) {
    die("<div style='color:red;'>Error de conexión: " . htmlspecialchars($mysqli->connect_error) . "</div>");
}

$mensaje = "";
$error = "";

// Obtener lista de niños para el select
$ninos = [];
$res = $mysqli->query("SELECT ID, Nombre FROM inicio_niños ORDER BY Nombre ASC");
while ($row = $res->fetch_assoc()) {
    $ninos[] = $row;
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $id_nino = intval($_POST['id_nino'] ?? 0);
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    $puntaje = intval($_POST['puntaje'] ?? 0);
    $conclusion = trim($_POST['conclusion'] ?? "");
    $observaciones = trim($_POST['observaciones'] ?? "");

    if ($id_nino && $fecha && $puntaje) {
        $stmt = $mysqli->prepare("INSERT INTO evaluaciones_psicologicas (id_nino, fecha, puntaje, conclusion, observaciones) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isiss", $id_nino, $fecha, $puntaje, $conclusion, $observaciones);
        if ($stmt->execute()) {
            $mensaje = "¡Evaluación registrada correctamente!";
        } else {
            $error = "Error al registrar la evaluación.";
        }
        $stmt->close();
    } else {
        $error = "Completa todos los campos obligatorios.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Evaluación Psicológica</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f8f8ff; margin:0; padding:30px;}
        .container { background:#fff; max-width: 480px; margin:auto; border-radius: 18px; box-shadow:0 4px 24px #ececec; padding:38px;}
        label { font-weight: bold;}
        input, select, textarea { width: 100%; padding: 8px 11px; font-size: 1em; border-radius: 8px; border:1.5px solid #ccc; margin-bottom: 14px;}
        button { padding: 8px 28px; background:linear-gradient(90deg,#6c63ff 60%,#ff7f50 100%); color:#fff; border:none; border-radius:11px; font-weight: bold; cursor:pointer;}
        .msg { margin: 14px 0; font-weight:bold;}
        .msg.ok { color: #27ae60;}
        .msg.err { color: #e74c3c;}
    </style>
</head>
<body>
<div class="container">
    <h2>Registrar Evaluación Psicológica</h2>
    <?php if ($mensaje): ?>
        <div class="msg ok"><?php echo htmlspecialchars($mensaje); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="msg err"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="post">
        <label for="id_nino">Niño:</label>
        <select name="id_nino" id="id_nino" required>
            <option value="">Selecciona un niño</option>
            <?php foreach ($ninos as $nino): ?>
                <option value="<?php echo $nino['ID']; ?>"><?php echo htmlspecialchars($nino['Nombre']); ?></option>
            <?php endforeach; ?>
        </select>
        <label for="fecha">Fecha:</label>
        <input type="date" name="fecha" id="fecha" value="<?php echo date('Y-m-d'); ?>" required>
        <label for="puntaje">Puntaje:</label>
        <input type="number" name="puntaje" id="puntaje" min="0" max="100" required>
        <label for="conclusion">Conclusión breve:</label>
        <input type="text" name="conclusion" id="conclusion" maxlength="255">
        <label for="observaciones">Observaciones:</label>
        <textarea name="observaciones" id="observaciones" rows="4"></textarea>
        <button type="submit">Registrar Evaluación</button>
    </form>
</div>
</body>
</html>