<?php
// Registrar el shortcode [buscar_eventos_mapa]
add_shortcode('buscar_eventos_mapa', function () {
    ob_start();

    // Incluir conexión a la base de datos centralizada
    require_once plugin_dir_path(__FILE__) . '/../conexion.php';
    global $conn;
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    // Obtener solo eventos actuales o futuros
    $res = $conn->query("
        SELECT E.nombre_evento, E.descripcion, E.fecha_ini, L.latitud, L.longitud
        FROM EVENTOS E
        JOIN LOCALIDADES L ON E.id_localidad = L.id_localidad
        WHERE L.latitud IS NOT NULL AND L.longitud IS NOT NULL
          AND (E.fecha_ini >= NOW() OR (E.fecha_fin IS NOT NULL AND E.fecha_fin >= NOW()))
    ");

    $eventos = [];
    while ($row = $res->fetch_assoc()) {
        $eventos[] = $row;
    }

    $conn->close();
    ?>

    <!-- Contenedor del mapa -->
    <div id="map" style="height: 500px;"></div>

    <!-- Cargar Leaflet (JS y CSS) -->
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />

    <script>
    document.addEventListener("DOMContentLoaded", function () {
        // Inicializar el mapa
        var map = L.map('map').setView([43.52, -5.82], 10);

        // Añadir capa base OSM
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(map);

        // Insertar eventos desde PHP
        var eventos = <?php echo json_encode($eventos); ?>;

        // Añadir marcadores con popups
        eventos.forEach(e => {
            var marker = L.marker([e.latitud, e.longitud]).addTo(map);
            marker.bindPopup(
                "<strong>" + e.nombre_evento + "</strong><br>" +
                e.descripcion + "<br>" +
                "<small>📅 " + e.fecha_ini + "</small>"
            );
        });
    });
    </script>

    <?php
    return ob_get_clean();
});

