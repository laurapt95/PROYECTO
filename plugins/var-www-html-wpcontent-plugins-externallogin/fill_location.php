<?php
header('Content-Type: application/json');

$conn = new mysqli('10.33.4.23', 'preventuser', 'preventpass', 'eventosdb');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$id_localidad = isset($_GET['id_localidad']) ? intval($_GET['id_localidad']) : 0;
if (!$id_localidad) {
    echo json_encode([]);
    exit;
}

// Obtener MUNICIPIO desde LOCALIDAD
$res = $conn->query("SELECT id_municipio FROM LOCALIDADES WHERE id_localidad = $id_localidad");
$loc = $res->fetch_assoc();
$id_municipio = $loc['id_municipio'];

// Obtener PROVINCIA desde MUNICIPIO
$res = $conn->query("SELECT id_provincia FROM MUNICIPIOS WHERE id_municipio = $id_municipio");
$mun = $res->fetch_assoc();
$id_provincia = $mun['id_provincia'];

// Obtener CCAA desde PROVINCIA
$res = $conn->query("SELECT id_ccaa FROM PROVINCIAS WHERE id_provincia = $id_provincia");
$prov = $res->fetch_assoc();
$id_ccaa = $prov['id_ccaa'];

// Función para devolver listas
function fetch_list($conn, $table, $id_field, $name_field, $where = '') {
    $list = [];
    $query = "SELECT $id_field AS id, $name_field AS nombre FROM $table $where ORDER BY $name_field";
    $res = $conn->query($query);
    while ($row = $res->fetch_assoc()) {
        $list[] = $row;
    }
    return $list;
}

// Devolver estructura
echo json_encode([
    'ccaa_list'       => fetch_list($conn, 'CCAA', 'id_ccaa', 'nombre', 'WHERE id_pais = 1'),
    'ccaa_selected'   => $id_ccaa,
    'provincia_list'  => fetch_list($conn, 'PROVINCIAS', 'id_provincia', 'nombre', "WHERE id_ccaa = $id_ccaa"),
    'provincia_selected' => $id_provincia,
    'municipio_list'  => fetch_list($conn, 'MUNICIPIOS', 'id_municipio', 'nombre', "WHERE id_provincia = $id_provincia"),
    'municipio_selected' => $id_municipio,
    'localidad_list'  => fetch_list($conn, 'LOCALIDADES', 'id_localidad', 'nombre', "WHERE id_municipio = $id_municipio"),
    'localidad_selected' => $id_localidad
]);
?>
