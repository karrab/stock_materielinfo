<?php
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('inventaires', 'update');
$db = Database::getInstance();
$id = $_GET['id'] ?? 0;

$db->prepare("SELECT * FROM inventaires WHERE id = :id AND etat = 'en_cours'");
$db->bind(':id', $id);
$inventaire = $db->fetch();

if (!$inventaire) {
    $_SESSION['error'] = 'Inventaire introuvable ou déjà validé.';
    header('Location: ' . BASE_URL . '/pages/inventaires/index.php');
    exit;
}

try {
    // Mettre à jour les écarts
    $sql = "UPDATE ligne_inventaires
            SET ecart = qte_physique - qte_theorique
            WHERE inventaire_id = :id";

    $db->prepare($sql);
    $db->bind(':id', $id);
    $db->execute();

    $auth->logTrace($auth->getUserId(), 'inventaires', 'update', 'inventaires', $id, "Génération écarts");
    $_SESSION['success'] = 'Écarts générés avec succès.';

} catch (Exception $e) {
    $_SESSION['error'] = 'Erreur: ' . $e->getMessage();
}

header('Location: ' . BASE_URL . '/pages/inventaires/view.php?id=' . $id);
exit;
