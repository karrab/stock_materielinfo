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
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Construction de la requête
$sql = "SELECT id, code_article, designation, qte_disponible, stock_min, stock_max
        FROM articles
        WHERE actif = 1";

$params = [];

if (!empty($search)) {
    $sql .= " AND (code_article LIKE :search OR designation LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

$sql .= " ORDER BY designation ASC LIMIT :limit OFFSET :offset";

$stmt = $db->getConnection()->prepare($sql);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$items = $stmt->fetchAll();

// Vérifier s'il y a plus de résultats
$total_sql = "SELECT COUNT(*) as total FROM articles WHERE actif = 1";
if (!empty($search)) {
    $total_sql .= " AND (code_article LIKE :search OR designation LIKE :search)";
}

$stmt_total = $db->getConnection()->prepare($total_sql);
if (!empty($search)) {
    $stmt_total->bindValue(':search', '%' . $search . '%');
}
$stmt_total->execute();
$total = $stmt_total->fetch()['total'];

$more = ($offset + $per_page) < $total;

echo json_encode([
    'items' => $items,
    'more' => $more,
    'total' => $total
]);
