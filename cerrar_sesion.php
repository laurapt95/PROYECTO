<?php
// Registramos el shortcode [cerrar_sesion]
add_shortcode('cerrar_sesion', function () {
    // Iniciamos la sesión si aún no está iniciada
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    ob_start(); // Empezamos a capturar la salida

    // Si hay un usuario en sesión
    if (isset($_SESSION['usuario'])) {
        session_unset();      // Limpia todas las variables de sesión
        session_destroy();    // Destruye la sesión completamente

        // Mensaje de confirmación y redirección automática a /iniciar-sesion
        echo "<p>🔒 Has cerrado sesión correctamente.</p>";
        echo "<script>
                setTimeout(function() {
                    window.location.href = '" . home_url('/iniciar-sesion') . "';
                }, 1500);
              </script>";
    } else {
        // Si no había sesión iniciada, informamos al usuario
        echo "<p>ℹ️ No hay sesión activa.</p>";
        echo "<script>
                setTimeout(function() {
                    window.location.href = '" . home_url('/') . "';
                }, 1500);
              </script>";
    }

    return ob_get_clean(); // Devolvemos el contenido generado al navegador
});
