<?php
require_once __DIR__ . '/../../config/config.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$id = $_GET['id'] ?? 0;

// Vérification de l'existence
$db->prepare("SELECT * FROM employes WHERE id = :id");
$db->bind(':id', $id);
$employe = $db->fetch();

if (!$employe) {
    $_SESSION['error'] = 'Employé introuvable.';
    header('Location: ' . BASE_URL . '/pages/employes/index.php');
    exit;
}

// Vérifier si le employe est utilisé
$db->prepare("SELECT COUNT(*) as count FROM employes WHERE employe_id = :id");
$db->bind(':id', $id);
$nb_employes = $db->fetch()['count'];

$db->prepare("SELECT COUNT(*) as count FROM bureaux WHERE employe_id = :id");
$db->bind(':id', $id);
$nb_bureaux = $db->fetch()['count'];

$db->prepare("SELECT COUNT(*) as count FROM sorties WHERE employe_id = :id OR employe_affectation_id = :id");
$db->bind(':id', $id);
$nb_sorties = $db->fetch()['count'];

if ($nb_employes > 0 || $nb_bureaux > 0 || $nb_sorties > 0) {
    $_SESSION['error'] = 'Impossible de supprimer ce employe car il est utilisé (' . $nb_employes . ' employé(s), ' . $nb_bureaux . ' bureau(x), ' . $nb_sorties . ' sortie(s)).';
    header('Location: ' . BASE_URL . '/pages/employes/index.php');
    exit;
}

try {
    $db->prepare("DELETE FROM employes WHERE id = :id");
    $db->bind(':id', $id);

    if ($db->execute()) {
        $auth->logTrace($auth->getUserId(), 'employes', 'delete', 'employes', $id, "Suppression: " . $employe['nom']);

        $_SESSION['success'] = 'Employé supprimé avec succès.';
    }
} catch (Exception $e) {
    $_SESSION['error'] = 'Erreur lors de la suppression: ' . $e->getMessage();
}

header('Location: ' . BASE_URL . '/pages/employes/index.php');
exit;
