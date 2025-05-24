<?php
header('Content-Type: application/json');
if (!isset($_GET['id_provincia'])) {
    echo json_encode([]);
    exit;
}
$id_provincia = intval($_GET['id_provincia']);
$conn = new mysqli('10.33.4.23', 'preventuser', 'preventpass', 'eventosdb');
$stmt = $conn->prepare("SELECT id_municipio, nombre FROM MUNICIPIOS WHERE id_provincia = ?");
$stmt->bind_param("i", $id_provincia);
$stmt->execute();
$res = $stmt->get_result();
$municipios = [];
while ($row = $res->fetch_assoc()) {
    $municipios[] = $row;
}
echo json_encode($municipios);
