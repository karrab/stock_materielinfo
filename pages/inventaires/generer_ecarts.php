<?php
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('inventaires', 'update');
$db = Database::getInstance();
$id = $_GET['id'] ?? 0;

// Vérifier que l'inventaire existe et est en cours
$db->prepare("SELECT * FROM inventaires WHERE id = :id AND etat = 'en_cours'");
$db->bind(':id', $id);
$inventaire = $db->fetch();

if (!$inventaire) {
    $_SESSION['error'] = 'Inventaire introuvable ou non modifiable (état non "En cours").';
    header('Location: ' . BASE_URL . '/pages/inventaires/index.php');
    exit;
}

try {
    // Calculer les écarts: ecart = qte_physique - qte_theorique
    $sql = "UPDATE ligne_inventaires
            SET ecart = qte_physique - qte_theorique
            WHERE inventaire_id = :id";

    $db->prepare($sql);
    $db->bind(':id', $id);
    $db->execute();

    // Compter les écarts
    $db->prepare("SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN ecart > 0 THEN 1 ELSE 0 END) as positifs,
                    SUM(CASE WHEN ecart < 0 THEN 1 ELSE 0 END) as negatifs,
                    SUM(CASE WHEN ecart = 0 THEN 1 ELSE 0 END) as ok
                  FROM ligne_inventaires
                  WHERE inventaire_id = :id");
    $db->bind(':id', $id);
    $stats = $db->fetch();

    $auth->logTrace(
        $auth->getUserId(),
        'inventaires',
        'generer_ecarts',
        'inventaires',
        $id,
        "Génération des écarts pour: " . $inventaire['reference']
    );

    $_SESSION['success'] = "Écarts calculés avec succès ! " .
                           "{$stats['positifs']} excédent(s), " .
                           "{$stats['negatifs']} manquant(s), " .
                           "{$stats['ok']} conforme(s).";

} catch (Exception $e) {
    $_SESSION['error'] = 'Erreur lors du calcul des écarts: ' . $e->getMessage();
}

header('Location: ' . BASE_URL . '/pages/inventaires/view.php?id=' . $id);
exit;
