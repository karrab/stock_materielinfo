<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/Auth.php';

// Démarrer le buffering pour éviter tout output avant le JSON
ob_start();

header('Content-Type: application/json');

// Log pour debugging (dans un fichier séparé)
$log_file = __DIR__ . '/../../logs/qte_physique_debug.log';
$log_entry = date('Y-m-d H:i:s') . " - POST data: " . json_encode($_POST) . "\n";
@file_put_contents($log_file, $log_entry, FILE_APPEND);

// Vérifier que la requête est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

// Vérifier l'authentification (session déjà démarrée par config.php)
$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasPermission('inventaires', 'update')) {
    ob_end_clean();
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Permission refusée']);
    exit;
}

$ligne_id = intval($_POST['ligne_id'] ?? 0);
$qte_physique = floatval($_POST['qte_physique'] ?? 0);

// Log les valeurs reçues
$log_entry = date('Y-m-d H:i:s') . " - Traitement: ligne_id=$ligne_id, qte_physique=$qte_physique\n";
@file_put_contents($log_file, $log_entry, FILE_APPEND);

// Validation
if ($ligne_id <= 0) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de ligne invalide']);
    exit;
}

if ($qte_physique < 0) {
    ob_end_clean();
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
        $log_entry = date('Y-m-d H:i:s') . " - ERREUR: Ligne introuvable (ID: $ligne_id)\n";
        @file_put_contents($log_file, $log_entry, FILE_APPEND);
        ob_end_clean();
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Ligne d\'inventaire introuvable']);
        exit;
    }

    if ($ligne['etat'] !== 'en_cours') {
        $log_entry = date('Y-m-d H:i:s') . " - ERREUR: Inventaire non modifiable (état: {$ligne['etat']})\n";
        @file_put_contents($log_file, $log_entry, FILE_APPEND);
        ob_end_clean();
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Inventaire non modifiable (état: ' . $ligne['etat'] . ')']);
        exit;
    }

    // Calculer l'écart
    $qte_theorique = floatval($ligne['qte_theorique']);
    $ecart = $qte_physique - $qte_theorique;

    $log_entry = date('Y-m-d H:i:s') . " - Calcul: qte_theorique=$qte_theorique, ecart=$ecart\n";
    @file_put_contents($log_file, $log_entry, FILE_APPEND);

    // Mettre à jour la quantité physique ET l'écart
    $sql_update = "UPDATE ligne_inventaires
                   SET qte_physique = :qte_physique,
                       ecart = :ecart
                   WHERE id = :ligne_id";

    $stmt_update = $db->getConnection()->prepare($sql_update);
    $stmt_update->bindValue(':qte_physique', $qte_physique, PDO::PARAM_STR);
    $stmt_update->bindValue(':ecart', $ecart, PDO::PARAM_STR);
    $stmt_update->bindValue(':ligne_id', $ligne_id, PDO::PARAM_INT);

    $log_entry = date('Y-m-d H:i:s') . " - Exécution UPDATE...\n";
    @file_put_contents($log_file, $log_entry, FILE_APPEND);

    if ($stmt_update->execute()) {
        $rows_affected = $stmt_update->rowCount();
        $log_entry = date('Y-m-d H:i:s') . " - SUCCÈS: $rows_affected ligne(s) mise(s) à jour\n";
        @file_put_contents($log_file, $log_entry, FILE_APPEND);

        ob_end_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Quantité mise à jour et écart recalculé',
            'qte_physique' => $qte_physique,
            'qte_theorique' => $qte_theorique,
            'ecart' => $ecart,
            'rows_affected' => $rows_affected
        ]);
    } else {
        $errorInfo = $stmt_update->errorInfo();
        $log_entry = date('Y-m-d H:i:s') . " - ERREUR SQL: " . json_encode($errorInfo) . "\n";
        @file_put_contents($log_file, $log_entry, FILE_APPEND);

        ob_end_clean();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erreur lors de la mise à jour',
            'debug' => $errorInfo[2] ?? 'Unknown error',
            'sql_state' => $errorInfo[0] ?? ''
        ]);
    }

} catch (Exception $e) {
    $log_entry = date('Y-m-d H:i:s') . " - EXCEPTION: " . $e->getMessage() . "\n";
    @file_put_contents($log_file, $log_entry, FILE_APPEND);

    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
