<?php
require_once __DIR__ . '/../../includes/header.php';

// Vérifier que l'utilisateur est administrateur
$auth->requireAdmin();
$db = Database::getInstance();
$id = $_GET['id'] ?? 0;

// Vérifier que l'inventaire existe et est validé (pas en_cours ni clôturé)
$db->prepare("SELECT * FROM inventaires WHERE id = :id AND etat = 'valide'");
$db->bind(':id', $id);
$inventaire = $db->fetch();

if (!$inventaire) {
    $_SESSION['error'] = 'Inventaire introuvable ou état invalide. Seuls les inventaires validés peuvent être réinitialisés.';
    header('Location: ' . BASE_URL . '/pages/inventaires/index.php');
    exit;
}

try {
    // Réinitialiser l'inventaire en "En cours"
    // Effacer les informations de validation et date_fin
    $sql = "UPDATE inventaires SET
                etat = 'en_cours',
                date_fin = NULL,
                user_validation_id = NULL,
                date_validation = NULL,
                updated_at = NOW()
            WHERE id = :id";

    $db->prepare($sql);
    $db->bind(':id', $id);
    $db->execute();

    $auth->logTrace(
        $auth->getUserId(),
        'inventaires',
        'reinitialiser',
        'inventaires',
        $id,
        "Réinitialisation inventaire: " . $inventaire['reference']
    );

    $_SESSION['success'] = 'Inventaire réinitialisé en état "En cours". Il peut à nouveau être modifié.';

} catch (Exception $e) {
    $_SESSION['error'] = 'Erreur lors de la réinitialisation: ' . $e->getMessage();
}

header('Location: ' . BASE_URL . '/pages/inventaires/view.php?id=' . $id);
exit;
