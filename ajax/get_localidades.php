<?php
// Establecer cabecera para que el navegador interprete la respuesta como JSON
header('Content-Type: application/json');

// Verificar que se ha recibido el parámetro id_municipio
if (!isset($_GET['id_municipio'])) {
    echo json_encode([]);
    exit;
}

// Convertir el valor recibido a entero para mayor seguridad
$id_municipio = intval($_GET['id_municipio']);

// Incluir la conexión a la base de datos desde el archivo central del plugin
require_once __DIR__ . '/../conexion.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Preparar y ejecutar la consulta para obtener localidades del municipio
$stmt = $conn->prepare("SELECT id_localidad, nombre, latitud, longitud FROM LOCALIDADES WHERE id_municipio = ?");
$stmt->bind_param("i", $id_municipio);
$stmt->execute();
$res = $stmt->get_result();

// Recoger los resultados en un array
$localidades = [];
while ($row = $res->fetch_assoc()) {
    $localidades[] = $row;
}

// Devolver los datos como JSON
echo json_encode($localidades);
exit;
?>
