<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/Auth.php';

header('Content-Type: application/json');

// Vérifier que la requête est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

// Vérifier l'authentification
session_start();
$auth = Auth::getInstance();
if (!$auth->isLoggedIn() || !$auth->hasPermission('inventaires', 'update')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Permission refusée']);
    exit;
}

$ligne_id = intval($_POST['ligne_id'] ?? 0);
$qte_physique = floatval($_POST['qte_physique'] ?? 0);

// Validation
if ($ligne_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de ligne invalide']);
    exit;
}

if ($qte_physique < 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Quantité invalide (doit être >= 0)']);
    exit;
}

try {
    $db = Database::getInstance();

    // Vérifier que la ligne appartient à un inventaire en cours
    $db->prepare("SELECT i.etat
                  FROM ligne_inventaires li
                  INNER JOIN inventaires i ON li.inventaire_id = i.id
                  WHERE li.id = :ligne_id");
    $db->bind(':ligne_id', $ligne_id);
    $ligne = $db->fetch();

    if (!$ligne) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Ligne d\'inventaire introuvable']);
        exit;
    }

    if ($ligne['etat'] !== 'en_cours') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Inventaire non modifiable (état: ' . $ligne['etat'] . ')']);
        exit;
    }

    // Mettre à jour la quantité physique
    $sql = "UPDATE ligne_inventaires
            SET qte_physique = :qte
            WHERE id = :id";

    $db->prepare($sql);
    $db->bind(':qte', $qte_physique);
    $db->bind(':id', $ligne_id);

    if ($db->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Quantité mise à jour',
            'qte_physique' => $qte_physique
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erreur lors de la mise à jour']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
