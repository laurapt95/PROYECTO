<?php
add_shortcode('formulario_registro', function () {
    ob_start();

    // Variables iniciales
    $mensaje = '';
    $alias = $email = $nombre = $apellidos = '';
    $tipo_usuario = 'particular';

    // Procesamiento del formulario
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Recoger y limpiar los datos
        $alias = trim($_POST['alias']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $nombre = trim($_POST['nombre']);
        $apellidos = isset($_POST['apellidos']) ? trim($_POST['apellidos']) : '';
        $tipo_usuario = $_POST['tipo_usuario'];

        // Validación de la contraseña
        if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $mensaje = "<div style='color:red;'>La contraseña debe tener al menos 8 caracteres, una mayúscula y un número.</div>";
        } else {
            // Conexión a la base de datos usando el archivo centralizado
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            require_once plugin_dir_path(__FILE__) . '/../conexion.php';

            // Comprobación de alias o email duplicado
            $stmt = $conn->prepare("SELECT COUNT(*) FROM USUARIOS WHERE alias = ? OR email = ?");
            $stmt->bind_param("ss", $alias, $email);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();

            if ($count > 0) {
                $mensaje = "<div style='color:red;'>Alias o correo ya registrados.</div>";
            } else {
                // Hashear la contraseña antes de almacenarla
                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                // Insertar nuevo usuario
                $stmt = $conn->prepare("INSERT INTO USUARIOS (alias, tipo_usuario, nombre, apellidos, email, password) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssss", $alias, $tipo_usuario, $nombre, $apellidos, $email, $password_hash);

                if ($stmt->execute()) {
                    $stmt->close();
                    $conn->close();
                    // Redirigir al login después del registro
                    echo "<script>window.location.href = '" . home_url('/iniciar-sesion') . "';</script>";
                    return ob_get_clean();
                } else {
                    $mensaje = "<div style='color:red;'>Error al registrar usuario.</div>";
                }

                $stmt->close();
            }

            $conn->close();
        }
    }

    // Mostrar mensaje (si hay)
    echo $mensaje;
    ?>

    <!-- Formulario HTML -->
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

// Script para mostrar u ocultar el campo de apellidos en funcion del tipo de usuario
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
