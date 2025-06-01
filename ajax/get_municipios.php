<?php
// Establecer cabecera para que el navegador interprete la respuesta como JSON
header('Content-Type: application/json');

// Verificar que se ha recibido el parámetro id_provincia
if (!isset($_GET['id_provincia'])) {
    echo json_encode([]);
    exit;
}

// Convertir el valor recibido a entero para mayor seguridad
$id_provincia = intval($_GET['id_provincia']);

// Incluir la conexión a la base de datos desde el archivo central del plugin
require_once __DIR__ . '/../conexion.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Preparar y ejecutar la consulta para obtener municipios de la provincia
$stmt = $conn->prepare("SELECT id_municipio, nombre FROM MUNICIPIOS WHERE id_provincia = ?");
$stmt->bind_param("i", $id_provincia);
$stmt->execute();
$res = $stmt->get_result();

// Recoger los resultados en un array
$municipios = [];
while ($row = $res->fetch_assoc()) {
    $municipios[] = $row;
}

// Devolver los datos como JSON
echo json_encode($municipios);
exit;
?>
