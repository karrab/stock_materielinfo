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

// Vérifier l'authentification (session déjà démarrée par config.php)
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
    $sql_check = "SELECT i.etat, li.qte_theorique
                  FROM ligne_inventaires li
                  INNER JOIN inventaires i ON li.inventaire_id = i.id
                  WHERE li.id = :ligne_id";

    $stmt = $db->getConnection()->prepare($sql_check);
    $stmt->bindValue(':ligne_id', $ligne_id, PDO::PARAM_INT);
    $stmt->execute();
    $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

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

    // Calculer l'écart
    $qte_theorique = floatval($ligne['qte_theorique']);
    $ecart = $qte_physique - $qte_theorique;

    // Mettre à jour la quantité physique ET l'écart
    $sql_update = "UPDATE ligne_inventaires
                   SET qte_physique = :qte_physique,
                       ecart = :ecart
                   WHERE id = :ligne_id";

    $stmt_update = $db->getConnection()->prepare($sql_update);
    $stmt_update->bindValue(':qte_physique', $qte_physique, PDO::PARAM_STR);
    $stmt_update->bindValue(':ecart', $ecart, PDO::PARAM_STR);
    $stmt_update->bindValue(':ligne_id', $ligne_id, PDO::PARAM_INT);

    if ($stmt_update->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Quantité mise à jour et écart recalculé',
            'qte_physique' => $qte_physique,
            'qte_theorique' => $qte_theorique,
            'ecart' => $ecart
        ]);
    } else {
        $errorInfo = $stmt_update->errorInfo();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erreur lors de la mise à jour',
            'debug' => $errorInfo[2] ?? 'Unknown error'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
