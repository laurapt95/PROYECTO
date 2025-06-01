<?php
// Establecer cabecera para que la respuesta sea JSON
header('Content-Type: application/json');

// Verificar que se ha recibido el parámetro id_ccaa
if (!isset($_GET['id_ccaa'])) {
    echo json_encode([]);
    exit;
}

// Convertir el valor recibido a entero para mayor seguridad
$id_ccaa = intval($_GET['id_ccaa']);

// Incluir la conexión a la base de datos desde el archivo centralizado
require_once __DIR__ . '/../conexion.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Preparar y ejecutar la consulta para obtener provincias asociadas a la CCAA
$stmt = $conn->prepare("SELECT id_provincia, nombre FROM PROVINCIAS WHERE id_ccaa = ?");
$stmt->bind_param("i", $id_ccaa);
$stmt->execute();
$res = $stmt->get_result();

// Recoger los resultados en un array
$provincias = [];
while ($row = $res->fetch_assoc()) {
    $provincias[] = $row;
}

// Devolver los datos en formato JSON
echo json_encode($provincias);
exit;
?>

