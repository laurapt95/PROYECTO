<?php
add_shortcode('formulario_registrar_evento', function () {
    ob_start();
    $mensaje = '';

    // Verificar que hay sesión iniciada
    require_once plugin_dir_path(__FILE__) . '/../verificar_login.php';
    verificar_sesion();

    // Obtener alias del usuario desde la sesión
    $alias = $_SESSION['usuario'];

    // Conexión a la base de datos reutilizando el archivo global
    require_once plugin_dir_path(__FILE__) . '/../conexion.php';
    global $conn;
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    // Obtener ID del usuario
    $stmt = $conn->prepare("SELECT id_usuario FROM USUARIOS WHERE alias = ?");
    $stmt->bind_param("s", $alias);
    $stmt->execute();
    $stmt->bind_result($id_usuario);
    $stmt->fetch();
    $stmt->close();

    // Obtener lista de países
    $paises = [];
    $res = $conn->query("SELECT id_pais, nombre FROM PAISES ORDER BY nombre");
    while ($row = $res->fetch_assoc()) {
        $paises[] = $row;
    }

    // Procesar formulario si fue enviado
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nombre_evento = trim($_POST['nombre_evento']);
        $tipo_evento = $_POST['tipo_evento'];
        $otro_evento = ($tipo_evento === 'Otros' && !empty($_POST['otro_tipo'])) ? trim($_POST['otro_tipo']) : null;
        $descripcion = trim($_POST['descripcion']);
        $fecha_ini = $_POST['fecha_ini'];
        $fecha_fin = !empty($_POST['fecha_fin']) ? $_POST['fecha_fin'] : null;
        $id_localidad = intval($_POST['id_localidad']);

        $fecha_actual = date('Y-m-d H:i:s'); // Obtener la fecha y hora actual

        // Validaciones
        if (empty($nombre_evento) || empty($descripcion) || empty($fecha_ini) || empty($tipo_evento) || empty($id_localidad)) {
            $mensaje = "<p style='color:red;'>Todos los campos obligatorios deben estar rellenados.</p>";
        } elseif ($fecha_ini < $fecha_actual) {
            $mensaje = "<p style='color:red;'>La fecha de inicio no puede ser anterior a la actual.</p>";
        } else {
            // Comprobar si ya existe un evento con mismo tipo, fecha y localidad
            $stmt = $conn->prepare("SELECT COUNT(*) FROM EVENTOS WHERE tipo_evento = ? AND fecha_ini = ? AND id_localidad = ?");
            $stmt->bind_param("ssi", $tipo_evento, $fecha_ini, $id_localidad);
            $stmt->execute();
            $stmt->bind_result($evento_repetido);
            $stmt->fetch();
            $stmt->close();

            if ($evento_repetido > 0) {
                $mensaje = "<p style='color:red;'>Ya existe un evento registrado con ese tipo, fecha y localidad.</p>";
            } else {
                // Insertar el evento si todo es válido
                $stmt = $conn->prepare("INSERT INTO EVENTOS (nombre_evento, tipo_evento, otro_tipo, descripcion, fecha_ini, fecha_fin, id_localidad, id_usuario)
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssssii", $nombre_evento, $tipo_evento, $otro_evento, $descripcion, $fecha_ini, $fecha_fin, $id_localidad, $id_usuario);
                if ($stmt->execute()) {
                    $mensaje = "<p style='color:green;'>Evento registrado correctamente.</p>";
                } else {
                    $mensaje = "<p style='color:red;'>Error al registrar el evento.</p>";
                }
                $stmt->close();
            }
        }
    }

    $conn->close();

    // Mostrar mensaje y enlace de retorno
    $mensaje .= "<p><a href='" . home_url('/bienvenido') . "' style='color:#0073aa; text-decoration:underline;'>⬅ Volver a la página de bienvenida</a></p>";
    echo $mensaje;
?>

<!-- Cargar mapa con Leaflet -->
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<!-- Formulario HTML -->
<form method="POST">
    <p><label>Nombre del evento:</label><br><input type="text" name="nombre_evento" required></p>

    <p><label>Tipo de evento:</label><br>
        <select name="tipo_evento" id="tipo_evento" onchange="mostrarOtroEvento()" required>
            <option value="Fuegos artificiales">Fuegos artificiales</option>
            <option value="Tormenta eléctrica">Tormenta eléctrica</option>
            <option value="Concierto">Concierto</option>
            <option value="Obras">Obras</option>
            <option value="Otros">Otros</option>
        </select>
    </p>

    <p id="campo-otro-evento" style="display: none;">
        <label>Otro tipo de evento:</label><br>
        <input type="text" name="otro_tipo">
    </p>

    <p><label>Descripción del evento:</label><br><input type="text" name="descripcion" required></p>
    <p><label>Fecha y hora de inicio:</label><br><input type="datetime-local" name="fecha_ini" required></p>
    <p><label>Fecha y hora de fin (opcional):</label><br><input type="datetime-local" name="fecha_fin"></p>

    <!-- Campos de ubicación -->
    <p><label>País:</label><br>
        <select name="id_pais" id="id_pais" required>
            <option value="">-- Selecciona país --</option>
            <?php foreach ($paises as $pais): ?>
                <option value="<?php echo $pais['id_pais']; ?>"><?php echo htmlspecialchars($pais['nombre']); ?></option>
            <?php endforeach; ?>
        </select>
    </p>

    <p><label>CCAA:</label><br><select name="id_ccaa" id="id_ccaa" required></select></p>
    <p><label>Provincia:</label><br><select name="id_provincia" id="id_provincia" required></select></p>
    <p><label>Municipio:</label><br><select name="id_municipio" id="id_municipio" required></select></p>
    <p><label>Localidad:</label><br><select name="id_localidad" id="id_localidad" required></select></p>

    <p><label>Ubicación en el mapa:</label></p>
    <div id="map" style="height: 400px;"></div>

    <p><input type="submit" value="Registrar evento"></p>
</form>

<!-- Scripts de ubicación y mapa -->
<script>
function mostrarOtroEvento() {
    const tipo = document.getElementById('tipo_evento').value;
    document.getElementById('campo-otro-evento').style.display = (tipo === 'Otros') ? 'block' : 'none';
}

let map, marker;

window.onload = function () {
    map = L.map('map').setView([43.361, -5.849], 8);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(map);
};

jQuery(document).ready(function($) {
    $('#id_pais').on('change', function() {
        let id = $(this).val();
        $('#id_ccaa, #id_provincia, #id_municipio, #id_localidad').html('');
        if (id) {
            $.get('<?php echo home_url(); ?>/wp-content/plugins/external-login/ajax/get_ccaa.php', { id_pais: id }, function(data) {
                $('#id_ccaa').append('<option value="">-- Selecciona CCAA --</option>');
                data.forEach(ccaa => {
                    $('#id_ccaa').append(`<option value="${ccaa.id_ccaa}">${ccaa.nombre}</option>`);
                });
            });
        }
    });

    $('#id_ccaa').on('change', function() {
        let id = $(this).val();
        $('#id_provincia, #id_municipio, #id_localidad').html('');
        if (id) {
            $.get('<?php echo home_url(); ?>/wp-content/plugins/external-login/ajax/get_provincias.php', { id_ccaa: id }, function(data) {
                $('#id_provincia').append('<option value="">-- Selecciona provincia --</option>');
                data.forEach(p => {
                    $('#id_provincia').append(`<option value="${p.id_provincia}">${p.nombre}</option>`);
                });
            });
        }
    });

    $('#id_provincia').on('change', function() {
        let id = $(this).val();
        $('#id_municipio, #id_localidad').html('');
        if (id) {
            $.get('<?php echo home_url(); ?>/wp-content/plugins/external-login/ajax/get_municipios.php', { id_provincia: id }, function(data) {
                $('#id_municipio').append('<option value="">-- Selecciona municipio --</option>');
                data.forEach(m => {
                    $('#id_municipio').append(`<option value="${m.id_municipio}">${m.nombre}</option>`);
                });
            });
        }
    });

    $('#id_municipio').on('change', function() {
        let id = $(this).val();
        $('#id_localidad').html('');
        if (id) {
            $.get('<?php echo home_url(); ?>/wp-content/plugins/external-login/ajax/get_localidades.php', { id_municipio: id }, function(data) {
                $('#id_localidad').append('<option value="">-- Selecciona localidad --</option>');
                data.forEach(l => {
                    $('#id_localidad').append(`<option value="${l.id_localidad}">${l.nombre}</option>`);
                });
            });
        }
    });

    $('#id_localidad').on('change', function () {
        let id = $(this).val();
        if (id) {
            $.get('<?php echo home_url(); ?>/wp-content/plugins/external-login/ajax/get_localidades.php', {
                id_municipio: $('#id_municipio').val()
            }, function (data) {
                let loc = data.find(l => l.id_localidad == id);
                if (loc && loc.latitud && loc.longitud) {
                    let latlng = [parseFloat(loc.latitud), parseFloat(loc.longitud)];
                    map.setView(latlng, 13);
                    if (marker) {
                        marker.setLatLng(latlng);
                    } else {
                        marker = L.marker(latlng).addTo(map);
                    }
                }
            });
        }
    });

    mostrarOtroEvento();
});
</script>

<?php
    return ob_get_clean();
});
