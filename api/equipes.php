<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';

header('Content-Type: application/json');

$search = $_GET['search'] ?? '';
$db = Database::getInstance();

$sql = "SELECT id, nom as text FROM equipes_inventaire WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND nom LIKE :search";
    $params[':search'] = '%' . $search . '%';
}

$sql .= " ORDER BY nom ASC LIMIT 20";

$stmt = $db->getConnection()->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($results);
