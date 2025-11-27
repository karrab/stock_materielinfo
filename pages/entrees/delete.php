<?php
require_once __DIR__ . '/../../config/config.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$id = $_GET['id'] ?? 0;

// Vérification de l'existence
$db->prepare("SELECT * FROM entrees WHERE id = :id");
$db->bind(':id', $id);
$entree = $db->fetch();

if (!$entree) {
    $_SESSION['error'] = 'Entrée introuvable.';
    header('Location: ' . BASE_URL . '/pages/entrees/index.php');
    exit;
}

// Vérifier si le entree est utilisé
$db->prepare("SELECT COUNT(*) as count FROM employes WHERE entree_id = :id");
$db->bind(':id', $id);
$nb_employes = $db->fetch()['count'];

$db->prepare("SELECT COUNT(*) as count FROM bureaux WHERE entree_id = :id");
$db->bind(':id', $id);
$nb_bureaux = $db->fetch()['count'];

$db->prepare("SELECT COUNT(*) as count FROM sorties WHERE entree_id = :id OR entree_affectation_id = :id");
$db->bind(':id', $id);
$nb_sorties = $db->fetch()['count'];

if ($nb_employes > 0 || $nb_bureaux > 0 || $nb_sorties > 0) {
    $_SESSION['error'] = 'Impossible de supprimer ce entree car il est utilisé (' . $nb_employes . ' employé(s), ' . $nb_bureaux . ' bureau(x), ' . $nb_sorties . ' sortie(s)).';
    header('Location: ' . BASE_URL . '/pages/entrees/index.php');
    exit;
}

try {
    $db->prepare("DELETE FROM entrees WHERE id = :id");
    $db->bind(':id', $id);

    if ($db->execute()) {
        $auth->logTrace($auth->getUserId(), 'entrees', 'delete', 'entrees', $id, "Suppression: " . $entree['nom']);

        $_SESSION['success'] = 'Entrée supprimé avec succès.';
    }
} catch (Exception $e) {
    $_SESSION['error'] = 'Erreur lors de la suppression: ' . $e->getMessage();
}

header('Location: ' . BASE_URL . '/pages/entrees/index.php');
exit;
