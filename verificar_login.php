<?php
// Funcion para verificar si el usuario ha iniciado sesion
function verificar_sesion() {

    // Si aun no se ha iniciado la sesion, la iniciamos
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Comprobamos si no existe la variable de sesion 'usuario'
    if (!isset($_SESSION['usuario'])) {
        // Si no hay sesion iniciada, redirigimos al usuario a la pagina de login por JS
        echo "<script>window.location.href = '" . home_url('/iniciar-sesion') . "';</script>";

        // Detenemos la ejecucion para que no se procese el contenido protegido
        exit;
    }
}
