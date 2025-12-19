<?php
/**
 * Création d'un nouvel inventaire avec génération automatique de référence
 */

$page_title = 'Créer un inventaire';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('inventaires', 'create');
$db = Database::getInstance();

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'] ?? date('Y-m-d');
    $equipe_id = !empty($_POST['equipe_id']) ? intval($_POST['equipe_id']) : null;
   
    try {
        $conn = $db->getConnection();
        $conn->beginTransaction();
        
        // Générer la référence automatique INV-YEAR-NUMBER
        $year = date('Y', strtotime($date));
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM inventaires WHERE YEAR(date) = :year");
        $stmt->execute([':year' => $year]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        $numero = str_pad($count + 1, 3, '0', STR_PAD_LEFT);
        $reference = "INV-{$year}-{$numero}";
        
        // Insérer l'inventaire
        $sql = "INSERT INTO inventaires (reference, date, equipe_id, etat, user_id, created_at)
                VALUES (:reference, :date, :equipe_id, 'en_cours', :user_id, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':reference' => $reference,
            ':date' => $date,
            ':equipe_id' => $equipe_id,
            ':user_id' => $auth->getUserId()
        ]);
        
        $inventaire_id = $conn->lastInsertId();
        
        // Créer les lignes d'inventaire pour tous les articles actifs
        $sql_articles = "SELECT id, reference, designation, qte_disponible
                        FROM articles
                        WHERE actif = 1
                        ORDER BY reference";
        
        $stmt_articles = $conn->query($sql_articles);
        $articles = $stmt_articles->fetchAll(PDO::FETCH_ASSOC);
        
        $sql_ligne = "INSERT INTO ligne_inventaires 
                     (inventaire_id, article_id, code_article, designation, qte_theorique, qte_physique, ecart)
                      VALUES (:inv_id, :art_id, :code, :design, :qte_theo, 0, :qte_theo * -1)";
        
        $stmt_ligne = $conn->prepare($sql_ligne);
        
        foreach ($articles as $article) {
            $stmt_ligne->execute([
                ':inv_id' => $inventaire_id,
                ':art_id' => $article['id'],
                ':code' => $article['reference'],
                ':design' => $article['designation'],
                ':qte_theo' => $article['qte_disponible']
            ]);
        }
        
        $conn->commit();
        
        $_SESSION['success'] = "Inventaire {$reference} créé avec succès ! " . count($articles) . " articles ajoutés.";
        header('Location: ' . BASE_URL . '/pages/inventaires/view.php?id=' . $inventaire_id);
        exit;
        
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = 'Erreur lors de la création : ' . $e->getMessage();
    }
}

// Récupérer les équipes
$stmt_equipes = $db->getConnection()->query("SELECT id, nom FROM equipes_inventaire ORDER BY nom");
$equipes = $stmt_equipes->fetchAll(PDO::FETCH_ASSOC);

// Compter les articles actifs
$stmt_count = $db->getConnection()->query("SELECT COUNT(*) as count FROM articles WHERE actif = 1");
$nb_articles = $stmt_count->fetch(PDO::FETCH_ASSOC)['count'];
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-plus-circle"></i> Créer un inventaire</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/inventaires/index.php">Inventaires</a></li>
                    <li class="breadcrumb-item active">Créer</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 offset-lg-2">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-clipboard-plus"></i> Nouvel inventaire
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        <strong>Information :</strong> Un inventaire sera créé avec <?php echo $nb_articles; ?> article(s) actif(s).
                        La référence sera générée automatiquement au format <strong>INV-ANNÉE-NUMÉRO</strong>.
                    </div>

                    <form method="POST" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="date" class="form-label">Date de l'inventaire <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="date" name="date"
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="equipe_id" class="form-label">Équipe d'inventaire</label>
                            <select class="form-select" id="equipe_id" name="equipe_id">
                                <option value="">Aucune équipe</option>
                                <?php foreach ($equipes as $equipe): ?>
                                    <option value="<?php echo $equipe['id']; ?>">
                                        <?php echo htmlspecialchars($equipe['nom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Optionnel - Sélectionnez l'équipe qui effectuera l'inventaire</div>
                        </div>

                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Important :</strong>
                            <ul class="mb-0 mt-2">
                                <li>L'inventaire sera créé en état "En cours"</li>
                                <li>Tous les articles actifs seront ajoutés automatiquement</li>
                                <li>Les quantités physiques seront à 0 par défaut</li>
                                <li>Vous pourrez les saisir sur la page de détail</li>
                            </ul>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="<?php echo BASE_URL; ?>/pages/inventaires/index.php" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Annuler
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Créer l'inventaire
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
