<?php
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

$db = Database::getInstance();

$search = $_GET['q'] ?? '';
$service_id = $_GET['service_id'] ?? null;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Construction de la requête
$sql = "SELECT e.id, e.matricule, e.nom, e.prenom, e.service_id, s.nom as service_nom
        FROM employes e
        INNER JOIN services s ON e.service_id = s.id
        WHERE e.actif = 1";

$params = [];

if (!empty($search)) {
    $sql .= " AND (e.matricule LIKE :search OR e.nom LIKE :search OR e.prenom LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($service_id)) {
    $sql .= " AND e.service_id = :service_id";
    $params[':service_id'] = $service_id;
}

$sql .= " ORDER BY e.nom ASC, e.prenom ASC LIMIT :limit OFFSET :offset";

$stmt = $db->getConnection()->prepare($sql);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$items = $stmt->fetchAll();

// Vérifier s'il y a plus de résultats
$total_sql = "SELECT COUNT(*) as total FROM employes WHERE actif = 1";
$total_params = [];

if (!empty($search)) {
    $total_sql .= " AND (matricule LIKE :search OR nom LIKE :search OR prenom LIKE :search)";
    $total_params[':search'] = '%' . $search . '%';
}

if (!empty($service_id)) {
    $total_sql .= " AND service_id = :service_id";
    $total_params[':service_id'] = $service_id;
}

$stmt_total = $db->getConnection()->prepare($total_sql);
foreach ($total_params as $key => $value) {
    $stmt_total->bindValue($key, $value);
}
$stmt_total->execute();
$total = $stmt_total->fetch()['total'];

$more = ($offset + $per_page) < $total;

echo json_encode([
    'items' => $items,
    'more' => $more,
    'total' => $total
]);
