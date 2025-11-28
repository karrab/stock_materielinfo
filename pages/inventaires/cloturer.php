<?php
require_once __DIR__ . '/../../includes/header.php';

// Vérifier que l'utilisateur est administrateur
$auth->requireAdmin();
$db = Database::getInstance();
$id = $_GET['id'] ?? 0;

// Vérifier que l'inventaire existe et est validé (pas en_cours ou déjà clôturé)
$db->prepare("SELECT * FROM inventaires WHERE id = :id AND etat = 'valide'");
$db->bind(':id', $id);
$inventaire = $db->fetch();

if (!$inventaire) {
    $_SESSION['error'] = 'Inventaire introuvable, non validé ou déjà clôturé. Seuls les inventaires validés peuvent être clôturés.';
    header('Location: ' . BASE_URL . '/pages/inventaires/index.php');
    exit;
}

try {
    // Clôturer l'inventaire (état final, définitif)
    // Ne pas modifier date_fin (déjà définie lors de la validation)
    $sql = "UPDATE inventaires SET
                etat = 'cloture',
                updated_at = NOW()
            WHERE id = :id";

    $db->prepare($sql);
    $db->bind(':id', $id);
    $db->execute();

    $auth->logTrace(
        $auth->getUserId(),
        'inventaires',
        'cloturer',
        'inventaires',
        $id,
        "Clôture définitive inventaire: " . $inventaire['reference']
    );

    $_SESSION['success'] = 'Inventaire clôturé avec succès ! Il est maintenant figé et ne peut plus être modifié.';

} catch (Exception $e) {
    $_SESSION['error'] = 'Erreur lors de la clôture: ' . $e->getMessage();
}

header('Location: ' . BASE_URL . '/pages/inventaires/view.php?id=' . $id);
exit;
