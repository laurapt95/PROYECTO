<?php
header('Content-Type: application/json');

if (!isset($_GET['id_pais'])) {
    echo json_encode([]);
    exit;
}

$id_pais = intval($_GET['id_pais']);

$conn = new mysqli('10.33.4.23', 'preventuser', 'preventpass', 'eventosdb');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$stmt = $conn->prepare("SELECT id_ccaa, nombre FROM CCAA WHERE id_pais = ?");
$stmt->bind_param("i", $id_pais);
$stmt->execute();
$res = $stmt->get_result();

$ccaa = [];
while ($row = $res->fetch_assoc()) {
    $ccaa[] = $row;
}

echo json_encode($ccaa);
