<?php
// Conexión a la base de datos
$mysqli = new mysqli("localhost", "root", "", "inicio");
if ($mysqli->connect_errno) {
    die("<div style='color:red;'>Error de conexión: " . htmlspecialchars($mysqli->connect_error) . "</div>");
}

// Búsqueda
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$tipo_usuario = isset($_GET['tipo_usuario']) ? $_GET['tipo_usuario'] : '';

$resultados = [];
$error_busqueda = "";

if ($busqueda !== '' && $tipo_usuario !== '') {
    if ($tipo_usuario == 'padre') {
        // Buscar padres por nombre o id
        if ($stmt = $mysqli->prepare("SELECT ID, Nombre, Genero, Edad, Codigo_Postal, Telefono, Calle, Colonia, Numero_de_Vivienda FROM inicio_padres WHERE Nombre LIKE ? OR ID = ?")) {
            $paramBusqueda = "%$busqueda%";
            $idBusqueda = is_numeric($busqueda) ? (int)$busqueda : 0;
            $stmt->bind_param("si", $paramBusqueda, $idBusqueda);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($padre = $res->fetch_assoc()) {
                // Buscar hijos relacionados
                $stmt2 = $mysqli->prepare("SELECT n.ID, n.Nombre FROM relacion_nino_padre r INNER JOIN inicio_niños n ON r.id_niños = n.ID WHERE r.id_padres = ?");
                $stmt2->bind_param("i", $padre['ID']);
                $stmt2->execute();
                $res2 = $stmt2->get_result();
                $hijos = [];
                while ($hijo = $res2->fetch_assoc()) {
                    $hijos[] = $hijo;
                }
                $padre['hijos'] = $hijos;
                $resultados[] = $padre;
                $stmt2->close();
            }
            $stmt->close();
        } else {
            $error_busqueda = "Error interno al buscar padres.";
        }
    } elseif ($tipo_usuario == 'nino') {
        // Buscar niños por nombre o id
        if ($stmt = $mysqli->prepare("SELECT ID, Nombre, Genero, Edad, Padre, Madre FROM inicio_niños WHERE Nombre LIKE ? OR ID = ?")) {
            $paramBusqueda = "%$busqueda%";
            $idBusqueda = is_numeric($busqueda) ? (int)$busqueda : 0;
            $stmt->bind_param("si", $paramBusqueda, $idBusqueda);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($nino = $res->fetch_assoc()) {
                // Buscar padres relacionados
                $stmt2 = $mysqli->prepare("SELECT p.ID, p.Nombre FROM relacion_nino_padre r INNER JOIN inicio_padres p ON r.id_padres = p.ID WHERE r.id_niños = ?");
                $stmt2->bind_param("i", $nino['ID']);
                $stmt2->execute();
                $res2 = $stmt2->get_result();
                $padres = [];
                while ($padre = $res2->fetch_assoc()) {
                    $padres[] = $padre;
                }
                $nino['padres'] = $padres;
                $resultados[] = $nino;
                $stmt2->close();
            }
            $stmt->close();
        } else {
            $error_busqueda = "Error interno al buscar niños.";
        }
    }
}
$promedio_semanal = null;
$num_partidas = 0;

if (!empty($nino_nombre)) {
    $hoy = new DateTime();
    $dia_semana = $hoy->format('N'); // 1=Lunes, 7=Domingo
    $inicio_semana = clone $hoy;
    $fin_semana = clone $hoy;
    $inicio_semana->modify('-' . ($dia_semana - 1) . ' days')->setTime(0,0,0);
    $fin_semana->modify('+' . (7 - $dia_semana) . ' days')->setTime(23,59,59);

    $sql = "SELECT Puntaje FROM juegos WHERE Nombre = ? AND fecha_asignacion BETWEEN ? AND ?";
    if ($stmt = $mysqli->prepare($sql)) {
        $fecha_inicio = $inicio_semana->format('Y-m-d 00:00:00');
        $fecha_fin = $fin_semana->format('Y-m-d 23:59:59');
        $stmt->bind_param("sss", $nino_nombre, $fecha_inicio, $fecha_fin);
        $stmt->execute();
        $res = $stmt->get_result();
        $total = 0; $num_partidas = 0;
        while ($row = $res->fetch_assoc()) {
            $total += intval($row['Puntaje']);
            $num_partidas++;
        }
        $stmt->close();
        if ($num_partidas > 0) {
            $promedio_semanal = round($total / $num_partidas, 2);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Buscar Usuarios (Especialista)</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f8f8ff; margin:0; padding:30px;}
        .container { background:#fff; max-width: 700px; margin:auto; border-radius: 18px; box-shadow:0 4px 24px #ececec; padding:38px;}
        h1 { color: #6c63ff;}
        label { font-weight: bold;}
        input, select { padding: 7px 13px; font-size: 1em; border-radius: 8px; border:1.5px solid #ccc;}
        button { padding: 8px 28px; background:linear-gradient(90deg,#6c63ff 60%,#ff7f50 100%); color:#fff; border:none; border-radius:11px; font-weight: bold; cursor:pointer;}
        .usuario { margin: 30px 0; padding:18px; background: #fafaff; border-radius: 12px; box-shadow:0 2px 10px #f2f2f2;}
        .usuario h2 { margin: 0 0 10px 0; color:#ff7f50;}
        ul { padding-left: 22px;}
        .campo { color:#333; font-weight:600;}
        .error { color:red; margin:14px 0; }
.btn-regresar {
  display: inline-block;
  background: linear-gradient(135deg, #ff7e5f, #feb47b);
  color: white;
  padding: 10px 20px;
  border-radius: 8px;
  text-decoration: none;
  font-weight: bold;
  font-size: 1em;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.btn-regresar:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
}

.btn-regresar:active {
  transform: translateY(0);
  box-shadow: none;
}
.footer {
  background-color: #000;
  color: #fff;
  text-align: center;
  padding: 15px 10px;
  font-size: 0.95em;
  border-top: 3px solid var(--accent);
  position: fixed;
  bottom: 0;
  left: 0;
  width: 100%;
}
footer p {
  margin: 0;  /* Quita márgenes extra */
}
    </style>
</head>
<body>
<a href="Especialista.php" class="btn-regresar">⬅ Regresar</a>
<div class="container">
    <h1>Buscar Usuarios</h1>
    <form method="get">
        <label for="tipo_usuario">Tipo de usuario:</label>
        <select name="tipo_usuario" id="tipo_usuario" required>
            <option value="">Selecciona</option>
            <option value="padre" <?php if($tipo_usuario=='padre') echo 'selected'; ?>>Padre</option>
            <option value="nino" <?php if($tipo_usuario=='nino') echo 'selected'; ?>>Niño</option>
        </select>
        <label for="busqueda">Nombre o ID:</label>
        <input type="text" name="busqueda" id="busqueda" value="<?php echo htmlspecialchars($busqueda); ?>" required>
        <button type="submit">Buscar</button>
        
    </form>
    <?php if (!empty($error_busqueda)): ?>
        <div class="error"><?php echo htmlspecialchars($error_busqueda); ?></div>
    <?php endif; ?>
    <?php if ($busqueda !== '' && $tipo_usuario !== ''): ?>
        <?php if (empty($resultados)): ?>
            <div class="usuario">No se encontraron resultados.</div>
        <?php else: ?>
            <?php foreach ($resultados as $user): ?>
                <div class="usuario">
                    <?php if ($tipo_usuario == 'padre'): ?>
                        <h2>Padre: <?php echo htmlspecialchars($user['Nombre']); ?> (ID: <?php echo $user['ID']; ?>)</h2>
                        <div><span class="campo">Género:</span> <?php echo htmlspecialchars($user['Genero']); ?></div>
                        <div><span class="campo">Edad:</span> <?php echo htmlspecialchars($user['Edad']); ?></div>
                        <div><span class="campo">Teléfono:</span> <?php echo htmlspecialchars($user['Telefono']); ?></div>
                        <div><span class="campo">Dirección:</span> <?php echo htmlspecialchars($user['Calle'] . ' ' . $user['Colonia'] . ' ' . $user['Numero_de_Vivienda']); ?></div>
                        <div style="margin-top:13px;"><span class="campo">Hijos registrados:</span>
                            <?php if (empty($user['hijos'])): ?>
                                <div style="color:#aaa;">Sin hijos registrados.</div>
                            <?php else: ?>
                                <ul>
                                    <?php foreach ($user['hijos'] as $hijo): ?>
                                        <li>Nombre: <?php echo htmlspecialchars($hijo['Nombre']); ?> (ID: <?php echo $hijo['ID']; ?>)</li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    <?php elseif ($tipo_usuario == 'nino'): ?>
                        <h2>Niño: <?php echo htmlspecialchars($user['Nombre']); ?> (ID: <?php echo $user['ID']; ?>)</h2>
                        <div><span class="campo">Género:</span> <?php echo htmlspecialchars($user['Genero']); ?></div>
                        <div><span class="campo">Edad:</span> <?php echo htmlspecialchars($user['Edad']); ?></div>
                        <div><span class="campo">Padre:</span> <?php echo htmlspecialchars($user['Padre']); ?></div>
                        <div><span class="campo">Madre:</span> <?php echo htmlspecialchars($user['Madre']); ?></div>
                        <div style="margin-top:13px;"><span class="campo">Padres registrados:</span>
                            <?php if (empty($user['padres'])): ?>
                                <div style="color:#aaa;">Sin padres registrados.</div>
                            <?php else: ?>
                                <ul>
                                    <?php foreach ($user['padres'] as $padre): ?>
                                        <li>Nombre: <?php echo htmlspecialchars($padre['Nombre']); ?> (ID: <?php echo $padre['ID']; ?>)</li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    <?php
                        // --- PROMEDIO SEMANAL DE PUNTAJE PARA ESTE NIÑO ---
                        $promedio_semanal = null;
                        $num_partidas = 0;
                        $nino_nombre = $user['Nombre'];
                        // Calcula solo si hay nombre
                        if (!empty($nino_nombre)) {
                            $hoy = new DateTime();
                            $dia_semana = $hoy->format('N');
                            $inicio_semana = clone $hoy;
                            $fin_semana = clone $hoy;
                            $inicio_semana->modify('-' . ($dia_semana - 1) . ' days')->setTime(0,0,0);
                            $fin_semana->modify('+' . (7 - $dia_semana) . ' days')->setTime(23,59,59);

                            $sql = "SELECT Puntaje FROM juegos WHERE Nombre = ? AND fecha_asignacion BETWEEN ? AND ?";
                            if ($stmt = $mysqli->prepare($sql)) {
                                $fecha_inicio = $inicio_semana->format('Y-m-d 00:00:00');
                                $fecha_fin = $fin_semana->format('Y-m-d 23:59:59');
                                $stmt->bind_param("sss", $nino_nombre, $fecha_inicio, $fecha_fin);
                                $stmt->execute();
                                $res = $stmt->get_result();
                                $total = 0; $num_partidas = 0;
                                while ($row = $res->fetch_assoc()) {
                                    $total += intval($row['Puntaje']);
                                    $num_partidas++;
                                }
                                $stmt->close();
                                if ($num_partidas > 0) {
                                    $promedio_semanal = round($total / $num_partidas, 2);
                                }
                            }
                        }
                        ?>
                        <?php if ($promedio_semanal !== null): ?>
                            <div class="section" style="background:#eafaf1; margin-top:18px;">
                                <h3 style="color:#27ae60;">Promedio semanal de puntaje en minijuegos</h3>
                                <div style="font-size:1.3em;font-weight:bold">
                                    <?php echo htmlspecialchars($promedio_semanal); ?>
                                </div>
                                <div style="font-size:0.95em;color:#4d4d4d;">
                                    (Calculado de <?php echo htmlspecialchars($num_partidas); ?> partida<?php echo $num_partidas==1?'':'s'; ?> esta semana)
                                </div>
                            </div>
                        <?php endif; ?>
                        <!-- --- FIN PROMEDIO --- -->
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>
<footer class="footer">
  <p>Creado por: Edwin Abraham Hernández Rivero &nbsp;|&nbsp; Axel Abraham Esquivel Saucedo &nbsp;|&nbsp; Janemir Alexander Aguirre Rios</p>
</footer>

</body>
</html>