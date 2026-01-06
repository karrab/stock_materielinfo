<?php
require_once __DIR__ . '/../../config/config.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$retourFournisseur = new RetourFournisseur();
$id = $_GET['id'] ?? 0;

// Vérifier existence
$retour = $retourFournisseur->getById($id);

if (!$retour) {
    $_SESSION['error'] = 'Retour fournisseur introuvable.';
    header('Location: ' . BASE_URL . '/pages/retour_fournisseur/index.php');
    exit;
}

try {
    $retourFournisseur->delete($id);

    $auth->logTrace($auth->getUserId(), 'retour_fournisseur', 'delete', 'retour_fournisseur', $id, "Suppression retour fournisseur #" . $id);

    $_SESSION['success'] = 'Retour fournisseur supprimé avec succès.';
} catch (Exception $e) {
    $_SESSION['error'] = 'Erreur lors de la suppression: ' . $e->getMessage();
}

header('Location: ' . BASE_URL . '/pages/retour_fournisseur/index.php');
exit;
