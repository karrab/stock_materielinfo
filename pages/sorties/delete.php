<?php
require_once __DIR__ . '/../../config/config.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$id = $_GET['id'] ?? 0;

// Vérification de l'existence
$db->prepare("SELECT * FROM sorties WHERE id = :id");
$db->bind(':id', $id);
$sortie = $db->fetch();

if (!$sortie) {
    $_SESSION['error'] = 'Sortie introuvable.';
    header('Location: ' . BASE_URL . '/pages/sorties/index.php');
    exit;
}

try {
    $db->beginTransaction();

    // Récupérer les lignes de sortie pour restaurer les stocks
    $db->prepare("SELECT article_id, qte_sortie FROM ligne_sorties WHERE sortie_id = :id");
    $db->bind(':id', $id);
    $db->execute();
    $lignes = $db->fetchAll();

    // Restaurer les stocks
    foreach ($lignes as $ligne) {
        $sql = "UPDATE articles
                SET qte_sortie = qte_sortie - :qte,
                    qte_disponible = qte_disponible + :qte
                WHERE id = :article_id";

        $db->prepare($sql);
        $db->bind(':qte', $ligne['qte_sortie']);
        $db->bind(':article_id', $ligne['article_id']);
        $db->execute();
    }

    // Supprimer les mouvements de l'historique
    $historique = new HistoriqueArticle();
    $historique->supprimerParSortie($id);

    // Supprimer les lignes de sortie
    $db->prepare("DELETE FROM ligne_sorties WHERE sortie_id = :id");
    $db->bind(':id', $id);
    $db->execute();

    // Supprimer le fichier joint si existe
    if (!empty($sortie['fichier']) && file_exists(UPLOAD_SORTIES_PATH . '/' . $sortie['fichier'])) {
        unlink(UPLOAD_SORTIES_PATH . '/' . $sortie['fichier']);
    }

    // Supprimer la sortie
    $db->prepare("DELETE FROM sorties WHERE id = :id");
    $db->bind(':id', $id);
    $db->execute();

    // Log de la trace
    $auth->logTrace($auth->getUserId(), 'sorties', 'delete', 'sorties', $id, "Suppression sortie #" . $id);

    $db->commit();

    $_SESSION['success'] = 'Sortie supprimée avec succès.';
} catch (Exception $e) {
    $db->rollback();
    $_SESSION['error'] = 'Erreur lors de la suppression: ' . $e->getMessage();
}

header('Location: ' . BASE_URL . '/pages/sorties/index.php');
exit;
