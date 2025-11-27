<?php
$page_title = 'Nouvel article';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('articles', 'create');
$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code_article = trim($_POST['code_article'] ?? '');
    $designation = trim($_POST['designation'] ?? '');
    $stock_initial = floatval($_POST['stock_initial'] ?? 0);
    $stock_min = floatval($_POST['stock_min'] ?? 0);
    $stock_max = floatval($_POST['stock_max'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');

    $errors = [];

    if (empty($code_article)) $errors[] = 'Le code article est obligatoire.';
    if (empty($designation)) $errors[] = 'La désignation est obligatoire.';

    // Vérifier unicité code
    $db->prepare("SELECT COUNT(*) as count FROM articles WHERE code_article = :code");
    $db->bind(':code', $code_article);
    if ($db->fetch()['count'] > 0) {
        $errors[] = 'Un article avec ce code existe déjà.';
    }

    if (empty($errors)) {
        try {
            // À la création : qte_disponible = stock_initial
            $sql = "INSERT INTO articles (code_article, designation, stock_initial, qte_disponible, stock_min, stock_max, notes, actif)
                    VALUES (:code, :designation, :stock_initial, :qte_disponible, :stock_min, :stock_max, :notes, 1)";

            $db->prepare($sql);
            $db->bind(':code', $code_article);
            $db->bind(':designation', $designation);
            $db->bind(':stock_initial', $stock_initial);
            $db->bind(':qte_disponible', $stock_initial); // IMPORTANT: qte_disponible = stock_initial
            $db->bind(':stock_min', $stock_min);
            $db->bind(':stock_max', $stock_max);
            $db->bind(':notes', $notes);

            if ($db->execute()) {
                $id = $db->lastInsertId();
                $auth->logTrace($auth->getUserId(), 'articles', 'create', 'articles', $id, "Création: $code_article");
                $_SESSION['success'] = 'Article créé avec succès.';
                header('Location: ' . BASE_URL . '/pages/articles/index.php');
                exit;
            }
        } catch (Exception $e) {
            $errors[] = 'Erreur: ' . $e->getMessage();
        }
    }
}
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-box-seam"></i> Nouvel article</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/articles/index.php">Articles</a></li>
                    <li class="breadcrumb-item active">Nouveau</li>
                </ol>
            </nav>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo $error; ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-header"><i class="bi bi-info-circle"></i> Informations générales</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="code_article" class="form-label required">Code article</label>
                                <input type="text" class="form-control" id="code_article" name="code_article" required
                                       value="<?php echo htmlspecialchars($code_article ?? ''); ?>" placeholder="Ex: PC-001">
                                <small class="text-muted">Code unique pour identifier l'article</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="designation" class="form-label required">Désignation</label>
                                <input type="text" class="form-control" id="designation" name="designation" required
                                       value="<?php echo htmlspecialchars($designation ?? ''); ?>" placeholder="Ex: Ordinateur portable Dell">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes / Description</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"
                                      placeholder="Description détaillée de l'article"><?php echo htmlspecialchars($notes ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header"><i class="bi bi-graph-up"></i> Gestion du stock</div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>Important :</strong> Le stock initial est modifiable uniquement lors de la création.
                            Après, seul l'administrateur pourra le modifier.
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="stock_initial" class="form-label required">Stock initial</label>
                                <input type="number" class="form-control" id="stock_initial" name="stock_initial" required
                                       value="<?php echo htmlspecialchars($stock_initial ?? 0); ?>" min="0" step="0.01">
                                <small class="text-muted">Quantité de départ</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="stock_min" class="form-label">Stock minimum</label>
                                <input type="number" class="form-control" id="stock_min" name="stock_min"
                                       value="<?php echo htmlspecialchars($stock_min ?? 0); ?>" min="0" step="0.01">
                                <small class="text-muted">Seuil d'alerte</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="stock_max" class="form-label">Stock maximum</label>
                                <input type="number" class="form-control" id="stock_max" name="stock_max"
                                       value="<?php echo htmlspecialchars($stock_max ?? 0); ?>" min="0" step="0.01">
                                <small class="text-muted">Seuil maximum</small>
                            </div>
                        </div>

                        <div class="alert alert-warning">
                            <strong>Note :</strong> Les quantités d'entrées, sorties et disponible seront calculées automatiquement
                            par les mouvements de stock. Vous n'avez pas à les saisir.
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header"><i class="bi bi-gear"></i> Actions</div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-save"></i> Enregistrer
                            </button>
                            <a href="<?php echo BASE_URL; ?>/pages/articles/index.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Retour
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><i class="bi bi-lightbulb"></i> Aide</div>
                    <div class="card-body">
                        <p class="small text-muted mb-2">
                            <strong>Calcul automatique :</strong>
                        </p>
                        <p class="small text-muted">
                            <code>Stock disponible = Stock initial + Entrées - Sorties</code>
                        </p>
                        <hr>
                        <p class="small text-muted mb-0">
                            Les entrées et sorties mettront à jour automatiquement le stock disponible.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
