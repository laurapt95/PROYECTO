<?php
// Registrar el shortcode [external_login_form]
add_shortcode('external_login_form', function () {
    ob_start();

    // Iniciar sesión si no está iniciada
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $mensaje = '';

    // Si el formulario fue enviado por POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['alias'], $_POST['password'])) {
        $alias = trim($_POST['alias']);
        $password = $_POST['password'];

        // Incluir conexión centralizada
        require_once __DIR__ . '/conexion.php';
        global $conn;
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        // Buscar el usuario por alias
        $stmt = $conn->prepare("SELECT password FROM USUARIOS WHERE alias = ?");
        $stmt->bind_param("s", $alias);
        $stmt->execute();
        $stmt->bind_result($hash);
        $usuario_encontrado = $stmt->fetch();
        $stmt->close();
        $conn->close();

        // Verificar contraseña si se encontró el usuario
        if ($usuario_encontrado && password_verify($password, $hash)) {
            $_SESSION['usuario'] = $alias;
            echo "<script>window.location.href = '" . home_url('/bienvenido') . "';</script>";
            return ob_get_clean();
        } else {
            $mensaje = "<p style='color:red;'>❌ Alias o contraseña incorrectos.</p>";
        }
    }

    // Mostrar mensaje de error si lo hay
    echo $mensaje;
    ?>

    <form method="POST">
        <p><label for="alias">Alias:</label><br>
            <input type="text" name="alias" required>
        </p>
        <p><label for="password">Contraseña:</label><br>
            <input type="password" name="password" required>
        </p>
        <p>
            <input type="submit" value="Entrar">
            <button type="button" onclick="window.location.href='<?php echo home_url('/registrarse'); ?>'">Registrarse</button>
        </p>
    </form>

    <?php
    return ob_get_clean();
});
