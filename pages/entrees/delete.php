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

try {
    $db->beginTransaction();

    // Récupérer les lignes d'entrée pour restaurer les stocks
    $db->prepare("SELECT article_id, qte_entree FROM ligne_entrees WHERE entree_id = :id");
    $db->bind(':id', $id);
    $db->execute();
    $lignes = $db->fetchAll();

    // Restaurer les stocks
    foreach ($lignes as $ligne) {
        $sql = "UPDATE articles
                SET qte_entree = qte_entree - :qte,
                    qte_disponible = qte_disponible - :qte
                WHERE id = :article_id";

        $db->prepare($sql);
        $db->bind(':qte', $ligne['qte_entree']);
        $db->bind(':article_id', $ligne['article_id']);
        $db->execute();
    }

    // Supprimer les mouvements de l'historique
    $historique = new HistoriqueArticle();
    $historique->supprimerParEntree($id);

    // Supprimer les lignes d'entrée
    $db->prepare("DELETE FROM ligne_entrees WHERE entree_id = :id");
    $db->bind(':id', $id);
    $db->execute();

    // Supprimer le fichier joint si existe
    if (!empty($entree['fichier']) && file_exists(UPLOAD_ENTREES_PATH . '/' . $entree['fichier'])) {
        unlink(UPLOAD_ENTREES_PATH . '/' . $entree['fichier']);
    }

    // Supprimer l'entrée
    $db->prepare("DELETE FROM entrees WHERE id = :id");
    $db->bind(':id', $id);
    $db->execute();

    // Log de la trace
    $auth->logTrace($auth->getUserId(), 'entrees', 'delete', 'entrees', $id, "Suppression entrée #" . $id);

    $db->commit();

    $_SESSION['success'] = 'Entrée supprimée avec succès.';
} catch (Exception $e) {
    $db->rollback();
    $_SESSION['error'] = 'Erreur lors de la suppression: ' . $e->getMessage();
}

header('Location: ' . BASE_URL . '/pages/entrees/index.php');
exit;
