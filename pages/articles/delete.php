<?php
require_once __DIR__ . '/../../config/config.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$id = $_GET['id'] ?? 0;

// Vérification de l'existence
$db->prepare("SELECT * FROM articles WHERE id = :id");
$db->bind(':id', $id);
$article = $db->fetch();

if (!$article) {
    $_SESSION['error'] = 'Article introuvable.';
    header('Location: ' . BASE_URL . '/pages/articles/index.php');
    exit;
}

// Vérifier si le article est utilisé
$db->prepare("SELECT COUNT(*) as count FROM employes WHERE article_id = :id");
$db->bind(':id', $id);
$nb_employes = $db->fetch()['count'];

$db->prepare("SELECT COUNT(*) as count FROM bureaux WHERE article_id = :id");
$db->bind(':id', $id);
$nb_bureaux = $db->fetch()['count'];

$db->prepare("SELECT COUNT(*) as count FROM sorties WHERE article_id = :id OR article_affectation_id = :id");
$db->bind(':id', $id);
$nb_sorties = $db->fetch()['count'];

if ($nb_employes > 0 || $nb_bureaux > 0 || $nb_sorties > 0) {
    $_SESSION['error'] = 'Impossible de supprimer ce article car il est utilisé (' . $nb_employes . ' employé(s), ' . $nb_bureaux . ' bureau(x), ' . $nb_sorties . ' sortie(s)).';
    header('Location: ' . BASE_URL . '/pages/articles/index.php');
    exit;
}

try {
    $db->prepare("DELETE FROM articles WHERE id = :id");
    $db->bind(':id', $id);

    if ($db->execute()) {
        $auth->logTrace($auth->getUserId(), 'articles', 'delete', 'articles', $id, "Suppression: " . $article['nom']);

        $_SESSION['success'] = 'Article supprimé avec succès.';
    }
} catch (Exception $e) {
    $_SESSION['error'] = 'Erreur lors de la suppression: ' . $e->getMessage();
}

header('Location: ' . BASE_URL . '/pages/articles/index.php');
exit;
