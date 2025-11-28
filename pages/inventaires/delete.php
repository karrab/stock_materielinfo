<?php
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('inventaires', 'delete');
$db = Database::getInstance();
$id = $_GET['id'] ?? 0;

// Seuls les inventaires "en_cours" peuvent être supprimés
$db->prepare("SELECT * FROM inventaires WHERE id = :id AND etat = 'en_cours'");
$db->bind(':id', $id);
$inventaire = $db->fetch();

if (!$inventaire) {
    $_SESSION['error'] = 'Inventaire introuvable ou non supprimable (seuls les inventaires "En cours" peuvent être supprimés).';
    header('Location: ' . BASE_URL . '/pages/inventaires/index.php');
    exit;
}

try {
    $db->beginTransaction();

    // Supprimer d'abord les lignes d'inventaire
    $db->prepare("DELETE FROM ligne_inventaires WHERE inventaire_id = :id");
    $db->bind(':id', $id);
    $db->execute();

    // Puis supprimer l'inventaire lui-même
    $db->prepare("DELETE FROM inventaires WHERE id = :id");
    $db->bind(':id', $id);
    $db->execute();

    $auth->logTrace(
        $auth->getUserId(),
        'inventaires',
        'delete',
        'inventaires',
        $id,
        "Suppression inventaire: {$inventaire['reference']}"
    );

    $db->commit();
    $_SESSION['success'] = "Inventaire \"{$inventaire['reference']}\" supprimé avec succès ainsi que toutes ses lignes.";

} catch (Exception $e) {
    $db->rollback();
    $_SESSION['error'] = 'Erreur lors de la suppression: ' . $e->getMessage();
}

header('Location: ' . BASE_URL . '/pages/inventaires/index.php');
exit;
