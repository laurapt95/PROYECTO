<?php
// Registrar el shortcode [contador_eventos]
add_shortcode('contador_eventos', function () {
    ob_start();

    // Incluir conexión desde el archivo centralizado
    require_once plugin_dir_path(__FILE__) . '/../conexion.php';
    global $conn;
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $tipos = [];
    $total = 0;

    // Consultar número de eventos agrupados por tipo
    $res = $conn->query("SELECT tipo_evento, COUNT(*) as cantidad FROM EVENTOS GROUP BY tipo_evento");

    while ($row = $res->fetch_assoc()) {
        $tipos[] = $row;
        $total += $row['cantidad'];
    }

    // Construir HTML con los resultados
    ?>
    <div style="max-width: 600px; margin: 30px auto; background: #f8f8f8; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
        <h2 style="margin-top: 0; color: #2a7a2a; font-size: 1.5em;">Contador de eventos registrados</h2>
        <p style="font-size: 1.1em; margin-bottom: 15px;">Total: <strong style="color: #2a7a2a;"><?php echo $total; ?></strong></p>
        <ul style="list-style: none; padding-left: 0; margin: 0;">
            <?php foreach ($tipos as $tipo): ?>
                <li style="margin-bottom: 6px;">
                    <strong><?php echo htmlspecialchars($tipo['tipo_evento']); ?>:</strong>
                    <?php echo $tipo['cantidad']; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php

    $conn->close();
    return ob_get_clean();
});
