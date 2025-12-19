<?php
/**
 * Réinitialisation d'un inventaire (retour en état "en_cours")
 */

require_once __DIR__ . '/../../config/config.php';

$auth = new Auth();

if (!$auth->isAdmin()) {
    $_SESSION['error'] = 'Seuls les administrateurs peuvent réinitialiser un inventaire.';
    header('Location: ' . BASE_URL . '/pages/inventaires/index.php');
    exit;
}

$db = Database::getInstance();
$id = intval($_GET['id'] ?? 0);

$db->prepare("SELECT * FROM inventaires WHERE id = :id");
$db->bind(':id', $id);
$inventaire = $db->fetch();

if (!$inventaire) {
    $_SESSION['error'] = 'Inventaire introuvable.';
    header('Location: ' . BASE_URL . '/pages/inventaires/index.php');
    exit;
}

if ($inventaire['etat'] === 'en_cours') {
    $_SESSION['error'] = 'Cet inventaire est déjà en cours.';
    header('Location: ' . BASE_URL . '/pages/inventaires/view.php?id=' . $id);
    exit;
}

// Traitement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $sql = "UPDATE inventaires 
                SET etat = 'en_cours',
                    user_validation_id = NULL,
                    date_validation = NULL
                WHERE id = :id";
        
        $conn = $db->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        $_SESSION['success'] = "Inventaire {$inventaire['reference']} réinitialisé en \"En cours\".";
        header('Location: ' . BASE_URL . '/pages/inventaires/view.php?id=' . $id);
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error'] = 'Erreur : ' . $e->getMessage();
    }
}

$page_title = 'Réinitialiser inventaire';
require_once __DIR__ . '/../../includes/header.php';
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-arrow-counterclockwise"></i> Réinitialiser un inventaire</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/inventaires/index.php">Inventaires</a></li>
                    <li class="breadcrumb-item active">Réinitialiser</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 offset-lg-3">
            <div class="card border-warning">
                <div class="card-header bg-warning">
                    <i class="bi bi-arrow-counterclockwise"></i> Confirmation de réinitialisation
                </div>
                <div class="card-body">
                    <p>Inventaire à réinitialiser :</p>

                    <div class="card bg-light">
                        <div class="card-body">
                            <h5><i class="bi bi-clipboard"></i> <?php echo htmlspecialchars($inventaire['reference']); ?></h5>
                            <p class="mb-1"><strong>Date :</strong> <?php echo date('d/m/Y', strtotime($inventaire['date'])); ?></p>
                            <p class="mb-0"><strong>État actuel :</strong> 
                                <span class="badge <?php echo $inventaire['etat'] == 'valide' ? 'bg-info' : 'bg-success'; ?>">
                                    <?php echo $inventaire['etat'] == 'valide' ? 'Validé' : 'Clôturé'; ?>
                                </span>
                            </p>
                        </div>
                    </div>

                    <div class="alert alert-info mt-3">
                        <strong>Après réinitialisation :</strong>
                        <ul class="mb-0">
                            <li>L'inventaire repassera en état "En cours"</li>
                            <li>Les quantités physiques pourront être modifiées</li>
                            <li>La validation sera supprimée</li>
                        </ul>
                    </div>

                    <form method="POST">
                        <div class="d-flex justify-content-between mt-4">
                            <a href="<?php echo BASE_URL; ?>/pages/inventaires/view.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Annuler
                            </a>
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-arrow-counterclockwise"></i> Réinitialiser
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
