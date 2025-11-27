<?php
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('inventaires', 'delete');
$db = Database::getInstance();
$id = $_GET['id'] ?? 0;

$db->prepare("SELECT * FROM inventaires WHERE id = :id AND etat = 'en_cours'");
$db->bind(':id', $id);
$inventaire = $db->fetch();

if (!$inventaire) {
    $_SESSION['error'] = 'Inventaire introuvable ou non supprimable (état non "En cours").';
    header('Location: ' . BASE_URL . '/pages/inventaires/index.php');
    exit;
}

try {
    $db->beginTransaction();

    // Supprimer les lignes
    $db->prepare("DELETE FROM ligne_inventaires WHERE inventaire_id = :id");
    $db->bind(':id', $id);
    $db->execute();

    // Supprimer l'inventaire
    $db->prepare("DELETE FROM inventaires WHERE id = :id");
    $db->bind(':id', $id);
    $db->execute();

    $auth->logTrace($auth->getUserId(), 'inventaires', 'delete', 'inventaires', $id, "Suppression: {$inventaire['reference']}");

    $db->commit();
    $_SESSION['success'] = 'Inventaire supprimé avec succès.';

} catch (Exception $e) {
    $db->rollback();
    $_SESSION['error'] = 'Erreur: ' . $e->getMessage();
}

header('Location: ' . BASE_URL . '/pages/inventaires/index.php');
exit;
