<?php
header('Content-Type: application/json');
if (!isset($_GET['id_ccaa'])) {
    echo json_encode([]);
    exit;
}
$id_ccaa = intval($_GET['id_ccaa']);
$conn = new mysqli('10.33.4.23', 'preventuser', 'preventpass', 'eventosdb');
$stmt = $conn->prepare("SELECT id_provincia, nombre FROM PROVINCIAS WHERE id_ccaa = ?");
$stmt->bind_param("i", $id_ccaa);
$stmt->execute();
$res = $stmt->get_result();
$provincias = [];
while ($row = $res->fetch_assoc()) {
    $provincias[] = $row;
}
echo json_encode($provincias);
