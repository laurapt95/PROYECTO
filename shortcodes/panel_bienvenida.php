<?php
// Registrar el shortcode [panel_bienvenida] en WordPress
add_shortcode('panel_bienvenida', function () {
    ob_start(); // Iniciar almacenamiento de salida para devolverla al final

    // Verificar que el usuario ha iniciado sesión antes de mostrar contenido
    require_once plugin_dir_path(__FILE__) . '/../verificar_login.php';
    verificar_sesion();

    // Recuperar el alias del usuario desde la sesión
    $alias = $_SESSION['usuario'];

    // Conexión a la base de datos utilizando el archivo centralizado
    require_once plugin_dir_path(__FILE__) . '/../conexion.php';
    global $conn;
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    // Obtener el ID del usuario según su alias
    $stmt = $conn->prepare("SELECT id_usuario FROM USUARIOS WHERE alias = ?");
    $stmt->bind_param("s", $alias);
    $stmt->execute();
    $stmt->bind_result($id_usuario);
    $stmt->fetch();
    $stmt->close();

    // Obtener valoraciones de todos los eventos creados por este usuario
    $stmt = $conn->prepare("
        SELECT estrellas FROM VOTACIONES_EVENTOS
        WHERE id_evento IN (
            SELECT id_evento FROM EVENTOS WHERE id_usuario = ?
        )
    ");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $res = $stmt->get_result();

    // Calcular la media de valoraciones en formato visual (estrellas)
    $total_estrellas = 0;
    $num_votos = 0;
    while ($row = $res->fetch_assoc()) {
        $total_estrellas += intval($row['estrellas']);
        $num_votos++;
    }
    $stmt->close();
    $conn->close(); // Cerrar conexión a la base de datos

    // Si tiene valoraciones, calcular la media
    $media = $num_votos > 0 ? $total_estrellas / $num_votos : 0;
    $estrellas_llenas = floor($media); // Número entero de estrellas llenas
    $media_estrellas = str_repeat('★', $estrellas_llenas) . str_repeat('☆', 5 - $estrellas_llenas);

    // Mostrar bienvenida personalizada con nombre de usuario y valoración
    echo "<h2>Bienvenido, <strong>" . htmlspecialchars($alias) . "</strong> ";
    echo "<span style='color:gold; font-size: 20px;'>$media_estrellas</span></h2>";

    // Mostrar enlaces del panel de usuario
    echo "<p>Accede a las siguientes secciones:</p>";
    echo "<ul style='list-style: none; padding-left: 0;'>";
    echo "<li><a href='" . home_url('/configurar-cuenta') . "'>⚙️ Configuración de cuenta</a></li>";
    echo "<li><a href='" . home_url('/registrar-evento') . "'>📣 Registrar evento</a></li>";
    echo "<li><a href='" . home_url('/modificar-evento') . "'>✏️ Modificar / Borrar evento</a></li>";
    echo "<li><a href='" . home_url('/votar-evento') . "'>✅ Votar evento</a></li>";
    echo "<li><a href='" . home_url('/cerrar-sesion') . "'>🚪 Cerrar sesión</a></li>";
    echo "</ul>";

    return ob_get_clean(); // Devolver todo el contenido generado como resultado del shortcode
});
