<?php
require_once __DIR__ . '/../../config/config.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$id = $_GET['id'] ?? 0;

// Vérification de l'existence
$db->prepare("SELECT * FROM retours WHERE id = :id");
$db->bind(':id', $id);
$retour = $db->fetch();

if (!$retour) {
    $_SESSION['error'] = 'Retour introuvable.';
    header('Location: ' . BASE_URL . '/pages/retours/index.php');
    exit;
}

try {
    $db->beginTransaction();

    // Récupérer les lignes de retour pour restaurer les stocks
    $db->prepare("SELECT article_id, qte_retour FROM ligne_retours WHERE retour_id = :id");
    $db->bind(':id', $id);
    $db->execute();
    $lignes = $db->fetchAll();

    // Restaurer les stocks (retour augmentait le stock, donc on le diminue)
    foreach ($lignes as $ligne) {
        $sql = "UPDATE articles
                SET qte_retour = qte_retour - :qte,
                    qte_disponible = qte_disponible - :qte
                WHERE id = :article_id";

        $db->prepare($sql);
        $db->bind(':qte', $ligne['qte_retour']);
        $db->bind(':article_id', $ligne['article_id']);
        $db->execute();
    }

    // Supprimer les mouvements de l'historique
    $historique = new HistoriqueArticle();
    $historique->supprimerParRetour($id);

    // Supprimer les lignes de retour
    $db->prepare("DELETE FROM ligne_retours WHERE retour_id = :id");
    $db->bind(':id', $id);
    $db->execute();

    // Supprimer le fichier joint si existe
    if (!empty($retour['fichier']) && file_exists(UPLOAD_RETOURS_PATH . '/' . $retour['fichier'])) {
        unlink(UPLOAD_RETOURS_PATH . '/' . $retour['fichier']);
    }

    // Supprimer le retour
    $db->prepare("DELETE FROM retours WHERE id = :id");
    $db->bind(':id', $id);
    $db->execute();

    // Log de la trace
    $auth->logTrace($auth->getUserId(), 'retours', 'delete', 'retours', $id, "Suppression retour #" . $id);

    $db->commit();

    $_SESSION['success'] = 'Retour supprimé avec succès.';
} catch (Exception $e) {
    $db->rollback();
    $_SESSION['error'] = 'Erreur lors de la suppression: ' . $e->getMessage();
}

header('Location: ' . BASE_URL . '/pages/retours/index.php');
exit;
