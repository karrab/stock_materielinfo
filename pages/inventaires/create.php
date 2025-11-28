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
    if (empty($articles)) $errors[] = 'Veuillez ajouter au moins un article à inventorier.';

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

                // Insert ligne avec qte_theorique = stock actuel, qte_physique = 0
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

            $_SESSION['success'] = 'Inventaire créé avec succès en état "En cours". Vous pouvez maintenant saisir les quantités physiques.';
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
            <h2><i class="bi bi-clipboard-check"></i> Créer un nouvel inventaire</h2>
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
        <div class="alert alert-danger alert-dismissible fade show">
            <strong><i class="bi bi-exclamation-triangle"></i> Erreurs de saisie :</strong>
            <ul class="mb-0 mt-2">
                <?php foreach ($errors as $error): ?><li><?php echo $error; ?></li><?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="row">
            <div class="col-md-8">
                <!-- Informations générales -->
                <div class="card mb-3">
                    <div class="card-header bg-primary text-white">
                        <i class="bi bi-info-circle"></i> Informations de l'inventaire
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="reference" class="form-label required">Référence de l'inventaire</label>
                                <input type="text" class="form-control form-control-lg" id="reference" name="reference" required 
                                       placeholder="Ex: INV-2024-001" value="<?php echo htmlspecialchars($_POST['reference'] ?? ''); ?>">
                                <div class="form-text">Référence unique pour identifier cet inventaire</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="date_debut" class="form-label required">Date de début</label>
                                <input type="date" class="form-control form-control-lg" id="date_debut" name="date_debut" required 
                                       value="<?php echo $_POST['date_debut'] ?? date('Y-m-d'); ?>">
                                <div class="form-text">Date de démarrage de l'inventaire</div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="equipe_id" class="form-label">Équipe d'inventaire</label>
                                <select class="form-select select2" id="equipe_id" name="equipe_id">
                                    <option value="">Aucune équipe assignée</option>
                                </select>
                                <div class="form-text">Optionnel - Équipe responsable de l'inventaire</div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes / Commentaires</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" 
                                      placeholder="Remarques, objectifs de l'inventaire..."><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Articles à inventorier -->
                <div class="card">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-box-seam"></i> Articles à inventorier</span>
                        <div class="btn-group">
                            <button type="button" class="btn btn-light btn-sm" onclick="addAllActiveArticles()">
                                <i class="bi bi-layers-fill"></i> Tous les articles actifs
                            </button>
                            <button type="button" class="btn btn-light btn-sm" onclick="addArticleLine()">
                                <i class="bi bi-plus-circle"></i> Ajouter un article
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="bi bi-lightbulb"></i> <strong>Info :</strong> Le stock théorique sera automatiquement rempli avec le stock disponible actuel au moment de la création.
                            Utilisez le bouton "Tous les articles actifs" pour ajouter automatiquement tous les articles en un clic.
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="80%">Article</th>
                                        <th width="15%" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="articlesBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Actions -->
                <div class="card mb-3 sticky-top" style="top: 20px;">
                    <div class="card-header bg-dark text-white">
                        <i class="bi bi-gear"></i> Actions
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-save"></i> Créer l'inventaire
                            </button>
                            <a href="<?php echo BASE_URL; ?>/pages/inventaires/index.php" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Annuler
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Workflow -->
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-diagram-3"></i> Workflow de l'inventaire
                    </div>
                    <div class="card-body">
                        <ol class="list-group list-group-numbered">
                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                <div class="ms-2 me-auto">
                                    <div class="fw-bold">Créer l'inventaire</div>
                                    État: <span class="badge bg-warning text-dark">En cours</span>
                                </div>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                <div class="ms-2 me-auto">
                                    <div class="fw-bold">Saisir les quantités physiques</div>
                                    Compter les articles réels
                                </div>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                <div class="ms-2 me-auto">
                                    <div class="fw-bold">Générer les écarts</div>
                                    Calcul automatique des différences
                                </div>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                <div class="ms-2 me-auto">
                                    <div class="fw-bold">Valider</div>
                                    État: <span class="badge bg-info">Validé</span>
                                </div>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                <div class="ms-2 me-auto">
                                    <div class="fw-bold">Clôturer (Admin)</div>
                                    État: <span class="badge bg-success">Clôturé</span>
                                </div>
                            </li>
                        </ol>
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
    addArticleLine(); // Ajouter une première ligne
});

function initEquipeSelect(selector) {
    $(selector).select2({
        theme: 'bootstrap-5',
        placeholder: 'Sélectionner une équipe...',
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
            <td class="text-center align-middle">${articleLineCounter}</td>
            <td>
                <select class="form-select article-select" name="article_id[]" id="article_${articleLineCounter}" required>
                    <option value="">Sélectionner un article...</option>
                </select>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-danger btn-sm" onclick="removeArticleLine(${articleLineCounter})">
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
        // Renumber les lignes
        let counter = 1;
        $('#articlesBody tr').each(function() {
            $(this).find('td:first').text(counter++);
        });
    } else {
        alert('Vous devez avoir au moins un article à inventorier.');
    }
}

function initArticleSelect(selector) {
    $(selector).select2({
        theme: 'bootstrap-5',
        placeholder: 'Sélectionner un article...',
        ajax: {
            url: BASE_URL + '/api/articles.php',
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

function addAllActiveArticles() {
    // Demander confirmation
    if (!confirm('Ajouter tous les articles actifs à cet inventaire ?\n\nCela va charger tous les articles de la base de données.')) {
        return;
    }

    // Afficher loader
    const btn = event.target.closest('button');
    const originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Chargement...';

    // Vider la table actuelle
    $('#articlesBody').empty();
    articleLineCounter = 0;

    // Charger tous les articles actifs via AJAX
    $.ajax({
        url: BASE_URL + '/api/articles.php',
        method: 'GET',
        dataType: 'json',
        data: { all_active: 1 },
        success: function(articles) {
            if (!articles || articles.length === 0) {
                alert('Aucun article actif trouvé dans la base de données.');
                addArticleLine(); // Ajouter au moins une ligne vide
                btn.disabled = false;
                btn.innerHTML = originalHTML;
                return;
            }

            // Ajouter chaque article
            articles.forEach(function(article) {
                articleLineCounter++;
                const row = `
                    <tr id="articleLine${articleLineCounter}">
                        <td class="text-center align-middle">${articleLineCounter}</td>
                        <td>
                            <select class="form-select article-select" name="article_id[]" id="article_${articleLineCounter}" required>
                                <option value="${article.id}" selected>${article.text}</option>
                            </select>
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-danger btn-sm" onclick="removeArticleLine(${articleLineCounter})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
                $('#articlesBody').append(row);

                // Initialiser Select2 pour ce select
                const selectId = '#article_' + articleLineCounter;
                $(selectId).select2({
                    theme: 'bootstrap-5',
                    placeholder: 'Sélectionner un article...',
                    ajax: {
                        url: BASE_URL + '/api/articles.php',
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
            });

            // Restaurer le bouton
            btn.disabled = false;
            btn.innerHTML = originalHTML;

            // Message de succès
            alert(`✅ ${articles.length} article(s) actif(s) ajouté(s) avec succès !`);
        },
        error: function(xhr, status, error) {
            alert('❌ Erreur lors du chargement des articles: ' + error);
            addArticleLine(); // Ajouter au moins une ligne vide
            btn.disabled = false;
            btn.innerHTML = originalHTML;
        }
    });
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
