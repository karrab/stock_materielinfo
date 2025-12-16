<?php
/**
 * Enregistrement des quantités physiques pour un inventaire
 */
require_once __DIR__ . '/../../config/config.php';

$auth = Auth::getInstance();
$auth->requireLogin();
$auth->requirePermission('inventaires', 'update');

$db = Database::getInstance();
$inventaire_id = intval($_GET['id'] ?? 0);

// Vérifier que l'inventaire existe et est en cours
$db->prepare("SELECT * FROM inventaires WHERE id = :id");
$db->bind(':id', $inventaire_id);
$inventaire = $db->fetch();

if (!$inventaire) {
    $_SESSION['error'] = 'Inventaire introuvable.';
    header('Location: ' . BASE_URL . '/pages/inventaires/index.php');
    exit;
}

if ($inventaire['etat'] !== 'en_cours') {
    $_SESSION['error'] = 'Cet inventaire ne peut plus être modifié (état: ' . $inventaire['etat'] . ')';
    header('Location: ' . BASE_URL . '/pages/inventaires/view.php?id=' . $inventaire_id);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qte_physiques = $_POST['qte_physique'] ?? [];
    $ligne_ids = $_POST['ligne_id'] ?? [];

    $errors = [];
    $updates = 0;

    try {
        $db->beginTransaction();

        foreach ($ligne_ids as $index => $ligne_id) {
            $ligne_id = intval($ligne_id);
            $qte_physique = floatval($qte_physiques[$index] ?? 0);

            if ($ligne_id <= 0) continue;
            if ($qte_physique < 0) {
                $errors[] = "Quantité invalide pour la ligne ID $ligne_id";
                continue;
            }

            // Récupérer la quantité théorique
            $db->prepare("SELECT qte_theorique FROM ligne_inventaires WHERE id = :id AND inventaire_id = :inventaire_id");
            $db->bind(':id', $ligne_id);
            $db->bind(':inventaire_id', $inventaire_id);
            $ligne = $db->fetch();

            if (!$ligne) {
                $errors[] = "Ligne ID $ligne_id introuvable";
                continue;
            }

            // Calculer l'écart
            $qte_theorique = floatval($ligne['qte_theorique']);
            $ecart = $qte_physique - $qte_theorique;

            // Mettre à jour la ligne
            $sql = "UPDATE ligne_inventaires
                    SET qte_physique = :qte_physique,
                        ecart = :ecart
                    WHERE id = :id";

            $db->prepare($sql);
            $db->bind(':qte_physique', $qte_physique);
            $db->bind(':ecart', $ecart);
            $db->bind(':id', $ligne_id);

            if ($db->execute()) {
                $updates++;
            }
        }

        if (empty($errors)) {
            $db->commit();
            $_SESSION['success'] = "✓ $updates quantité(s) physique(s) enregistrée(s) avec succès.";
        } else {
            $db->rollback();
            $_SESSION['error'] = 'Erreurs: ' . implode(', ', $errors);
        }

    } catch (Exception $e) {
        $db->rollback();
        $_SESSION['error'] = 'Erreur lors de l\'enregistrement: ' . $e->getMessage();
    }

    header('Location: ' . BASE_URL . '/pages/inventaires/view.php?id=' . $inventaire_id);
    exit;
}

// Si GET, rediriger vers la page de vue
header('Location: ' . BASE_URL . '/pages/inventaires/view.php?id=' . $inventaire_id);
exit;
