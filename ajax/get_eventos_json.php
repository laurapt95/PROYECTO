<?php
// Establecer cabecera para que la respuesta sea interpretada como JSON
header('Content-Type: application/json');

// Incluir conexión desde la raíz del plugin
require_once __DIR__ . '/../conexion.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Consulta SQL para obtener eventos con ubicación y media de valoraciones
$query = "
    SELECT 
        E.tipo_evento,
        E.descripcion,
        E.fecha_ini,
        E.fecha_fin,
        L.latitud,
        L.longitud,
        ROUND(AVG(V.estrellas), 1) AS media_valoracion
    FROM EVENTOS E
    JOIN LOCALIDADES L ON E.id_localidad = L.id_localidad
    LEFT JOIN VOTACIONES_EVENTOS V ON E.id_evento = V.id_evento
    WHERE L.latitud IS NOT NULL
      AND L.longitud IS NOT NULL
      AND E.fecha_ini >= NOW()
    GROUP BY E.id_evento
";

// Ejecutar la consulta
$res = $conn->query($query);

// Recoger resultados en un array
$eventos = [];
while ($row = $res->fetch_assoc()) {
    $eventos[] = $row;
}

// Devolver los datos como JSON
echo json_encode($eventos);
exit;
?>
