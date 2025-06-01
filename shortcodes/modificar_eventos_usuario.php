<?php
add_shortcode('modificar_eventos_usuario', function () {
    ob_start();

    // Verificar sesión activa
    require_once plugin_dir_path(__FILE__) . '/../verificar_login.php';
    verificar_sesion();

    // Obtener alias y conectar
    $alias = $_SESSION['usuario'];
    require_once plugin_dir_path(__FILE__) . '/../conexion.php';
    global $conn;
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    // Obtener ID del usuario desde su alias
    $stmt = $conn->prepare("SELECT id_usuario FROM USUARIOS WHERE alias = ?");
    $stmt->bind_param("s", $alias);
    $stmt->execute();
    $stmt->bind_result($id_usuario);
    $stmt->fetch();
    $stmt->close();

    $mensaje = '';
    $evento = null;

    // Borrar evento
    if (isset($_POST['accion']) && $_POST['accion'] === 'borrar-directo') {
        $id_evento = intval($_POST['id_evento']);
        $stmt = $conn->prepare("DELETE FROM EVENTOS WHERE id_evento = ? AND id_usuario = ?");
        $stmt->bind_param("ii", $id_evento, $id_usuario);
        if ($stmt->execute()) {
            $mensaje = "<p style='color:green;'>🗑 Evento borrado correctamente.</p>";
        }
        $stmt->close();
    }

    // Guardar cambios
    if (isset($_POST['accion']) && $_POST['accion'] === 'guardar') {
        $id_evento = intval($_POST['id_evento']);
        $nombre = trim($_POST['nombre_evento']);
        $tipo = $_POST['tipo_evento'];
        $otro = ($tipo === 'Otros') ? trim($_POST['otro_tipo']) : null;
        $descripcion = trim($_POST['descripcion']);
        $fecha_ini = $_POST['fecha_ini'];
        $fecha_fin = !empty($_POST['fecha_fin']) ? $_POST['fecha_fin'] : null;
        $id_localidad = intval($_POST['id_localidad']);

        // Validaciones
        if (strtotime($fecha_ini) < time()) {
            $mensaje = "<p style='color:red;'>❌ La fecha de inicio no puede ser anterior al momento actual.</p>";
        } elseif ($fecha_fin && strtotime($fecha_fin) < strtotime($fecha_ini)) {
            $mensaje = "<p style='color:red;'>❌ La fecha de fin no puede ser anterior a la fecha de inicio.</p>";
        } else {
            // Comprobar si existe otro evento con mismo tipo, fecha y localidad (excluyendo el actual)
            $stmt = $conn->prepare("SELECT COUNT(*) FROM EVENTOS WHERE tipo_evento = ? AND fecha_ini = ? AND id_localidad = ? AND id_evento != ?");
            $stmt->bind_param("ssii", $tipo, $fecha_ini, $id_localidad, $id_evento);
            $stmt->execute();
            $stmt->bind_result($duplicado);
            $stmt->fetch();
            $stmt->close();

            if ($duplicado > 0) {
                $mensaje = "<p style='color:red;'>❌ Ya existe otro evento con ese tipo, fecha y localidad.</p>";
            } else {
                $stmt = $conn->prepare("UPDATE EVENTOS SET nombre_evento=?, tipo_evento=?, otro_tipo=?, descripcion=?, fecha_ini=?, fecha_fin=?, id_localidad=? WHERE id_evento=? AND id_usuario=?");
                $stmt->bind_param("sssssssii", $nombre, $tipo, $otro, $descripcion, $fecha_ini, $fecha_fin, $id_localidad, $id_evento, $id_usuario);
                if ($stmt->execute()) {
                    $mensaje = "<p style='color:green;'>✔ Cambios guardados correctamente.</p>";
                } else {
                    $mensaje = "<p style='color:red;'>❌ Error al guardar los cambios.</p>";
                }
                $stmt->close();
            }
        }
    }

    // Obtener eventos futuros del usuario
    $eventos = [];
    $res = $conn->query("SELECT id_evento, nombre_evento FROM EVENTOS WHERE id_usuario = $id_usuario AND fecha_ini >= NOW()");
    while ($row = $res->fetch_assoc()) {
        $eventos[] = $row;
    }

    // Obtener detalles de evento seleccionado para modificar
    if (isset($_POST['accion']) && $_POST['accion'] === 'modificar') {
        $id_evento = intval($_POST['id_evento']);
        $stmt = $conn->prepare("SELECT * FROM EVENTOS WHERE id_evento = ? AND id_usuario = ?");
        $stmt->bind_param("ii", $id_evento, $id_usuario);
        $stmt->execute();
        $res = $stmt->get_result();
        $evento = $res->fetch_assoc();
        $stmt->close();
    }

    // Mostrar mensajes
    echo $mensaje;

    // Formulario para seleccionar evento
    echo "<form method='POST'>";
    echo "<p><label>Selecciona uno de tus eventos futuros:</label><br>";
    echo "<select name='id_evento' required>";
    echo "<option value=''>-- Elige --</option>";
    foreach ($eventos as $e) {
        echo "<option value='{$e['id_evento']}'>{$e['nombre_evento']}</option>";
    }
    echo "</select></p>";
    echo "<button type='submit' name='accion' value='modificar'>📝 Modificar</button> ";
    echo "<button type='submit' name='accion' value='borrar-directo' onclick=\"return confirm('¿Borrar este evento?');\">🗑 Borrar</button>";
    echo "</form>";

    // Mostrar formulario de modificación si hay evento cargado
    if ($evento):
?>
<hr>
<form method="POST">
    <input type="hidden" name="id_evento" value="<?php echo $evento['id_evento']; ?>">

    <p><label>Nombre del evento:</label><br>
        <input type="text" name="nombre_evento" value="<?php echo htmlspecialchars($evento['nombre_evento']); ?>" required></p>

    <p><label>Tipo de evento:</label><br>
        <select name="tipo_evento" id="tipo_evento_mod" onchange="toggleOtroTipo()" required>
            <?php
            $tipos = ['Fuegos artificiales', 'Tormenta eléctrica', 'Concierto', 'Obras', 'Otros'];
            foreach ($tipos as $tipo_opcion) {
                $sel = ($evento['tipo_evento'] === $tipo_opcion) ? 'selected' : '';
                echo "<option value=\"$tipo_opcion\" $sel>$tipo_opcion</option>";
            }
            ?>
        </select>
    </p>

    <p id="campo-otro-tipo" style="display:none;">
        <label>Otro tipo:</label><br>
        <input type="text" name="otro_tipo" value="<?php echo htmlspecialchars($evento['otro_tipo']); ?>">
    </p>

    <p><label>Descripción:</label><br>
        <textarea name="descripcion" required><?php echo htmlspecialchars($evento['descripcion']); ?></textarea></p>

    <p><label>Fecha y hora de inicio:</label><br>
        <input type="datetime-local" name="fecha_ini" value="<?php echo date('Y-m-d\TH:i', strtotime($evento['fecha_ini'])); ?>" required></p>

    <p><label>Fecha y hora de fin:</label><br>
        <input type="datetime-local" name="fecha_fin" value="<?php echo $evento['fecha_fin'] ? date('Y-m-d\TH:i', strtotime($evento['fecha_fin'])) : ''; ?>"></p>

    <p><label>Localidad:</label><br>
        <select id="id_localidad" name="id_localidad" required></select></p>

    <p><input type="submit" name="accion" value="guardar"></p>
</form>

<script>
function toggleOtroTipo() {
    const tipo = document.getElementById('tipo_evento_mod').value;
    document.getElementById('campo-otro-tipo').style.display = (tipo === 'Otros') ? 'block' : 'none';
}

window.onload = function() {
    toggleOtroTipo();
    const id_localidad = <?php echo json_encode($evento['id_localidad']); ?>;
    if (id_localidad) {
        fetch('<?php echo home_url(); ?>/wp-content/plugins/external-login/ajax/fill_location.php?id_localidad=' + id_localidad)
        .then(res => res.json())
        .then(data => {
            fillSelect('#id_localidad', data.localidad_list, data.localidad_selected);
        });
    }
};

function fillSelect(id, list, selected) {
    const sel = document.querySelector(id);
    sel.innerHTML = '';
    list.forEach(item => {
        const opt = document.createElement('option');
        opt.value = item.id;
        opt.text = item.nombre;
        if (item.id == selected) opt.selected = true;
        sel.appendChild(opt);
    });
}
</script>
<?php endif;

    echo "<p><a href='" . home_url('/bienvenido') . "'>⬅ Volver a Bienvenido</a></p>";
    return ob_get_clean();
});
