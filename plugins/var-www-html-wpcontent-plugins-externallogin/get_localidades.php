<?php
header('Content-Type: application/json');
if (!isset($_GET['id_municipio'])) {
    echo json_encode([]);
    exit;
}
$id_municipio = intval($_GET['id_municipio']);
$conn = new mysqli('10.33.4.23', 'preventuser', 'preventpass', 'eventosdb');
$stmt = $conn->prepare("SELECT id_localidad, nombre, latitud, longitud FROM LOCALIDADES WHERE id_municipio = ?");
$stmt->bind_param("i", $id_municipio);
$stmt->execute();
$res = $stmt->get_result();
$localidades = [];
while ($row = $res->fetch_assoc()) {
    $localidades[] = $row;
}
echo json_encode($localidades);
?>
