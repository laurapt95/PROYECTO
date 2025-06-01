<?php
add_shortcode('configurar_cuenta', function () {
    ob_start(); // Inicia la captura del contenido generado por el shortcode

    // Verifica que el usuario ha iniciado sesión antes de permitir el acceso
    require_once plugin_dir_path(__FILE__) . '/../verificar_login.php';
    verificar_sesion();

    // Recupera el alias del usuario actual desde la sesión
    $alias_actual = $_SESSION['usuario'];

    // Conecta con la base de datos usando el archivo centralizado de conexión
    require_once plugin_dir_path(__FILE__) . '/../conexion.php';
    global $conn;
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // Muestra errores si los hay

    // Consulta los datos actuales del usuario para rellenar el formulario
    $stmt = $conn->prepare("SELECT tipo_usuario, nombre, apellidos, email, password FROM USUARIOS WHERE alias = ?");
    $stmt->bind_param("s", $alias_actual);
    $stmt->execute();
    $stmt->bind_result($tipo_usuario, $nombre, $apellidos, $email, $password_guardado);
    $stmt->fetch();
    $stmt->close();

    $mensaje = ''; // Variable para acumular mensajes de error o éxito

    // Si se ha enviado el formulario de actualización
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Recoger y limpiar los datos del formulario
        $nuevo_tipo = $_POST['tipo_usuario'];
        $nuevo_nombre = trim($_POST['nombre']);
        $nuevo_apellidos = ($nuevo_tipo === 'particular') ? trim($_POST['apellidos']) : '';
        $nuevo_email = trim($_POST['email']);
        $nuevo_password = trim($_POST['password']); // Puede estar vacío si no se cambia

        // Comprobación de email duplicado solo si se ha cambiado
        if ($nuevo_email !== $email) {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM USUARIOS WHERE email = ?");
            $stmt->bind_param("s", $nuevo_email);
            $stmt->execute();
            $stmt->bind_result($count_email);
            $stmt->fetch();
            $stmt->close();

            // Mostrar error si el nuevo email ya está registrado
            if ($count_email > 0) {
                $mensaje .= "<p style='color:red;'>El correo ya está registrado.</p>";
            }
        }

        // Validación de la nueva contraseña (si se proporciona una)
        if (!empty($nuevo_password)) {
            if (strlen($nuevo_password) < 8 || !preg_match('/[A-Z]/', $nuevo_password) || !preg_match('/[0-9]/', $nuevo_password)) {
                $mensaje .= "<p style='color:red;'>La contraseña debe tener al menos 8 caracteres, una mayúscula y un número.</p>";
            } else {
                // Si es válida, se cifra antes de guardar
                $nuevo_password = password_hash($nuevo_password, PASSWORD_DEFAULT);
            }
        }

        // Si no hay errores acumulados, preparar la actualización
        if (empty($mensaje)) {
            // Construir consulta dinámica según si hay nueva contraseña o no
            $query = "UPDATE USUARIOS SET tipo_usuario = ?, nombre = ?, apellidos = ?, email = ?";
            $params = [$nuevo_tipo, $nuevo_nombre, $nuevo_apellidos, $nuevo_email];
            $types = "ssss";

            // Agregar contraseña a la consulta solo si se va a actualizar
            if (!empty($nuevo_password)) {
                $query .= ", password = ?";
                $params[] = $nuevo_password;
                $types .= "s";
            }

            $query .= " WHERE alias = ?";
            $params[] = $alias_actual;
            $types .= "s";

            // Ejecutar la consulta preparada
            $stmt = $conn->prepare($query);
            $stmt->bind_param($types, ...$params);

            if ($stmt->execute()) {
                // Actualización exitosa: actualizar variables locales
                $mensaje = "<p style='color:green;'>Datos actualizados correctamente.</p>";
                $tipo_usuario = $nuevo_tipo;
                $nombre = $nuevo_nombre;
                $apellidos = $nuevo_apellidos;
                $email = $nuevo_email;
            } else {
                // Error al ejecutar la consulta
                $mensaje = "<p style='color:red;'>Error al actualizar los datos.</p>";
            }
            $stmt->close();
        }
    }

    $conn->close(); // Cerrar la conexión a la base de datos

    // Mostrar mensaje (éxito o errores)
    echo $mensaje;
    ?>

    <p><strong>Puedes cambiar:</strong> tipo de usuario, nombre, apellidos (si eres particular), correo electrónico y contraseña.</p>

    <form method="POST" id="form-config">
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
            <label for="password">Nueva contraseña:</label><br>
            <input type="password" name="password"
                   pattern="(?=.*[A-Z])(?=.*\d).{8,}"
                   title="Mínimo 8 caracteres, una mayúscula y un número">
            <small style="color:gray;">Déjalo vacío si no deseas cambiarla.</small>
        </p>

        <p><input type="submit" value="Actualizar"></p>
    </form>

    <p style="margin-top: 20px;">
        <a href="<?php echo home_url('/bienvenido'); ?>" style="text-decoration: none; background: #0073aa; color: white; padding: 8px 12px; border-radius: 4px;">
            Volver a Bienvenido
        </a>
    </p>

    <script>
    function toggleApellidos() {
        const tipo = document.getElementById('tipo_usuario').value;
        const campoApellidos = document.getElementById('campo-apellidos');
        campoApellidos.style.display = (tipo === 'particular') ? 'block' : 'none';
    }
    window.onload = toggleApellidos;
    </script>

    <?php
    return ob_get_clean(); // Devolver el contenido generado para que se muestre en la página
});
