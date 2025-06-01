<?php
// asignar_coordenadas.php
$mysqli = new mysqli('10.33.4.23', 'preventuser', 'preventpass', 'eventosdb');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function obtenerCoordenadas($localidad, $municipio) {
    $query = urlencode("$localidad, $municipio, Asturias, España");
    $url = "https://nominatim.openstreetmap.org/search?format=json&q=$query";

    $opts = ["http" => ["header" => "User-Agent: proyecto-final/1.0\r\n"]];
    $context = stream_context_create($opts);
    $json = file_get_contents($url, false, $context);
    $data = json_decode($json, true);

    if (!empty($data)) {
        return [$data[0]['lat'], $data[0]['lon']];
    }
    return [null, null];
}

$res = $mysqli->query("SELECT L.id_localidad, L.nombre AS loc, M.nombre AS mun FROM LOCALIDADES L JOIN MUNICIPIOS M ON L.id_municipio = M.id_municipio WHERE L.latitud IS NULL OR L.longitud IS NULL");

while ($row = $res->fetch_assoc()) {
    list($lat, $lon) = obtenerCoordenadas($row['loc'], $row['mun']);
    if ($lat && $lon) {
        $stmt = $mysqli->prepare("UPDATE LOCALIDADES SET latitud = ?, longitud = ? WHERE id_localidad = ?");
        $stmt->bind_param("ddi", $lat, $lon, $row['id_localidad']);
        $stmt->execute();
        echo "✔ {$row['loc']} ({$row['mun']}) actualizado: $lat, $lon\n";
    } else {
        echo "❌ No se encontraron coordenadas para {$row['loc']} ({$row['mun']})\n";
    }
}
?>
