<?php
/*
Plugin Name: External DB Login Redirect
Description: Login con base externa, registro, sesión y gestión de eventos.
Version: 1.1
Author: Laura Penedo Torino
*/

// Iniciar sesión si aún no se ha iniciado
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir archivos principales 
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/login.php';
require_once __DIR__ . '/cerrar_sesion.php';
require_once __DIR__ . '/verificar_login.php';

// Incluir todos los archivos de shortcodes
require_once __DIR__ . '/shortcodes/formulario_registro.php';
require_once __DIR__ . '/shortcodes/panel_bienvenida.php';
require_once __DIR__ . '/shortcodes/configurar_cuenta.php';
require_once __DIR__ . '/shortcodes/formulario_registrar_evento.php';
require_once __DIR__ . '/shortcodes/modificar_eventos_usuario.php';
require_once __DIR__ . '/shortcodes/contador_eventos.php';
require_once __DIR__ . '/shortcodes/votar_evento.php';
require_once __DIR__ . '/shortcodes/buscar_eventos_mapa.php';
