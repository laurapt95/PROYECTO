<?php
add_shortcode('votar_evento', function () {
    ob_start();

    // Verificar sesión iniciada
    require_once plugin_dir_path(__FILE__) . '/../verificar_login.php';
    verificar_sesion();

    // Incluir conexión a la base de datos centralizada
    require_once plugin_dir_path(__FILE__) . '/../conexion.php';
    global $conn;
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    // Obtener alias del usuario desde sesión y buscar su ID
    $alias = $_SESSION['usuario'];
    $stmt = $conn->prepare("SELECT id_usuario FROM USUARIOS WHERE alias = ?");
    $stmt->bind_param("s", $alias);
    $stmt->execute();
    $stmt->bind_result($id_usuario);
    $stmt->fetch();
    $stmt->close();

    $mensaje = '';

    // Procesar envío del formulario
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_evento'], $_POST['estrellas'])) {
        $id_evento = intval($_POST['id_evento']);
        $estrellas = intval($_POST['estrellas']);
        $comentario = isset($_POST['comentario']) ? trim($_POST['comentario']) : null;

        if ($comentario === '') $comentario = null;

        // Validar voto entre 1 y 5 estrellas
        if ($estrellas >= 1 && $estrellas <= 5) {
            // Insertar o actualizar voto
            $stmt = $conn->prepare("
                INSERT INTO VOTACIONES_EVENTOS (id_usuario, id_evento, estrellas, comentario, fecha_voto)
                VALUES (?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                    estrellas = VALUES(estrellas),
                    comentario = VALUES(comentario),
                    fecha_voto = NOW()
            ");
            $stmt->bind_param("iiis", $id_usuario, $id_evento, $estrellas, $comentario);
            if ($stmt->execute()) {
                $mensaje = "<p style='color:green;'>✔ Tu votación se ha registrado o actualizado correctamente.</p>";
            }
            $stmt->close();
        }
    }

    // Obtener eventos pasados que no sean del mismo usuario
    $stmt = $conn->prepare("
        SELECT E.id_evento, E.nombre_evento, E.tipo_evento, E.descripcion, E.fecha_ini, E.fecha_fin,
               V.estrellas AS mi_voto, V.comentario
        FROM EVENTOS E
        LEFT JOIN VOTACIONES_EVENTOS V ON V.id_evento = E.id_evento AND V.id_usuario = ?
        WHERE E.fecha_ini < NOW() AND E.id_usuario != ?
        ORDER BY E.fecha_ini DESC
    ");
    $stmt->bind_param("ii", $id_usuario, $id_usuario);
    $stmt->execute();
    $res = $stmt->get_result();

    // Mostrar mensaje y enlace de regreso
    echo "<a href='" . home_url('/bienvenido') . "'>⬅ Volver a Bienvenido</a>";
    echo $mensaje;

    // Mostrar eventos para votar
    if ($res->num_rows === 0) {
        echo "<p>No hay eventos pasados para valorar.</p>";
    } else {
        echo "<form method='POST'>";
        while ($row = $res->fetch_assoc()) {
            $id_evento     = $row['id_evento'];
            $nombre        = htmlspecialchars($row['nombre_evento']);
            $tipo          = htmlspecialchars($row['tipo_evento']);
            $descripcion   = htmlspecialchars($row['descripcion']);
            $fecha_ini     = htmlspecialchars($row['fecha_ini']);
            $fecha_fin     = htmlspecialchars($row['fecha_fin'] ?? '—');
            $mi_voto       = intval($row['mi_voto'] ?? 0);
            $mi_comentario = htmlspecialchars($row['comentario'] ?? '');

            echo "<hr>";
            echo "<div style='line-height: 1.4; margin-bottom: 8px;'>";
            echo "<strong>Evento:</strong> $nombre<br>";
            echo "<strong>Tipo:</strong> $tipo<br>";
            echo "<strong>Descripción:</strong> $descripcion<br>";
            echo "<strong>Fecha de inicio:</strong> $fecha_ini<br>";
            echo "<strong>Fecha de fin:</strong> $fecha_fin<br>";
            echo "</div>";

            echo "<p><strong>Tu voto:</strong></p>";
            for ($i = 1; $i <= 5; $i++) {
                $checked = ($i == $mi_voto) ? 'checked' : '';
                echo "<label><input type='radio' name='estrellas' value='$i' $checked> " . str_repeat('⭐', $i) . "</label><br>";
            }

            echo "<p><label>Comentario (opcional):</label><br>";
            echo "<textarea name='comentario' maxlength='200' rows='3' cols='50' placeholder='Escribe tu opinión...'>$mi_comentario</textarea></p>";

            echo "<input type='hidden' name='id_evento' value='$id_evento'>";
            echo "<p><input type='submit' value='Enviar voto'></p>";
        }
        echo "</form>";
    }

    $stmt->close();
    $conn->close();

    echo "<p><a href='" . home_url('/bienvenido') . "'>⬅ Volver a Bienvenido</a></p>";

    return ob_get_clean();
});
