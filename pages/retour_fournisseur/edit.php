<?php
$page_title = 'Modifier retour fournisseur';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('retour_fournisseur', 'update');
$db = Database::getInstance();
$retourFournisseur = new RetourFournisseur();

$id = $_GET['id'] ?? 0;
$retour = $retourFournisseur->getById($id);

if (!$retour) {
    $_SESSION['error'] = 'Retour fournisseur introuvable.';
    header('Location: ' . BASE_URL . '/pages/retour_fournisseur/index.php');
    exit;
}

// Récupérer la liste des fournisseurs
$db->prepare("SELECT id, nom_complet FROM fournisseurs ORDER BY nom_complet");
$db->execute();
$fournisseurs = $db->fetchAll();

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

    if (empty($errors)) {
        try {
            // Préparer les données
            $articlesData = [];
            foreach ($articles as $index => $article_id) {
                if (empty($article_id) || empty($quantites[$index]) || $quantites[$index] <= 0) {
                    continue;
                }

                $qte = floatval($quantites[$index]);

                // Récupérer stock actuel + quantité de ce retour pour cet article
                $db->prepare("SELECT a.qte_disponible, a.designation,
                                     COALESCE(lrf.qte, 0) as qte_retour_actuel
                              FROM articles a
                              LEFT JOIN ligne_retour_fournisseur lrf ON lrf.article_id = a.id AND lrf.retour_fournisseur_id = :retour_id
                              WHERE a.id = :id");
                $db->bind(':retour_id', $id);
                $db->bind(':id', $article_id);
                $db->execute();
                $article = $db->fetch();

                if (!$article) {
                    throw new Exception("Article ID $article_id introuvable.");
                }

                // Stock disponible après restauration de l'ancien retour
                $stock_apres_restauration = $article['qte_disponible'] + $article['qte_retour_actuel'];

                if ($stock_apres_restauration < $qte) {
                    throw new Exception("Stock insuffisant pour \"" . $article['designation'] . "\". Disponible: " . number_format($stock_apres_restauration, 2, ',', ' ') . " - Demandé: " . number_format($qte, 2, ',', ' '));
                }

                $articlesData[] = [
                    'article_id' => $article_id,
                    'qte' => $qte
                ];
            }

            if (empty($articlesData)) {
                throw new Exception("Aucun article valide à retourner.");
            }

            $data = [
                'fournisseur_id' => $fournisseur_id,
                'date' => $date,
                'notes' => $notes,
                'user_id' => $auth->getUserId(),
                'articles' => $articlesData
            ];

            $retourFournisseur->update($id, $data);

            // Log de la trace
            $auth->logTrace($auth->getUserId(), 'retour_fournisseur', 'update', 'retour_fournisseur', $id, "Modification retour fournisseur #$id");

            $_SESSION['success'] = 'Retour fournisseur modifié avec succès.';
            header('Location: ' . BASE_URL . '/pages/retour_fournisseur/view.php?id=' . $id);
            exit;

        } catch (Exception $e) {
            $errors[] = 'Erreur lors de la modification: ' . $e->getMessage();
        }
    }
} else {
    // Pré-remplir les données
    $fournisseur_id = $retour['fournisseur_id'];
    $date = $retour['date'];
    $notes = $retour['notes'];
}
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-pencil"></i> Modifier retour fournisseur #<?php echo str_pad($id, 5, '0', STR_PAD_LEFT); ?></h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/retour_fournisseur/index.php">Retours Fournisseur</a></li>
                    <li class="breadcrumb-item active">Modifier</li>
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

    <form method="POST" id="retourForm">
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
                                <select class="form-select" id="fournisseur_id" name="fournisseur_id" required>
                                    <option value="">Sélectionner un fournisseur...</option>
                                    <?php foreach ($fournisseurs as $fournisseur): ?>
                                        <option value="<?php echo $fournisseur['id']; ?>" <?php echo $fournisseur['id'] == $fournisseur_id ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($fournisseur['nom_complet']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="date" class="form-label required">Date</label>
                                <input type="date" class="form-control" id="date" name="date" required
                                       value="<?php echo htmlspecialchars($date); ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes / Motif du retour</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($notes ?? ''); ?></textarea>
                        </div>

                        <?php if (!empty($retour['fichier'])): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-file-earmark"></i>
                                Fichier joint: <a href="<?php echo UPLOAD_URL; ?>/retour_fournisseur/<?php echo $retour['fichier']; ?>" target="_blank"><?php echo $retour['fichier']; ?></a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Articles -->
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-box-seam"></i> Articles à retourner</span>
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
                                <i class="bi bi-save"></i> Enregistrer les modifications
                            </button>
                            <a href="<?php echo BASE_URL; ?>/pages/retour_fournisseur/view.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Annuler
                            </a>
                        </div>

                        <hr>

                        <div class="alert alert-warning mb-0">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Attention</strong>
                            <p class="mb-0 mt-2 small">
                                La modification recalculera automatiquement les stocks.
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
const existingArticles = <?php echo json_encode($retour['lignes']); ?>;

$(document).ready(function() {
    // Charger les lignes existantes
    existingArticles.forEach(function(ligne) {
        addArticleLine(ligne);
    });

    // Si aucune ligne, ajouter une ligne vide
    if (existingArticles.length === 0) {
        addArticleLine();
    }
});

function addArticleLine(existingData = null) {
    articleLineCounter++;

    const row = `
        <tr id="articleLine${articleLineCounter}">
            <td>
                <select class="form-select article-select" name="article_id[]" id="article_${articleLineCounter}" required>
                    <option value="">Sélectionner un article...</option>
                </select>
            </td>
            <td>
                <input type="number" class="form-control" name="quantite[]" min="0.01" step="0.01" 
                       value="${existingData ? existingData.qte : ''}" required>
            </td>
            <td>
                <span class="stock-disponible badge bg-info">${existingData ? formatNumber(existingData.stock_actuel) : '-'}</span>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-danger" onclick="removeArticleLine(${articleLineCounter})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    `;

    $('#articlesBody').append(row);

    const selectId = '#article_' + articleLineCounter;
    initArticleSelect(selectId);

    // Si données existantes, sélectionner l'article
    if (existingData) {
        $(selectId).append(new Option(existingData.code_article + ' - ' + existingData.designation, existingData.article_id, true, true));
    }

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
