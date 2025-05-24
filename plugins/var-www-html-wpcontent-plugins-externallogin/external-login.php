<?php
/*
Plugin Name: External DB Login Redirect
Description: Login con base externa + sesión + redirección.
Version: 1.1
Author: Laura Penedo Torino
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CONEXION CON BBDD EXTERNA

add_shortcode('external_login_form', 'external_login_form_handler');

function external_login_form_handler() {
    ob_start();

    $mensaje = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['alias']) && isset($_POST['password'])) {
        $alias = $_POST['alias'];
        $password = $_POST['password'];

        $conn = new mysqli('10.33.4.23', 'preventuser', 'preventpass', 'eventosdb');
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        if ($conn->connect_error) {
            $mensaje = "<p style='color:red;'>Error de conexión con la base de datos externa.</p>";
        } else {
            $stmt = $conn->prepare("SELECT password FROM USUARIOS WHERE alias = ?");
            $stmt->bind_param("s", $alias);
            $stmt->execute();
            $stmt->bind_result($hash);
            $stmt->fetch();
            $stmt->close();
            $conn->close();

            if ($hash && $password === $hash) {
                $_SESSION['usuario'] = $alias;
                echo "<script>window.location.href = '" . home_url('/bienvenido') . "';</script>";
                return ob_get_clean();
            } else {
                $mensaje = "<p style='color:red;'>❌ Alias o contraseña incorrectos.</p>";
            }
        }
    }

    echo $mensaje;
    ?>
    <form method="POST">
        <p><label for="alias">Alias:</label><br><input type="text" name="alias" required></p>
        <p><label for="password">Contraseña:</label><br><input type="password" name="password" required></p>
        <p>
        <input type="submit" value="Entrar">
        <button type="button" onclick="window.location.href='<?php echo home_url('/registrarse'); ?>'">Registrarse</button>
        </p>
    </form>
    <?php

    return ob_get_clean();
}


// VERIFICAR LOGIN
   add_shortcode('verificar_login', function () {
    if (!isset($_SESSION['usuario'])) {
        echo "<script>window.location.href = '" . home_url('/iniciar-sesion') . "';</script>";
        return '';
    }
    return '';
});

// FORMULARIO DE REGISTRO

add_shortcode('formulario_registro', function () {
    ob_start();

    $mensaje = '';
    $alias = $email = $nombre = $apellidos = '';
    $tipo_usuario = 'particular';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $alias = trim($_POST['alias']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $nombre = trim($_POST['nombre']);
        $apellidos = isset($_POST['apellidos']) ? trim($_POST['apellidos']) : '';
        $tipo_usuario = $_POST['tipo_usuario'];

        // Validación de contraseña segura
        if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $mensaje = "<div style='color:red;'>La contraseña debe tener al menos 8 caracteres, una mayúscula y un número.</div>";
        } else {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            $conn = new mysqli('10.33.4.23', 'preventuser', 'preventpass', 'eventosdb');

            if ($conn->connect_error) {
                $mensaje = "<div style='color:red;'>No se pudo conectar con la base de datos.</div>";
            } else {
                // Verificar si ya existe el alias o email
                $stmt = $conn->prepare("SELECT COUNT(*) FROM USUARIOS WHERE alias = ? OR email = ?");
                $stmt->bind_param("ss", $alias, $email);
                $stmt->execute();
                $stmt->bind_result($count);
                $stmt->fetch();
                $stmt->close();

                if ($count > 0) {
                    $mensaje = "<div style='color:red;'>Alias o correo ya registrados.</div>";
                } else {
                    // Insertar usuario
                    $stmt = $conn->prepare("INSERT INTO USUARIOS (alias, tipo_usuario, nombre, apellidos, email, password) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssssss", $alias, $tipo_usuario, $nombre, $apellidos, $email, $password);

                    if ($stmt->execute()) {
                        error_log("Usuario registrado correctamente: " . $alias);
                        echo "<script>window.location.href = '" . home_url('/iniciar-sesion') . "';</script>";
                        $stmt->close();
                        $conn->close();
                        return ob_get_clean();
                    } else {
                        $mensaje = "<div style='color:red;'>Error al registrar usuario.</div>";
                    }
                    $stmt->close();
                }
                $conn->close();
            }
        }
    }

    echo $mensaje;
    ?>

    <form method="POST" id="form-registro">
        <p>
            <label for="alias">Alias:</label><br>
            <input type="text" name="alias" value="<?php echo htmlspecialchars($alias); ?>" required maxlength="50">
        </p>
        <p>
            <label for="tipo_usuario">Tipo de usuario:</label><br>
            <select name="tipo_usuario" id="tipo_usuario" onchange="toggleApellidos()" required>
                <option value="particular" <?php if ($tipo_usuario === 'particular') echo 'selected'; ?>>Particular</option>
                <option value="organizacion" <?php if ($tipo_usuario === 'organizacion') echo 'selected'; ?>>Organización</option>
            </select>
        </p>
        <p>
            <label for="nombre">Nombre:</label><br>
            <input type="text" name="nombre" value="<?php echo htmlspecialchars($nombre); ?>" required>
        </p>
        <p id="campo-apellidos">
            <label for="apellidos">Apellidos:</label><br>
            <input type="text" name="apellidos" value="<?php echo htmlspecialchars($apellidos); ?>">
        </p>
        <p>
            <label for="email">Correo electrónico:</label><br>
            <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
        </p>
        <p>
            <label for="password">Contraseña:</label><br>
            <input type="password" name="password" required
                pattern="(?=.*[A-Z])(?=.*\d).{8,}"
                title="Debe tener al menos 8 caracteres, una mayúscula y un número">
            <small style="color:gray;">
                La contraseña debe tener al menos <strong>8 caracteres</strong>, una <strong>mayúscula</strong> y <strong>un número</strong>.
            </small>
        </p>
        <p><input type="submit" value="Registrarse"></p>
    </form>

    <script>
    function toggleApellidos() {
        var tipo = document.getElementById('tipo_usuario').value;
        var campoApellidos = document.getElementById('campo-apellidos');
        campoApellidos.style.display = (tipo === 'particular') ? 'block' : 'none';
    }
    window.onload = toggleApellidos;
    </script>

    <?php
    return ob_get_clean();
});


// PANEL_BIENVENIDA

add_shortcode('panel_bienvenida', function () {
    ob_start();

    if (!isset($_SESSION['usuario'])) {
        echo "<script>window.location.href = '" . home_url('/iniciar-sesion') . "';</script>";
        return ob_get_clean();
    }

    $alias = $_SESSION['usuario'];
    $conn = new mysqli('10.33.4.23', 'preventuser', 'preventpass', 'eventosdb');
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    // Obtener ID del usuario
    $stmt = $conn->prepare("SELECT id_usuario FROM USUARIOS WHERE alias = ?");
    $stmt->bind_param("s", $alias);
    $stmt->execute();
    $stmt->bind_result($id_usuario);
    $stmt->fetch();
    $stmt->close();

    // Obtener votos de eventos del usuario
    $stmt = $conn->prepare("
        SELECT estrellas FROM VOTACIONES_EVENTOS
        WHERE id_evento IN (SELECT id_evento FROM EVENTOS WHERE id_usuario = ?)
    ");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $res = $stmt->get_result();

    $total_estrellas = 0;
    $num_votos = 0;
    while ($row = $res->fetch_assoc()) {
        $total_estrellas += intval($row['estrellas']);
        $num_votos++;
    }
    $stmt->close();
    $conn->close();

    $media = $num_votos > 0 ? $total_estrellas / $num_votos : 0;
    $estrellas_llenas = floor($media);
    $media_estrellas = str_repeat('★', $estrellas_llenas) . str_repeat('☆', 5 - $estrellas_llenas);

    // Mostrar nombre y media
    $html = "<h2>Bienvenido, <strong>" . htmlspecialchars($alias) . "</strong> ";
    $html .= "<span style='color:gold; font-size: 20px;'>$media_estrellas</span></h2>";

    $html .= "<p>Accede a las siguientes secciones:</p>";
    $html .= "<ul style='list-style: none; padding-left: 0;'>";
    $html .= "<li><a href='" . home_url('/configurar-cuenta') . "'>⚙️ Configuración de cuenta</a></li>";
    $html .= "<li><a href='" . home_url('/registrar-evento') . "'>📣 Registrar evento</a></li>";
    $html .= "<li><a href='" . home_url('/modificar-evento') . "'>✏️ Modificar / Borrar evento</a></li>";
    $html .= "<li><a href='" . home_url('/votar-evento') . "'>✅ Votar evento</a></li>";
    $html .= "<li><a href='" . home_url('/cerrar-sesion') . "'>🚪 Cerrar sesión</a></li>";
    $html .= "</ul>";

    return $html;
});

// CERRAR_SESION

add_shortcode('cerrar_sesion', function () {
    session_start();
    unset($_SESSION['usuario']);
    session_destroy();
    echo "<script>window.location.href = '" . home_url('/') . "';</script>";
    return '';
});


// CONFIGURAR_CUENTA

add_shortcode('configurar_cuenta', function () {
    ob_start();

    if (!isset($_SESSION['usuario'])) {
        echo "<script>window.location.href = '" . home_url('/iniciar-sesion') . "';</script>";
        return ob_get_clean();
    }

    $alias_actual = $_SESSION['usuario'];

    $conn = new mysqli('10.33.4.23', 'preventuser', 'preventpass', 'eventosdb');
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    // Obtener datos actuales
    $stmt = $conn->prepare("SELECT tipo_usuario, nombre, apellidos, email, password FROM USUARIOS WHERE alias = ?");
    $stmt->bind_param("s", $alias_actual);
    $stmt->execute();
    $stmt->bind_result($tipo_usuario, $nombre, $apellidos, $email, $password_guardado);
    $stmt->fetch();
    $stmt->close();

    $mensaje = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nuevo_tipo = $_POST['tipo_usuario'];
        $nuevo_nombre = trim($_POST['nombre']);
        $nuevo_apellidos = ($nuevo_tipo === 'particular') ? trim($_POST['apellidos']) : '';
        $nuevo_email = trim($_POST['email']);
        $nuevo_password = trim($_POST['password']);

        // Validación email único (si cambia)
        if ($nuevo_email !== $email) {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM USUARIOS WHERE email = ?");
            $stmt->bind_param("s", $nuevo_email);
            $stmt->execute();
            $stmt->bind_result($count_email);
            $stmt->fetch();
            $stmt->close();

            if ($count_email > 0) {
                $mensaje .= "<p style='color:red;'>Correo ya registrado.</p>";
            }
        }

        // Validación de contraseña (si cambia)
        if (!empty($nuevo_password)) {
            if (strlen($nuevo_password) < 8 || !preg_match('/[A-Z]/', $nuevo_password) || !preg_match('/[0-9]/', $nuevo_password)) {
                $mensaje .= "<p style='color:red;'>La contraseña debe tener al menos 8 caracteres, una mayúscula y un número.</p>";
            }
        }

        // Ejecutar actualización
        if (empty($mensaje)) {
            $query = "UPDATE USUARIOS SET tipo_usuario=?, nombre=?, apellidos=?, email=?";
            $params = [$nuevo_tipo, $nuevo_nombre, $nuevo_apellidos, $nuevo_email];
            $types = "ssss";

            if (!empty($nuevo_password)) {
                $query .= ", password=?";
                $params[] = $nuevo_password;
                $types .= "s";
            }

            $query .= " WHERE alias=?";
            $params[] = $alias_actual;
            $types .= "s";

            $stmt = $conn->prepare($query);
            $stmt->bind_param($types, ...$params);

            if ($stmt->execute()) {
                $mensaje = "<p style='color:green;'>✔ Datos actualizados correctamente.</p>";
                $_SESSION['usuario'] = $alias_actual;
                $tipo_usuario = $nuevo_tipo;
                $nombre = $nuevo_nombre;
                $apellidos = $nuevo_apellidos;
                $email = $nuevo_email;
            } else {
                $mensaje = "<p style='color:red;'>❌ Error al actualizar los datos.</p>";
            }
            $stmt->close();
        }
    }

    $conn->close();

    echo $mensaje;
    ?>

    <p style="margin-top: 10px; font-weight: bold;">
        Puedes cambiar: tipo de usuario, nombre, apellidos (si eliges 'particular'), correo electrónico o contraseña.
    </p>

    <form method="POST" id="form-config">
        <p><label for="tipo_usuario">Tipo de usuario:</label><br>
            <select name="tipo_usuario" id="tipo_usuario" onchange="toggleApellidos()" required>
                <option value="particular" <?php if ($tipo_usuario === 'particular') echo 'selected'; ?>>Particular</option>
                <option value="organizacion" <?php if ($tipo_usuario === 'organizacion') echo 'selected'; ?>>Organización</option>
            </select>
        </p>

        <p><label for="nombre">Nombre:</label><br>
            <input type="text" name="nombre" value="<?php echo htmlspecialchars($nombre); ?>">
        </p>

        <p id="campo-apellidos">
            <label for="apellidos">Apellidos:</label><br>
            <input type="text" name="apellidos" value="<?php echo htmlspecialchars($apellidos); ?>">
        </p>

        <p><label for="email">Correo electrónico:</label><br>
            <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
        </p>

        <p>
            <label for="password">Nueva contraseña:</label><br>
            <input type="password" name="password" pattern="(?=.*[A-Z])(?=.*\d).{8,}" title="Mínimo 8 caracteres, una mayúscula y un número">
            <small style="color:gray;">Déjalo vacío si no deseas cambiarla.</small>
        </p>

        <p><input type="submit" value="Actualizar"></p>
    </form>

    <p style="margin-top: 20px;">
        <a href="<?php echo home_url('/bienvenido'); ?>" style="text-decoration: none; background: #0073aa; color: white; padding: 8px 12px; border-radius: 4px;">
            ⬅ Volver a Bienvenido
        </a>
    </p>

    <script>
    function toggleApellidos() {
        var tipo = document.getElementById('tipo_usuario').value;
        var campoApellidos = document.getElementById('campo-apellidos');
        campoApellidos.style.display = (tipo === 'particular') ? 'block' : 'none';
    }
    window.onload = toggleApellidos;
    </script>

    <?php
    return ob_get_clean();
});


// FORMULARIO REGISTRO EVENTO

add_shortcode('formulario_registrar_evento', function () {
    ob_start();
    $mensaje = '';

    if (!isset($_SESSION['usuario'])) {
        echo "<script>window.location.href = '" . home_url('/') . "';</script>";
        return ob_get_clean();
    }

    $alias = $_SESSION['usuario'];
    $conn = new mysqli('10.33.4.23', 'preventuser', 'preventpass', 'eventosdb');
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $stmt = $conn->prepare("SELECT id_usuario FROM USUARIOS WHERE alias = ?");
    $stmt->bind_param("s", $alias);
    $stmt->execute();
    $stmt->bind_result($id_usuario);
    $stmt->fetch();
    $stmt->close();

    $paises = [];
    $res = $conn->query("SELECT id_pais, nombre FROM PAISES ORDER BY nombre");
    while ($row = $res->fetch_assoc()) {
        $paises[] = $row;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nombre_evento = trim($_POST['nombre_evento']);
        $tipo_evento = $_POST['tipo_evento'];
        $otro_evento = ($tipo_evento === 'Otros' && !empty($_POST['otro_tipo'])) ? trim($_POST['otro_tipo']) : null;
        $descripcion = trim($_POST['descripcion']);
        $fecha_ini = $_POST['fecha_ini'];
        $fecha_fin = !empty($_POST['fecha_fin']) ? $_POST['fecha_fin'] : null;
        $id_localidad = intval($_POST['id_localidad']);

        if (empty($nombre_evento) || empty($descripcion) || empty($fecha_ini) || empty($tipo_evento) || empty($id_localidad)) {
            $mensaje = "<p style='color:red;'>Todos los campos obligatorios deben estar rellenados.</p>";
        } else {
            $stmt = $conn->prepare("INSERT INTO EVENTOS (nombre_evento, tipo_evento, otro_tipo, descripcion, fecha_ini, fecha_fin, id_localidad, id_usuario)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssii", $nombre_evento, $tipo_evento, $otro_evento, $descripcion, $fecha_ini, $fecha_fin, $id_localidad, $id_usuario);
            if ($stmt->execute()) {
                $mensaje = "<p style='color:green;'>✔ Evento registrado correctamente.</p>";
            } else {
                $mensaje = "<p style='color:red;'>❌ Error al registrar el evento.</p>";
            }
            $stmt->close();
        }
    }

    $conn->close();
    $mensaje .= "<p><a href='" . home_url('/bienvenido') . "' style='color:#0073aa; text-decoration:underline;'>⬅ Volver a la página de bienvenida</a></p>";
    echo $mensaje;
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

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
            $.get('<?php echo home_url(); ?>/wp-content/plugins/external-login/get_ccaa.php', { id_pais: id }, function(data) {
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
            $.get('<?php echo home_url(); ?>/wp-content/plugins/external-login/get_provincias.php', { id_ccaa: id }, function(data) {
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
            $.get('<?php echo home_url(); ?>/wp-content/plugins/external-login/get_municipios.php', { id_provincia: id }, function(data) {
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
            $.get('<?php echo home_url(); ?>/wp-content/plugins/external-login/get_localidades.php', { id_municipio: id }, function(data) {
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
            $.get('<?php echo home_url(); ?>/wp-content/plugins/external-login/get_localidades.php', {
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

// MODIFICAR / BORRAR EVENTOS PROPIOS

add_shortcode('modificar_eventos_usuario', function () {
    ob_start();
    if (!isset($_SESSION['usuario'])) {
        echo "<script>window.location.href = '" . home_url('/iniciar-sesion') . "';</script>";
        return ob_get_clean();
    }

    $alias = $_SESSION['usuario'];
    $conn = new mysqli('10.33.4.23', 'preventuser', 'preventpass', 'eventosdb');
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $stmt = $conn->prepare("SELECT id_usuario FROM USUARIOS WHERE alias = ?");
    $stmt->bind_param("s", $alias);
    $stmt->execute();
    $stmt->bind_result($id_usuario);
    $stmt->fetch();
    $stmt->close();

    $mensaje = '';
    $evento = null;

    if (isset($_POST['accion']) && $_POST['accion'] === 'borrar-directo') {
        $id_evento = intval($_POST['id_evento']);
        $stmt = $conn->prepare("DELETE FROM EVENTOS WHERE id_evento = ? AND id_usuario = ?");
        $stmt->bind_param("ii", $id_evento, $id_usuario);
        if ($stmt->execute()) {
            $mensaje = "<p style='color:green;'>🗑 Evento borrado correctamente.</p>";
        }
        $stmt->close();
    }

    if (isset($_POST['accion']) && $_POST['accion'] === 'guardar') {
        $id_evento = intval($_POST['id_evento']);
        $nombre = trim($_POST['nombre_evento']);
        $tipo = $_POST['tipo_evento'];
        $otro = ($tipo === 'Otros') ? trim($_POST['otro_tipo']) : null;
        $descripcion = trim($_POST['descripcion']);
        $fecha_ini = $_POST['fecha_ini'];
        $fecha_fin = !empty($_POST['fecha_fin']) ? $_POST['fecha_fin'] : null;
        $id_localidad = intval($_POST['id_localidad']);

        if (strtotime($fecha_ini) < time()) {
            $mensaje = "<p style='color:red;'>❌ La fecha de inicio no puede ser anterior al momento actual.</p>";
        } elseif ($fecha_fin && strtotime($fecha_fin) < strtotime($fecha_ini)) {
            $mensaje = "<p style='color:red;'>❌ La fecha de fin no puede ser anterior a la fecha de inicio.</p>";
        } else {
            $stmt = $conn->prepare("UPDATE EVENTOS SET nombre_evento=?, tipo_evento=?, otro_tipo=?, descripcion=?, fecha_ini=?, fecha_fin=?, id_localidad=? WHERE id_evento=? AND id_usuario=?");
            $stmt->bind_param("sssssssii", $nombre, $tipo, $otro, $descripcion, $fecha_ini, $fecha_fin, $id_localidad, $id_evento, $id_usuario);
            if ($stmt->execute()) {
                $mensaje = "<p style='color:green;'>✔ Cambios guardados correctamente.</p>";
            }
            $stmt->close();
        }
    }

    $eventos = [];
    $res = $conn->query("SELECT id_evento, nombre_evento FROM EVENTOS WHERE id_usuario = $id_usuario AND fecha_ini >= NOW()");
    while ($row = $res->fetch_assoc()) {
        $eventos[] = $row;
    }

    if (isset($_POST['accion']) && $_POST['accion'] === 'modificar') {
        $id_evento = intval($_POST['id_evento']);
        $stmt = $conn->prepare("SELECT * FROM EVENTOS WHERE id_evento = ? AND id_usuario = ?");
        $stmt->bind_param("ii", $id_evento, $id_usuario);
        $stmt->execute();
        $res = $stmt->get_result();
        $evento = $res->fetch_assoc();
        $stmt->close();
    }

    echo $mensaje;

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

    if ($evento) {
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
                    foreach ($tipos as $tipo) {
                        $sel = ($evento['tipo_evento'] === $tipo) ? 'selected' : '';
                        echo "<option value=\"$tipo\" $sel>$tipo</option>";
                    }
                    ?>
                </select>
            </p>
            <p id="campo-otro-tipo" style="display:none;"><label>Otro tipo:</label><br>
                <input type="text" name="otro_tipo" value="<?php echo htmlspecialchars($evento['otro_tipo']); ?>"></p>
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
                fetch('<?php echo home_url(); ?>/wp-content/plugins/external-login/fill_location.php?id_localidad=' + id_localidad)
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
        <?php
    }

    echo "<p><a href='" . home_url('/bienvenido') . "'>⬅ Volver a Bienvenido</a></p>";
    return ob_get_clean();
});

// CONTADOR EVENTOS

function mostrar_contador_eventos() {
    $conn = new mysqli('10.33.4.23', 'preventuser', 'preventpass', 'eventosdb');
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $tipos = [];
    $total = 0;

    // Consulta para agrupar por tipo_evento
    $res = $conn->query("SELECT tipo_evento, COUNT(*) as cantidad FROM EVENTOS GROUP BY tipo_evento");

    while ($row = $res->fetch_assoc()) {
        $tipos[] = $row;
        $total += $row['cantidad'];
    }

$html = "<div style='max-width: 600px; margin: 30px auto; background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-left: 6px solid #4f8c4f; border-radius: 8px; padding: 20px; font-family: sans-serif;'>";
$html .= "<h2 style='margin-top: 0; color: #4f8c4f; font-size: 1.5em;'>📊 Contador de eventos registrados</h2>";
$html .= "<p style='font-size: 1.1em; margin-bottom: 15px; color: #000;'>Total: <span style='color: #4f8c4f; font-weight: bold;'>$total</span></p>";
$html .= "<ul style='list-style: none; padding-left: 0; margin: 0;'>";
foreach ($tipos as $tipo) {
    $html .= "<li style='margin-bottom: 6px; color: #000;'>
        🟢 <strong>{$tipo['tipo_evento']}:</strong> <span style='color: #4f8c4f; font-weight: bold;'>{$tipo['cantidad']}</span>
    </li>";
}
$html .= "</ul></div>";


    $conn->close();
    return $html;
}
add_shortcode('contador_eventos', 'mostrar_contador_eventos');

// VOTAR EVENTO

add_shortcode('votar_evento', function () {
    ob_start();

    if (!isset($_SESSION['usuario'])) {
        echo "<script>window.location.href = '" . home_url('/iniciar-sesion') . "';</script>";
        return ob_get_clean();
    }

    // Enlace arriba
    echo "<p style='text-align:right; margin-bottom:15px;'>
            <a href='" . home_url('/bienvenido') . "' style='background:#0073aa; color:white; padding:6px 12px; border-radius:5px; text-decoration:none;'>
            ⬅ Volver a Bienvenido
            </a>
          </p>";

    $alias = $_SESSION['usuario'];
    $conn = new mysqli('10.33.4.23', 'preventuser', 'preventpass', 'eventosdb');
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    // Obtener ID de usuario
    $stmt = $conn->prepare("SELECT id_usuario FROM USUARIOS WHERE alias = ?");
    $stmt->bind_param("s", $alias);
    $stmt->execute();
    $stmt->bind_result($id_usuario);
    $stmt->fetch();
    $stmt->close();

    $mensaje = '';

    // Procesar envío de votación
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_evento'], $_POST['estrellas'])) {
        $id_evento = intval($_POST['id_evento']);
        $estrellas = intval($_POST['estrellas']);
        if ($estrellas >= 1 && $estrellas <= 5) {
            $stmt = $conn->prepare("
                INSERT INTO VOTACIONES_EVENTOS (id_usuario, id_evento, estrellas, fecha_voto)
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE estrellas = VALUES(estrellas), fecha_voto = NOW()
            ");
            $stmt->bind_param("iii", $id_usuario, $id_evento, $estrellas);
            if ($stmt->execute()) {
                $mensaje = "<p style='color:green;'>✔ Tu votación se ha registrado/modificado.</p>";
            }
            $stmt->close();
        }
    }

    // Obtener eventos pasados de otros usuarios + voto previo (si existe)
    $stmt = $conn->prepare("
        SELECT E.id_evento, E.nombre_evento, E.descripcion, E.fecha_ini,
               V.estrellas AS mi_voto
        FROM EVENTOS E
        LEFT JOIN VOTACIONES_EVENTOS V ON V.id_evento = E.id_evento AND V.id_usuario = ?
        WHERE E.fecha_ini < NOW() AND E.id_usuario != ?
        ORDER BY E.fecha_ini DESC
    ");
    $stmt->bind_param("ii", $id_usuario, $id_usuario);
    $stmt->execute();
    $res = $stmt->get_result();

    echo $mensaje;

    if ($res->num_rows === 0) {
        echo "<p>No hay eventos pasados para valorar.</p>";
    } else {
        echo "<form method='POST'>";
        while ($row = $res->fetch_assoc()) {
            $id_evento = $row['id_evento'];
            $nombre = htmlspecialchars($row['nombre_evento']);
            $descripcion = htmlspecialchars($row['descripcion']);
            $fecha = htmlspecialchars($row['fecha_ini']);
            $mi_voto = intval($row['mi_voto'] ?? 0);

            echo "<div style='border:1px solid #ccc; padding:10px; margin-bottom:15px;'>";
            echo "<strong>$nombre</strong><br><small>📅 $fecha</small><p>$descripcion</p>";

            echo "<label>Tu voto: </label><br>";
            for ($i = 1; $i <= 5; $i++) {
                $checked = ($i == $mi_voto) ? 'checked' : '';
                echo "<label style='margin-right:8px;'>";
                echo "<input type='radio' name='estrellas' value='$i' $checked required> ";
                echo str_repeat("⭐", $i);
                echo "</label>";
            }

            echo "<input type='hidden' name='id_evento' value='$id_evento'>";
            echo "<br><button type='submit' style='margin-top:5px;'>Enviar voto</button>";
            echo "</div>";
        }
        echo "</form>";
    }

    $stmt->close();
    $conn->close();

    // Enlace abajo
    echo "<p><a href='" . home_url('/bienvenido') . "' style='text-decoration: none; color: #0073aa;'>⬅ Volver a Bienvenido</a></p>";

    return ob_get_clean();
});

//BUSCAR EVENTOS

add_shortcode('buscar_eventos_mapa', function () {
    ob_start(); ?>

<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<h2 style="margin-bottom: 15px;">🗺 Eventos registrados en el mapa</h2>
<div id="map" style="height: 500px; width: 100%;"></div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const map = L.map('map').setView([43.5, -5.85], 10,75);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(map);

    fetch('<?php echo home_url(); ?>/wp-content/plugins/external-login/get_eventos_json.php')
        .then(res => res.json())
        .then(data => {
            data.forEach(evento => {
                const marker = L.marker([evento.latitud, evento.longitud]).addTo(map);
                marker.bindPopup(`
                    <strong>${evento.tipo_evento}</strong><br>
                    <em>${evento.descripcion}</em><br>
                    📅 ${evento.fecha_ini} - ${evento.fecha_fin || '—'}<br>
                    ⭐ Media valoraciones: ${evento.media_valoracion || 'Sin votos'}
                `);
            });
        });
});
</script>

<?php
    return ob_get_clean();
});
