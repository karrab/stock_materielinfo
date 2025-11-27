<?php
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('inventaires', 'update');
$db = Database::getInstance();
$id = $_GET['id'] ?? 0;

$db->prepare("SELECT * FROM inventaires WHERE id = :id AND etat = 'en_cours'");
$db->bind(':id', $id);
$inventaire = $db->fetch();

if (!$inventaire) {
    $_SESSION['error'] = 'Inventaire introuvable ou état invalide.';
    header('Location: ' . BASE_URL . '/pages/inventaires/index.php');
    exit;
}

try {
    $sql = "UPDATE inventaires SET etat = 'valide', date_fin = NOW() WHERE id = :id";
    $db->prepare($sql);
    $db->bind(':id', $id);
    $db->execute();

    $auth->logTrace($auth->getUserId(), 'inventaires', 'update', 'inventaires', $id, "Validation inventaire");
    $_SESSION['success'] = 'Inventaire validé avec succès.';

} catch (Exception $e) {
    $_SESSION['error'] = 'Erreur: ' . $e->getMessage();
}

header('Location: ' . BASE_URL . '/pages/inventaires/view.php?id=' . $id);
exit;
