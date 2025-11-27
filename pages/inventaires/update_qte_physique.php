<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$ligne_id = intval($_POST['ligne_id'] ?? 0);
$qte_physique = floatval($_POST['qte_physique'] ?? 0);

try {
    $db = Database::getInstance();
    $sql = "UPDATE ligne_inventaires SET qte_physique = :qte WHERE id = :id";
    $db->prepare($sql);
    $db->bind(':qte', $qte_physique);
    $db->bind(':id', $ligne_id);

    if ($db->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erreur lors de la mise à jour']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
