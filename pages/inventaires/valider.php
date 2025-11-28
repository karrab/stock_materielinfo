<?php
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('inventaires', 'update');
$db = Database::getInstance();
$id = $_GET['id'] ?? 0;

// Vérifier que l'inventaire existe et est en cours
$db->prepare("SELECT * FROM inventaires WHERE id = :id AND etat = 'en_cours'");
$db->bind(':id', $id);
$inventaire = $db->fetch();

if (!$inventaire) {
    $_SESSION['error'] = 'Inventaire introuvable ou déjà validé/clôturé.';
    header('Location: ' . BASE_URL . '/pages/inventaires/index.php');
    exit;
}

try {
    // Passer l'inventaire en état "validé"
    // date_fin = date de validation
    // user_validation_id = utilisateur qui valide
    // date_validation = horodatage de validation
    $sql = "UPDATE inventaires SET
                etat = 'valide',
                date_fin = CURDATE(),
                user_validation_id = :user_id,
                date_validation = NOW(),
                updated_at = NOW()
            WHERE id = :id";

    $db->prepare($sql);
    $db->bind(':id', $id);
    $db->bind(':user_id', $auth->getUserId());
    $db->execute();

    $auth->logTrace(
        $auth->getUserId(),
        'inventaires',
        'valider',
        'inventaires',
        $id,
        "Validation inventaire: " . $inventaire['reference']
    );

    $_SESSION['success'] = 'Inventaire validé avec succès ! Il ne peut plus être modifié. Seul un administrateur peut le clôturer.';

} catch (Exception $e) {
    $_SESSION['error'] = 'Erreur lors de la validation: ' . $e->getMessage();
}

header('Location: ' . BASE_URL . '/pages/inventaires/view.php?id=' . $id);
exit;
