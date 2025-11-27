<?php
$page_title = 'Nouvel inventaire';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('inventaires', 'create');
$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reference = trim($_POST['reference'] ?? '');
    $date_debut = $_POST['date_debut'] ?? '';
    $equipe_id = !empty($_POST['equipe_id']) ? intval($_POST['equipe_id']) : null;
    $notes = trim($_POST['notes'] ?? '');
    $articles = $_POST['article_id'] ?? [];

    $errors = [];
    if (empty($reference)) $errors[] = 'La référence est obligatoire.';
    if (empty($date_debut)) $errors[] = 'La date de début est obligatoire.';
    if (empty($articles)) $errors[] = 'Veuillez ajouter au moins un article.';

    // Vérifier unicité référence
    $db->prepare("SELECT COUNT(*) as count FROM inventaires WHERE reference = :ref");
    $db->bind(':ref', $reference);
    if ($db->fetch()['count'] > 0) {
        $errors[] = 'Un inventaire avec cette référence existe déjà.';
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Insert inventaire
            $sql = "INSERT INTO inventaires (reference, date_debut, equipe_id, etat, notes, user_id)
                    VALUES (:ref, :date_debut, :equipe_id, 'en_cours', :notes, :user_id)";

            $db->prepare($sql);
            $db->bind(':ref', $reference);
            $db->bind(':date_debut', $date_debut);
            $db->bind(':equipe_id', $equipe_id);
            $db->bind(':notes', $notes);
            $db->bind(':user_id', $auth->getUserId());
            $db->execute();

            $inventaire_id = $db->lastInsertId();

            // Insert lignes inventaire avec stock théorique
            foreach ($articles as $article_id) {
                if (empty($article_id)) continue;

                // Récupérer stock actuel
                $db->prepare("SELECT code_article, designation, qte_disponible FROM articles WHERE id = :id");
                $db->bind(':id', $article_id);
                $article = $db->fetch();

                if (!$article) continue;

                // Insert ligne avec qte_theorique = stock actuel
                $sql = "INSERT INTO ligne_inventaires (inventaire_id, article_id, code_article, designation, qte_theorique, qte_physique, ecart)
                        VALUES (:inv_id, :article_id, :code, :designation, :qte_theorique, 0, 0)";

                $db->prepare($sql);
                $db->bind(':inv_id', $inventaire_id);
                $db->bind(':article_id', $article_id);
                $db->bind(':code', $article['code_article']);
                $db->bind(':designation', $article['designation']);
                $db->bind(':qte_theorique', $article['qte_disponible']);
                $db->execute();
            }

            $auth->logTrace($auth->getUserId(), 'inventaires', 'create', 'inventaires', $inventaire_id, "Création: $reference");
            $db->commit();

            $_SESSION['success'] = 'Inventaire créé avec succès.';
            header('Location: ' . BASE_URL . '/pages/inventaires/view.php?id=' . $inventaire_id);
            exit;

        } catch (Exception $e) {
            $db->rollback();
            $errors[] = 'Erreur: ' . $e->getMessage();
        }
    }
}
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-clipboard-check"></i> Nouvel inventaire</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/inventaires/index.php">Inventaires</a></li>
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
                    <div class="card-header"><i class="bi bi-info-circle"></i> Informations de l'inventaire</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="reference" class="form-label required">Référence</label>
                                <input type="text" class="form-control" id="reference" name="reference" required placeholder="INV-2024-001">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="date_debut" class="form-label required">Date début</label>
                                <input type="date" class="form-control" id="date_debut" name="date_debut" required value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="equipe_id" class="form-label">Équipe d'inventaire</label>
                            <select class="form-select select2" id="equipe_id" name="equipe_id">
                                <option value="">Aucune</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-box-seam"></i> Articles à inventorier</span>
                        <button type="button" class="btn btn-sm btn-success" onclick="addArticleLine()">
                            <i class="bi bi-plus-circle"></i> Ajouter
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th width="85%">Article</th>
                                        <th width="15%">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="articlesBody"></tbody>
                            </table>
                        </div>
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-info-circle"></i> Le stock théorique sera automatiquement rempli avec le stock disponible actuel de chaque article.
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card sticky-top" style="top: 20px;">
                    <div class="card-header"><i class="bi bi-gear"></i> Actions</div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-save"></i> Créer l'inventaire
                            </button>
                            <a href="<?php echo BASE_URL; ?>/pages/inventaires/index.php" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Annuler
                            </a>
                        </div>
                        <hr>
                        <div class="alert alert-warning mb-0">
                            <strong>État initial</strong>
                            <p class="mb-0 mt-2 small">L'inventaire sera créé avec l'état <strong>"En cours"</strong></p>
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
    initEquipeSelect('#equipe_id');
    addArticleLine();
});

function initEquipeSelect(selector) {
    $(selector).select2({
        theme: 'bootstrap-5',
        ajax: {
            url: BASE_URL + '/api/equipes.php',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return { search: params.term };
            },
            processResults: function(data) {
                return { results: data };
            }
        }
    });
}

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
                <button type="button" class="btn btn-sm btn-danger" onclick="removeArticleLine(${articleLineCounter})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    `;
    $('#articlesBody').append(row);

    const selectId = '#article_' + articleLineCounter;
    initArticleSelect(selectId);
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
