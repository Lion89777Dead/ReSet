<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'inicio';

$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($mysqli->connect_errno) {
    die("<div style='color:red;'>Error de conexión: " . htmlspecialchars($mysqli->connect_error) . "</div>");
}

$search_nombre = isset($_GET['nombre']) ? trim($_GET['nombre']) : '';
$search_fecha = isset($_GET['fecha']) ? $_GET['fecha'] : '';
$search_tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';

$show_section = $search_tipo ?: 'all';

?>
<!-- El resto del HTML y PHP se mantiene igual, pero ahora nunca tendrás pantalla blanca -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Actividades</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --accent: #6c63ff;
            --accent2: #ff7f50;
            --bg: #f5f6fa;
            --panel: #fff;
            --border: #ececec;
        }
        body {
            font-family: 'Inter', Arial, sans-serif;
            background: var(--bg);
            margin:0;
            min-height:100vh;
        }
        .container {
            max-width: 900px;
            margin: 38px auto;
            background: var(--panel);
            border-radius: 18px;
            box-shadow: 0 4px 24px rgba(108,99,255,0.07);
            padding: 30px 28px 22px 28px;
            animation: fadein 1s;
        }
        @keyframes fadein {
            from {transform:translateY(30px);opacity:0;}
            to {transform:translateY(0);opacity:1;}
        }
        h2 {
            color: var(--accent);
            margin-top:28px;
            font-weight:800;
            font-size:1.5em;
            letter-spacing:0.01em;
        }
        table {
            width:100%;
            border-collapse:collapse;
            margin-top:22px;
            background:#fafaff;
            border-radius:12px;
            overflow:hidden;
            box-shadow:0 2px 10px #ececec;
        }
        th, td {
            padding:13px 10px;
            border-bottom:1px solid #e5e5e5;
            text-align:left;
        }
        th {
            background: linear-gradient(90deg, var(--accent) 60%, var(--accent2) 100%);
            color:#fff;
            font-weight:700;
            font-size:1.1em;
        }
        .filtros {
            margin-bottom:22px;
            padding:14px 18px;
            background: #fafaff;
            border-radius:14px;
            box-shadow:0 2px 8px #eee;
            display:flex;
            flex-wrap:wrap;
            gap:18px;
            align-items:center;
        }
        .filtros label {
            font-weight:600;
            color:#444;
            margin-right:6px;
        }
        .filtros input, .filtros select {
            padding:7px 11px;
            border-radius:8px;
            border:1px solid #ececec;
            outline:none;
            font-size:1em;
            background:#f5f6fa;
            margin-right:8px;
        }
        .filtros button {
            padding:9px 18px;
            border-radius:10px;
            border:none;
            background:linear-gradient(90deg, var(--accent) 60%, var(--accent2) 100%);
            color:#fff;
            font-weight:600;
            cursor:pointer;
            transition: background 0.2s, transform 0.15s;
            font-size:1em;
        }
        .filtros button:hover {
            background:linear-gradient(90deg, var(--accent2) 60%, var(--accent) 100%);
            transform:scale(1.06);
        }
        .empty { text-align:center; padding:40px;}
        img { max-width: 210px; margin: 10px; border: 2px solid #ccc; border-radius: 10px; box-shadow:0 2px 10px #ececec; }
        pre { white-space: pre-wrap; word-wrap: break-word; background:#f8f8ff; padding:12px 11px; border-radius:7px; font-size:1.07em;}
        .result-box {
            margin: 28px 0 14px 0;
            background: #fafaff;
            border-radius: 13px;
            box-shadow: 0 2px 12px #ececec;
            padding: 20px 20px 10px 20px;
            border:1.5px solid #f2f2f2;
            transition: box-shadow 0.2s;
            animation: fadein 0.7s;
        }
        .subtitulo {
            color:var(--accent2);
            font-size:1.07em;
            font-weight:600;
            margin-bottom:9px;
        }
        @media (max-width:900px) {
            .container { padding: 10px 2vw 10px 2vw;}
        }
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
    <div class="container">
        <h2>Historial de Actividades</h2>
        <form method="get" class="filtros">
            <label for="nombre">Nombre:</label>
            <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($search_nombre); ?>" placeholder="Nombre">
            <label for="fecha">Fecha (YYYY-MM-DD):</label>
            <input type="date" id="fecha" name="fecha" value="<?php echo htmlspecialchars($search_fecha); ?>">
            <label for="tipo">Tipo:</label>
            <select name="tipo" id="tipo">
                <option value="">Todos</option>
                <option value="entradas" <?php echo $search_tipo=="entradas"?"selected":""; ?>>Entradas de Niños</option>
                <option value="dibujos" <?php echo $search_tipo=="dibujos"?"selected":""; ?>>Dibujos de Niños</option>
                <option value="notas_padre" <?php echo $search_tipo=="notas_padre"?"selected":""; ?>>Notas de Padres</option>
                <option value="actividades_nino" <?php echo $search_tipo=="actividades_nino"?"selected":""; ?>>Actividades por Niño</option>
            </select>
            <button type="submit">Filtrar</button>
            <a href="notificaciones.php" style="margin-left:12px;color:var(--accent2);font-weight:500;">Quitar filtros</a><a href="Especialista.php" class="btn-regresar">⬅ Regresar</a>
        </form>

        <?php
        // ENTRADAS DE NIÑOS (solo si tipo==entradas o ninguno)
        if ($show_section == 'entradas' || $show_section=='all') {
            echo "<h2>Entradas de Niños</h2><table>
                <thead><tr><th>Niño</th><th>Entradas</th></tr></thead><tbody>";
            $query_ninos = "SELECT ID, Nombre, Nota, Fecha FROM niños WHERE 1";
            $params_n = [];
            $types_n = "";
            if ($search_nombre !== '') { $query_ninos .= " AND Nombre LIKE ?"; $params_n[] = "%$search_nombre%"; $types_n .= "s"; }
            if ($search_fecha !== '') { $query_ninos .= " AND DATE(Fecha) = ?"; $params_n[] = $search_fecha; $types_n .= "s"; }
            
            $stmt_n = $mysqli->prepare($query_ninos);
            if ($stmt_n) {
                if (!empty($params_n)) {
                    $stmt_n->bind_param($types_n, ...$params_n);
                }
                $stmt_n->execute();
                $stmt_n->bind_result($id_nino, $nombre_nino, $nota_nino, $fecha_nota);
                $hay_nota = false;
                while ($stmt_n->fetch()) {
                    $hay_nota = true;
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($nombre_nino) . "<br><span class='subtitulo'>";
                    if (!empty($fecha_nota)) {
                        echo date("Y-m-d", strtotime($fecha_nota));
                    } else {
                        echo "Sin fecha";
                    }
                    echo "</span></td>";
                    echo "<td><pre>" . htmlspecialchars($nota_nino) . "</pre></td>";
                    echo "</tr>";
                }
                $stmt_n->close();
            }
            if (!$hay_nota) {
                echo "<tr><td colspan='2' class='empty'>No hay entradas encontradas.</td></tr>";
            }
            echo "</tbody></table>";
        }

        // DIBUJOS DE NIÑOS (solo si tipo==dibujos o ninguno)
        if ($show_section == 'dibujos' || $show_section=='all') {
            echo "<h2>Dibujos de Niños</h2><table>
                <thead><tr><th>Niño</th><th>Dibujos</th></tr></thead><tbody>";
            $query_dibujos = "SELECT ID, Nombre, Dibujo, Fecha FROM niños WHERE 1";
            $params_d = [];
            $types_d = "";
            if ($search_nombre !== '') { $query_dibujos .= " AND Nombre LIKE ?"; $params_d[] = "%$search_nombre%"; $types_d .= "s"; }
            if ($search_fecha !== '') { $query_dibujos .= " AND DATE(Fecha) = ?"; $params_d[] = $search_fecha; $types_d .= "s"; }

            $stmt_d = $mysqli->prepare($query_dibujos);
            if ($stmt_d) {
                if (!empty($params_d)) {
                    $stmt_d->bind_param($types_d, ...$params_d);
                }
                $stmt_d->execute();
                $stmt_d->bind_result($id_nino, $nombre_nino, $dibujos, $fecha_dibujo);
                $hay_dibujo = false;
                while ($stmt_d->fetch()) {
                    $hay_dibujo = true;
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($nombre_nino) . "<br><span class='subtitulo'>";
                    if (!empty($fecha_dibujo)) {
                        echo date("Y-m-d", strtotime($fecha_dibujo));
                    } else {
                        echo "Sin fecha";
                    }
                    echo "</span></td>";
                    echo "<td>";
                    if ($dibujos) {
                        $rutas = array_filter(explode(",", $dibujos));
                        foreach ($rutas as $img) {
                            $img = trim($img);
                            if ($img && file_exists($img)) {
                                echo "<img src='$img'>";
                            } else if ($img) {
                                echo "<span>Archivo no encontrado: $img</span><br>";
                            }
                        }
                    } else {
                        echo "No hay dibujos guardados.";
                    }
                    echo "</td></tr>";
                }
                $stmt_d->close();
            }
            if (!$hay_dibujo) {
                echo "<tr><td colspan='2' class='empty'>No hay dibujos encontrados.</td></tr>";
            }
            echo "</tbody></table>";
        }

        // NOTAS DE PADRES (solo si tipo==notas_padre o ninguno)
        if ($show_section == 'notas_padre' || $show_section=='all') {
            echo "<h2>Notas de Padres</h2><table>
                <thead><tr><th>Padre</th><th>Notas</th></tr></thead><tbody>";
            $query_padres = "SELECT ID, Nombre, Notas FROM padres WHERE 1";
            $params_p = [];
            $types_p = "";
            if ($search_nombre !== '') { $query_padres .= " AND Nombre LIKE ?"; $params_p[] = "%$search_nombre%"; $types_p .= "s"; }
            
            $stmt_p = $mysqli->prepare($query_padres);
            if ($stmt_p) {
                if (!empty($params_p)) {
                    $stmt_p->bind_param($types_p, ...$params_p);
                }
                $stmt_p->execute();
                $stmt_p->bind_result($id_padre, $nombre_padre, $notas_json);
                $hay_padre = false;
                while ($stmt_p->fetch()) {
                    $hay_padre = true;
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($nombre_padre) . "</td>";
                    echo "<td>";
                    if ($notas_json) {
                        $notas = json_decode($notas_json, true);
                        if (is_array($notas)) {
                            foreach ($notas as $n) {
                                $texto = isset($n["texto"]) ? $n["texto"] : "";
                                $fecha_raw = isset($n["fecha"]) ? $n["fecha"] : 0;
                                $fecha = ($fecha_raw > 0) ? date("Y-m-d", $fecha_raw) : "Fecha no disponible";
                                if ($search_fecha && $fecha != $search_fecha) continue;
                                echo "<div style='margin-bottom:10px;'><strong>Fecha:</strong> $fecha<br><pre>" . htmlspecialchars($texto) . "</pre></div>";
                            }
                        } else {
                            echo "<pre>" . htmlspecialchars($notas_json) . "</pre>";
                        }
                    } else {
                        echo "No hay notas guardadas.";
                    }
                    echo "</td></tr>";
                }
                $stmt_p->close();
            }
            if (!$hay_padre) {
                echo "<tr><td colspan='2' class='empty'>No hay notas encontradas.</td></tr>";
            }
            echo "</tbody></table>";
        }

        // ACTIVIDADES POR NIÑO (notas y dibujos juntos por fecha y nombre)
        if ($show_section == 'actividades_nino') {
            echo "<h2>Actividades de Niño</h2>";
            
            // Notas
            $query_notas = "SELECT Nota, Fecha FROM niños WHERE 1";
            $params_a = [];
            $types_a = "";
            if ($search_nombre !== '') { $query_notas .= " AND Nombre LIKE ?"; $params_a[] = "%$search_nombre%"; $types_a .= "s"; }
            if ($search_fecha !== '') { $query_notas .= " AND DATE(Fecha) = ?"; $params_a[] = $search_fecha; $types_a .= "s"; }
            
            $stmt_a = $mysqli->prepare($query_notas);
            $hay_actividad = false;
            if ($stmt_a) {
                if (!empty($params_a)) {
                    $stmt_a->bind_param($types_a, ...$params_a);
                }
                $stmt_a->execute();
                $stmt_a->bind_result($nota_nino, $fecha_nota);
                while ($stmt_a->fetch()) {
                    $hay_actividad = true;
                    echo "<div class='result-box'><span class='subtitulo'>Nota de " . htmlspecialchars($search_nombre) . " (";
                    if (!empty($fecha_nota)) {
                        echo date("Y-m-d", strtotime($fecha_nota));
                    } else {
                        echo "Sin fecha";
                    }
                    echo ")</span><pre>" . htmlspecialchars($nota_nino) . "</pre></div>";
                }
                $stmt_a->close();
            }
            
            // Dibujos
            $query_dib = "SELECT Dibujo, Fecha FROM niños WHERE 1";
            $params_b = [];
            $types_b = "";
            if ($search_nombre !== '') { $query_dib .= " AND Nombre LIKE ?"; $params_b[] = "%$search_nombre%"; $types_b .= "s"; }
            if ($search_fecha !== '') { $query_dib .= " AND DATE(Fecha) = ?"; $params_b[] = $search_fecha; $types_b .= "s"; }
            
            $stmt_b = $mysqli->prepare($query_dib);
            if ($stmt_b) {
                if (!empty($params_b)) {
                    $stmt_b->bind_param($types_b, ...$params_b);
                }
                $stmt_b->execute();
                $stmt_b->bind_result($dibujos, $fecha_dibujo);
                while ($stmt_b->fetch()) {
                    $hay_actividad = true;
                    echo "<div class='result-box'><span class='subtitulo'>Dibujos de " . htmlspecialchars($search_nombre) . " (";
                    if (!empty($fecha_dibujo)) {
                        echo date("Y-m-d", strtotime($fecha_dibujo));
                    } else {
                        echo "Sin fecha";
                    }
                    echo ")</span>";
                    if ($dibujos) {
                        $rutas = array_filter(explode(",", $dibujos));
                        foreach ($rutas as $img) {
                            $img = trim($img);
                            if ($img && file_exists($img)) {
                                echo "<img src='$img'>";
                            } else if ($img) {
                                echo "<span>Archivo no encontrado: $img</span><br>";
                            }
                        }
                    } else {
                        echo "<div>No hay dibujos guardados.</div>";
                    }
                    echo "</div>";
                }
                $stmt_b->close();
            }
            if (!$hay_actividad) {
                echo "<div class='result-box empty'>No hay actividades encontradas.</div>";
            }
        }
        $mysqli->close();
        ?>
    </div>
    <footer class="footer">
  <p>Creado por: Edwin Abraham Hernández Rivero &nbsp;|&nbsp; Axel Abraham Esquivel Saucedo &nbsp;|&nbsp; Janemir Alexander Aguirre Rios</p>
</footer>

</body>
</html>