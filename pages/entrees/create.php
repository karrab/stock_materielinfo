<?php
$page_title = 'Nouvelle entrée';
require_once __DIR__ . '/../../includes/header.php';

$db = Database::getInstance();

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fournisseur_id = intval($_POST['fournisseur_id'] ?? 0);
    $date = $_POST['date'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    $articles = $_POST['article_id'] ?? [];
    $quantites = $_POST['quantite'] ?? [];

    $errors = [];

    // Validation
    if (empty($fournisseur_id)) {
        $errors[] = 'Le fournisseur est obligatoire.';
    }
    if (empty($date)) {
        $errors[] = 'La date est obligatoire.';
    }
    if (empty($articles) || count($articles) === 0) {
        $errors[] = 'Veuillez ajouter au moins un article.';
    }

    // Gestion de l'upload
    $fichier = '';
    if (isset($_FILES['fichier']) && $_FILES['fichier']['error'] === 0) {
        $allowed = ALLOWED_FILE_TYPES;
        $filename = $_FILES['fichier']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed) && $_FILES['fichier']['size'] <= MAX_FILE_SIZE) {
            $newname = uniqid() . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['fichier']['tmp_name'], UPLOAD_ENTREES_PATH . '/' . $newname)) {
                $fichier = $newname;
            } else {
                $errors[] = 'Erreur lors de l\'upload du fichier.';
            }
        } else {
            $errors[] = 'Fichier non autorisé ou trop volumineux.';
        }
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Insert entête d'entrée
            $sql = "INSERT INTO entrees (fournisseur_id, date, fichier, notes, user_id, created_at)
                    VALUES (:fournisseur_id, :date, :fichier, :notes, :user_id, NOW())";

            $db->prepare($sql);
            $db->bind(':fournisseur_id', $fournisseur_id);
            $db->bind(':date', $date);
            $db->bind(':fichier', $fichier);
            $db->bind(':notes', $notes);
            $db->bind(':user_id', $auth->getUserId());
            $db->execute();

            $entree_id = $db->lastInsertId();

            // Insert lignes d'entrée
            foreach ($articles as $index => $article_id) {
                if (empty($article_id) || empty($quantites[$index]) || $quantites[$index] <= 0) {
                    continue;
                }

                $qte = floatval($quantites[$index]);

                // Récupérer les infos de l'article
                $db->prepare("SELECT code_article, designation FROM articles WHERE id = :id");
                $db->bind(':id', $article_id);
                $article = $db->fetch();

                if (!$article) {
                    throw new Exception('Article ID ' . $article_id . ' introuvable.');
                }

                // Insert ligne
                $sql = "INSERT INTO ligne_entrees (entree_id, article_id, code_article, designation, qte_entree)
                        VALUES (:entree_id, :article_id, :code, :designation, :qte)";

                $db->prepare($sql);
                $db->bind(':entree_id', $entree_id);
                $db->bind(':article_id', $article_id);
                $db->bind(':code', $article['code_article']);
                $db->bind(':designation', $article['designation']);
                $db->bind(':qte', $qte);
                $db->execute();

                // Mise à jour du stock de l'article
                $sql = "UPDATE articles
                        SET qte_entree = qte_entree + :qte,
                            qte_disponible = qte_disponible + :qte
                        WHERE id = :article_id";

                $db->prepare($sql);
                $db->bind(':qte', $qte);
                $db->bind(':article_id', $article_id);
                $db->execute();
            }

            // Log de la trace
            $auth->logTrace($auth->getUserId(), 'entrees', 'create', 'entrees', $entree_id, "Création entrée");

            $db->commit();

            $_SESSION['success'] = 'Entrée créée avec succès.';
            header('Location: ' . BASE_URL . '/pages/entrees/view.php?id=' . $entree_id);
            exit;

        } catch (Exception $e) {
            $db->rollback();
            // Supprimer le fichier uploadé en cas d'erreur
            if (!empty($fichier) && file_exists(UPLOAD_ENTREES_PATH . '/' . $fichier)) {
                unlink(UPLOAD_ENTREES_PATH . '/' . $fichier);
            }
            $errors[] = 'Erreur lors de la création: ' . $e->getMessage();
        }
    }
}
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-box-arrow-in-down"></i> Nouvelle entrée</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/entrees/index.php">Entrées</a></li>
                    <li class="breadcrumb-item active">Nouvelle</li>
                </ol>
            </nav>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" id="entreeForm">
        <div class="row">
            <!-- Colonne principale -->
            <div class="col-md-8">
                <!-- Informations générales -->
                <div class="card mb-3">
                    <div class="card-header">
                        <i class="bi bi-info-circle"></i> Informations générales
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="fournisseur_id" class="form-label required">Fournisseur</label>
                                <select class="form-select select2" id="fournisseur_id" name="fournisseur_id" required>
                                    <option value="">Sélectionner un fournisseur...</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="date" class="form-label required">Date</label>
                                <input type="date" class="form-control" id="date" name="date" required
                                       value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="fichier" class="form-label">Fichier joint</label>
                            <input type="file" class="form-control" id="fichier" name="fichier"
                                   accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            <small class="form-text text-muted">
                                Formats acceptés: PDF, DOC, DOCX, JPG, PNG. Taille max: <?php echo (MAX_FILE_SIZE / 1024 / 1024); ?> MB
                            </small>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($notes ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Articles -->
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-box-seam"></i> Articles</span>
                        <button type="button" class="btn btn-sm btn-success" onclick="addArticleLine()">
                            <i class="bi bi-plus-circle"></i> Ajouter un article
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="articlesTable">
                                <thead>
                                    <tr>
                                        <th width="50%">Article</th>
                                        <th width="20%">Quantité</th>
                                        <th width="20%">Stock disponible</th>
                                        <th width="10%" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="articlesBody">
                                    <!-- Les lignes seront ajoutées ici par JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Colonne latérale -->
            <div class="col-md-4">
                <div class="card sticky-top" style="top: 20px;">
                    <div class="card-header">
                        <i class="bi bi-gear"></i> Actions
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-save"></i> Enregistrer l'entrée
                            </button>
                            <a href="<?php echo BASE_URL; ?>/pages/entrees/index.php" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Annuler
                            </a>
                        </div>

                        <hr>

                        <div class="alert alert-info mb-0">
                            <i class="bi bi-info-circle"></i>
                            <strong>Information</strong>
                            <p class="mb-0 mt-2 small">
                                Le stock des articles sera automatiquement mis à jour lors de l'enregistrement.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
let articleLineCounter = 0;

$(document).ready(function() {
    // Initialiser Select2 pour le fournisseur
    initFournisseurSelect('#fournisseur_id');

    // Ajouter une première ligne d'article
    addArticleLine();
});

function addArticleLine() {
    articleLineCounter++;

    const row = `
        <tr id="articleLine${articleLineCounter}">
            <td>
                <select class="form-select article-select" name="article_id[]" id="article_${articleLineCounter}" required>
                    <option value="">Sélectionner un article...</option>
                </select>
            </td>
            <td>
                <input type="number" class="form-control" name="quantite[]" min="0.01" step="0.01" required>
            </td>
            <td>
                <span class="stock-disponible badge bg-info">-</span>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-danger" onclick="removeArticleLine(${articleLineCounter})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    `;

    $('#articlesBody').append(row);

    // Initialiser Select2 pour le nouvel article
    const selectId = '#article_' + articleLineCounter;
    initArticleSelect(selectId);

    // Événement lors de la sélection d'un article
    $(selectId).on('select2:select', function(e) {
        const data = e.params.data;
        $(this).closest('tr').find('.stock-disponible').text(formatNumber(data.qte_disponible));
    });
}

function removeArticleLine(lineId) {
    if ($('#articlesBody tr').length > 1) {
        $('#articleLine' + lineId).remove();
    } else {
        alert('Vous devez avoir au moins un article.');
    }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
