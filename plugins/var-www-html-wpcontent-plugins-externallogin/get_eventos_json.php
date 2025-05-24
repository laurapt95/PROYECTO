<?php
header('Content-Type: application/json');

$conn = new mysqli('10.33.4.23', 'preventuser', 'preventpass', 'eventosdb');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$query = "
SELECT E.tipo_evento, E.descripcion, E.fecha_ini, E.fecha_fin,
       L.latitud, L.longitud,
       ROUND(AVG(V.estrellas), 1) AS media_valoracion
FROM EVENTOS E
JOIN LOCALIDADES L ON E.id_localidad = L.id_localidad
LEFT JOIN VOTACIONES_EVENTOS V ON E.id_evento = V.id_evento
WHERE L.latitud IS NOT NULL AND L.longitud IS NOT NULL
  AND E.fecha_ini >= NOW()
GROUP BY E.id_evento
";

$res = $conn->query($query);
$eventos = [];
while ($row = $res->fetch_assoc()) {
    $eventos[] = $row;
}

echo json_encode($eventos);
?>
