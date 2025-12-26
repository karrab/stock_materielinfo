<?php
$page_title = 'Nouveau retour';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('retours', 'create');
$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_id = intval($_POST['service_id'] ?? 0);
    $employe_id = intval($_POST['employe_id'] ?? 0);
    $date = $_POST['date'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    $articles = $_POST['article_id'] ?? [];
    $quantites = $_POST['quantite'] ?? [];

    $errors = [];

    if (empty($service_id)) $errors[] = 'Le service est obligatoire.';
    if (empty($employe_id)) $errors[] = 'L\'employé est obligatoire.';
    if (empty($date)) $errors[] = 'La date est obligatoire.';
    if (empty($articles)) $errors[] = 'Veuillez ajouter au moins un article.';

    // Upload fichier
    $fichier = '';
    if (isset($_FILES['fichier']) && $_FILES['fichier']['error'] === 0) {
        $allowed = ALLOWED_FILE_TYPES;
        $filename = $_FILES['fichier']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed) && $_FILES['fichier']['size'] <= MAX_FILE_SIZE) {
            $newname = uniqid() . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['fichier']['tmp_name'], UPLOAD_RETOURS_PATH . '/' . $newname)) {
                $fichier = $newname;
            }
        }
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Insert entête retour
            $sql = "INSERT INTO retours (service_id, employe_id, date, fichier, notes, user_id)
                    VALUES (:service_id, :employe_id, :date, :fichier, :notes, :user_id)";

            $db->prepare($sql);
            $db->bind(':service_id', $service_id);
            $db->bind(':employe_id', $employe_id);
            $db->bind(':date', $date);
            $db->bind(':fichier', $fichier);
            $db->bind(':notes', $notes);
            $db->bind(':user_id', $auth->getUserId());
            $db->execute();

            $retour_id = $db->lastInsertId();

            // Insert lignes retour + mise à jour stock
            foreach ($articles as $index => $article_id) {
                if (empty($article_id) || empty($quantites[$index]) || $quantites[$index] <= 0) {
                    continue;
                }

                $qte = floatval($quantites[$index]);

                // Récupérer info article
                $db->prepare("SELECT code_article, designation FROM articles WHERE id = :id");
                $db->bind(':id', $article_id);
                $article = $db->fetch();

                if (!$article) {
                    throw new Exception('Article ID ' . $article_id . ' introuvable.');
                }

                // Insert ligne retour
                $sql = "INSERT INTO ligne_retours (retour_id, article_id, code_article, designation, qte_retour)
                        VALUES (:retour_id, :article_id, :code, :designation, :qte)";

                $db->prepare($sql);
                $db->bind(':retour_id', $retour_id);
                $db->bind(':article_id', $article_id);
                $db->bind(':code', $article['code_article']);
                $db->bind(':designation', $article['designation']);
                $db->bind(':qte', $qte);
                $db->execute();

                // MISE À JOUR DU STOCK - RETOUR AJOUTE AU STOCK
                $sql = "UPDATE articles
                        SET qte_retour = qte_retour + :qte,
                            qte_disponible = qte_disponible + :qte
                        WHERE id = :article_id";

                $db->prepare($sql);
                $db->bind(':qte', $qte);
                $db->bind(':article_id', $article_id);
                $db->execute();

                // Enregistrement dans l'historique
                $historique = new HistoriqueArticle();
                $historique->enregistrerRetour(
                    $article_id,
                    $qte,
                    $retour_id,
                    $auth->getUserId(),
                    $date,
                    "Retour depuis service"
                );
            }

            $auth->logTrace($auth->getUserId(), 'retours', 'create', 'retours', $retour_id, "Création retour");
            $db->commit();

            $_SESSION['success'] = 'Retour créé avec succès.';
            header('Location: ' . BASE_URL . '/pages/retours/view.php?id=' . $retour_id);
            exit;

        } catch (Exception $e) {
            $db->rollback();
            if (!empty($fichier) && file_exists(UPLOAD_RETOURS_PATH . '/' . $fichier)) {
                unlink(UPLOAD_RETOURS_PATH . '/' . $fichier);
            }
            $errors[] = 'Erreur: ' . $e->getMessage();
        }
    }
}
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-box-arrow-in-up"></i> Nouveau retour</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/retours/index.php">Retours</a></li>
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

    <form method="POST" enctype="multipart/form-data">
        <div class="row">
            <div class="col-md-8">
                <!-- Informations retour --><div class="card mb-3">
                    <div class="card-header"><i class="bi bi-person"></i> Retour par</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="service_id" class="form-label required">Service</label>
                                <select class="form-select select2" id="service_id" name="service_id" required>
                                    <option value="">Sélectionner...</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="employe_id" class="form-label required">Employé</label>
                                <select class="form-select select2" id="employe_id" name="employe_id" required>
                                    <option value="">Sélectionner...</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="date" class="form-label required">Date</label>
                            <input type="date" class="form-control" id="date" name="date" required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                </div>

                <!-- Articles -->
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-box-seam"></i> Articles retournés</span>
                        <button type="button" class="btn btn-sm btn-success" onclick="addArticleLine()">
                            <i class="bi bi-plus-circle"></i> Ajouter
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th width="50%">Article</th>
                                        <th width="30%">Quantité</th>
                                        <th width="20%">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="articlesBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Fichier et notes -->
                <div class="card mb-3">
                    <div class="card-header"><i class="bi bi-file-text"></i> Informations complémentaires</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="fichier" class="form-label">Fichier joint</label>
                            <input type="file" class="form-control" id="fichier" name="fichier">
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
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
                                <i class="bi bi-save"></i> Enregistrer
                            </button>
                            <a href="<?php echo BASE_URL; ?>/pages/retours/index.php" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Annuler
                            </a>
                        </div>
                        <hr>
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-info-circle"></i>
                            <strong>Information</strong>
                            <p class="mb-0 mt-2 small">
                                Les articles retournés seront automatiquement ajoutés au stock disponible.
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
    // Charger les services initialement
    loadServices();

    // Charger employés quand service change
    $('#service_id').on('change', function() {
        let service_id = $(this).val();

        // Réinitialiser le select employe_id
        $('#employe_id').html('<option value="">Sélectionner un employé...</option>').prop('disabled', !service_id);

        if (service_id) {
            // Charger les employés du service sélectionné
            $.ajax({
                url: BASE_URL + '/api/getemployebyservice.php',
                data: { service_id: service_id, search: '' },
                dataType: 'json',
                success: function(data) {
                    if (data && data.length > 0) {
                        data.forEach(function(employe) {
                            $('#employe_id').append(new Option(employe.text, employe.id));
                        });
                    } else {
                        $('#employe_id').html('<option value="">Aucun employé dans ce service</option>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading employes:', error);
                    $('#employe_id').html('<option value="">Erreur de chargement</option>');
                }
            });
        }
    });

    addArticleLine();
});

// Fonction pour charger tous les services
function loadServices() {
    $.ajax({
        url: BASE_URL + '/api/services.php',
        data: { search: '' },
        dataType: 'json',
        success: function(data) {
            if (data && data.length > 0) {
                $('#service_id').html('<option value="">Sélectionner un service...</option>');
                data.forEach(function(service) {
                    $('#service_id').append(new Option(service.text, service.id));
                });
            } else {
                $('#service_id').html('<option value="">Aucun service disponible</option>');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading services:', error);
            console.error('Response:', xhr.responseText);
            $('#service_id').html('<option value="">Erreur de chargement</option>');
        }
    });
}

function addArticleLine() {
    articleLineCounter++;
    const row = `
        <tr id="articleLine${articleLineCounter}">
            <td>
                <select class="form-select article-select" name="article_id[]" id="article_${articleLineCounter}" required>
                    <option value="">Sélectionner...</option>
                </select>
            </td>
            <td>
                <input type="number" class="form-control" name="quantite[]" min="0.01" step="0.01" required>
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
