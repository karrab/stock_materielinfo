<?php
$page_title = 'Modifier sortie';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('sorties', 'update');
$db = Database::getInstance();
$id = $_GET['id'] ?? 0;

// Récupération de la sortie
$db->prepare("SELECT * FROM sorties WHERE id = :id");
$db->bind(':id', $id);
$sortie = $db->fetch();

if (!$sortie) {
    $_SESSION['error'] = 'Sortie introuvable.';
    header('Location: ' . BASE_URL . '/pages/sorties/index.php');
    exit;
}

// Récupération des lignes de sortie
$db->prepare("SELECT * FROM ligne_sorties WHERE sortie_id = :id ORDER BY id");
$db->bind(':id', $id);
$lignes_existantes = $db->fetchAll();

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_id = intval($_POST['service_id'] ?? 0);
    $employe_id = intval($_POST['employe_id'] ?? 0);
    $date = $_POST['date'] ?? '';
    $service_affectation_id = !empty($_POST['service_affectation_id']) ? intval($_POST['service_affectation_id']) : null;
    $employe_affectation_id = !empty($_POST['employe_affectation_id']) ? intval($_POST['employe_affectation_id']) : null;
    $armoire_id = !empty($_POST['armoire_id']) ? intval($_POST['armoire_id']) : null;
    $bureau_id = !empty($_POST['bureau_id']) ? intval($_POST['bureau_id']) : null;
    $notes = trim($_POST['notes'] ?? '');
    $articles = $_POST['article_id'] ?? [];
    $quantites = $_POST['quantite'] ?? [];

    $errors = [];

    if (empty($service_id)) $errors[] = 'Le service est obligatoire.';
    if (empty($employe_id)) $errors[] = 'L\'employé est obligatoire.';
    if (empty($date)) $errors[] = 'La date est obligatoire.';
    if (empty($articles)) $errors[] = 'Veuillez ajouter au moins un article.';

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // 1. Restaurer le stock des anciens articles
            foreach ($lignes_existantes as $ligne) {
                $sql = "UPDATE articles
                        SET qte_sortie = qte_sortie - ?,
                            qte_disponible = qte_disponible + ?
                        WHERE id = ?";

                $db->prepare($sql);
                $db->bind(1, $ligne['qte_sortie']);
                $db->bind(2, $ligne['qte_sortie']);
                $db->bind(3, $ligne['article_id']);
                $db->execute();
            }

            // 2. Supprimer les anciennes lignes
            $db->prepare("DELETE FROM ligne_sorties WHERE sortie_id = :id");
            $db->bind(':id', $id);
            $db->execute();

            // 3. Mettre à jour l'en-tête de la sortie
            $sql = "UPDATE sorties
                    SET service_id = :service_id,
                        employe_id = :employe_id,
                        date = :date,
                        service_affectation_id = :service_affectation_id,
                        employe_affectation_id = :employe_affectation_id,
                        armoire_id = :armoire_id,
                        bureau_id = :bureau_id,
                        notes = :notes,
                        updated_at = NOW()
                    WHERE id = :id";

            $db->prepare($sql);
            $db->bind(':service_id', $service_id);
            $db->bind(':employe_id', $employe_id);
            $db->bind(':date', $date);
            $db->bind(':service_affectation_id', $service_affectation_id);
            $db->bind(':employe_affectation_id', $employe_affectation_id);
            $db->bind(':armoire_id', $armoire_id);
            $db->bind(':bureau_id', $bureau_id);
            $db->bind(':notes', $notes);
            $db->bind(':id', $id);
            $db->execute();

            // 4. Insérer les nouvelles lignes et mettre à jour le stock
            foreach ($articles as $index => $article_id) {
                if (empty($article_id) || empty($quantites[$index]) || $quantites[$index] <= 0) {
                    continue;
                }

                $qte = floatval($quantites[$index]);

                // Récupérer info article ET vérifier stock disponible
                $db->prepare("SELECT code_article, designation, qte_disponible FROM articles WHERE id = :id");
                $db->bind(':id', $article_id);
                $article = $db->fetch();

                if (!$article) {
                    throw new Exception('Article ID ' . $article_id . ' introuvable.');
                }

                // ⚠️ VÉRIFICATION CRITIQUE DU STOCK
                if ($article['qte_disponible'] < $qte) {
                    throw new Exception('Quantité indisponible en stock pour "' . $article['designation'] .
                                      '". Disponible: ' . number_format($article['qte_disponible'], 2, ',', ' ') .
                                      ' - Demandé: ' . number_format($qte, 2, ',', ' '));
                }

                // Insert ligne sortie
                $sql = "INSERT INTO ligne_sorties (sortie_id, article_id, code_article, designation, qte_sortie)
                        VALUES (:sortie_id, :article_id, :code, :designation, :qte)";

                $db->prepare($sql);
                $db->bind(':sortie_id', $id);
                $db->bind(':article_id', $article_id);
                $db->bind(':code', $article['code_article']);
                $db->bind(':designation', $article['designation']);
                $db->bind(':qte', $qte);
                $db->execute();

                // Mise à jour stock article
                $sql = "UPDATE articles
                        SET qte_sortie = qte_sortie + ?,
                            qte_disponible = qte_disponible - ?
                        WHERE id = ?";

                $db->prepare($sql);
                $db->bind(1, $qte);
                $db->bind(2, $qte);
                $db->bind(3, $article_id);
                $db->execute();
            }

            $auth->logTrace($auth->getUserId(), 'sorties', 'update', 'sorties', $id, "Modification sortie #$id");
            $db->commit();

            $_SESSION['success'] = 'Sortie modifiée avec succès.';
            header('Location: ' . BASE_URL . '/pages/sorties/view.php?id=' . $id);
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
            <h2><i class="bi bi-pencil"></i> Modifier la sortie #<?php echo $sortie['id']; ?></h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/sorties/index.php">Sorties</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/sorties/view.php?id=<?php echo $id; ?>">Détails #<?php echo $id; ?></a></li>
                    <li class="breadcrumb-item active">Modifier</li>
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
                <!-- Informations demandeur -->
                <div class="card mb-3">
                    <div class="card-header"><i class="bi bi-person"></i> Demandeur</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="service_id" class="form-label required">Service</label>
                                <select class="form-select" id="service_id" name="service_id" required>
                                    <option value="">Sélectionner...</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="employe_id" class="form-label required">Employé</label>
                                <select class="form-select" id="employe_id" name="employe_id" required>
                                    <option value="">Sélectionner...</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="date" class="form-label required">Date</label>
                            <input type="date" class="form-control" id="date" name="date" required value="<?php echo $sortie['date']; ?>">
                        </div>
                    </div>
                </div>

                <!-- Affectation -->
                <div class="card mb-3">
                    <div class="card-header"><i class="bi bi-geo-alt"></i> Affectation (optionnel)</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="service_affectation_id" class="form-label">Service affectation</label>
                                <select class="form-select" id="service_affectation_id" name="service_affectation_id">
                                    <option value="">Aucun</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="employe_affectation_id" class="form-label">Employé affectation</label>
                                <select class="form-select" id="employe_affectation_id" name="employe_affectation_id">
                                    <option value="">Aucun</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="bureau_id" class="form-label">Bureau</label>
                                <select class="form-select" id="bureau_id" name="bureau_id">
                                    <option value="">Aucun</option>
                                </select>
                                <small class="text-muted">Se remplit automatiquement avec le bureau de l'employé affecté</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="armoire_id" class="form-label">Armoire</label>
                                <select class="form-select" id="armoire_id" name="armoire_id">
                                    <option value="">Aucune</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Articles -->
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-box-seam"></i> Articles</span>
                        <button type="button" class="btn btn-sm btn-success" onclick="addArticleLine()">
                            <i class="bi bi-plus-circle"></i> Ajouter
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th width="45%">Article</th>
                                        <th width="20%">Quantité</th>
                                        <th width="25%">Stock disponible</th>
                                        <th width="10%">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="articlesBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="card mb-3">
                    <div class="card-header"><i class="bi bi-sticky"></i> Notes</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($sortie['notes'] ?? ''); ?></textarea>
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
                            <a href="<?php echo BASE_URL; ?>/pages/sorties/view.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Annuler
                            </a>
                        </div>
                        <hr>
                        <div class="alert alert-warning mb-0">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Attention</strong>
                            <p class="mb-0 mt-2 small">
                                La modification recalculera le stock. Les anciens articles seront restaurés et les nouveaux articles seront déduits du stock disponible.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
let articleLineCounter = 0;
const existingLines = <?php echo json_encode($lignes_existantes); ?>;

$(document).ready(function() {
    // Initialiser Select2 avec AJAX pour les services
    initServiceSelect('#service_id', 'Sélectionner un service...');
    initServiceSelect('#service_affectation_id', 'Sélectionner un service...');

    // Charger la valeur initiale du service_id
    $.ajax({
        url: BASE_URL + '/api/services.php',
        data: { id: <?php echo $sortie['service_id']; ?> },
        dataType: 'json',
        success: function(data) {
            if (data && data.length > 0) {
                const option = new Option(data[0].text, data[0].id, true, true);
                $('#service_id').append(option).trigger('change');
            }
        }
    });

    // Charger la valeur initiale de employe_id
    $.ajax({
        url: BASE_URL + '/api/employes.php',
        data: { id: <?php echo $sortie['employe_id']; ?> },
        dataType: 'json',
        success: function(data) {
            if (data && data.length > 0) {
                const option = new Option(data[0].text, data[0].id, true, true);
                $('#employe_id').append(option).trigger('change');
            }
        }
    });

    <?php if (!empty($sortie['service_affectation_id'])): ?>
    // Charger service affectation
    $.ajax({
        url: BASE_URL + '/api/services.php',
        data: { id: <?php echo $sortie['service_affectation_id']; ?> },
        dataType: 'json',
        success: function(data) {
            if (data && data.length > 0) {
                const option = new Option(data[0].text, data[0].id, true, true);
                $('#service_affectation_id').append(option).trigger('change');
            }
        }
    });
    <?php endif; ?>

    <?php if (!empty($sortie['employe_affectation_id'])): ?>
    // Charger employe affectation
    $.ajax({
        url: BASE_URL + '/api/employes.php',
        data: { id: <?php echo $sortie['employe_affectation_id']; ?> },
        dataType: 'json',
        success: function(data) {
            if (data && data.length > 0) {
                const option = new Option(data[0].text, data[0].id, true, true);
                $('#employe_affectation_id').append(option).trigger('change');
            }
        }
    });
    <?php endif; ?>

    // Initialiser Select2 sur les autres champs
    initBureauSelect('#bureau_id');
    initArmoireSelect('#armoire_id');

    <?php if (!empty($sortie['bureau_id'])): ?>
    // Charger bureau
    $.ajax({
        url: BASE_URL + '/api/bureaux.php',
        data: { id: <?php echo $sortie['bureau_id']; ?> },
        dataType: 'json',
        success: function(data) {
            if (data && data.length > 0) {
                const option = new Option(data[0].text, data[0].id, true, true);
                $('#bureau_id').append(option).trigger('change');
            }
        }
    });
    <?php endif; ?>

    <?php if (!empty($sortie['armoire_id'])): ?>
    // Charger armoire
    $.ajax({
        url: BASE_URL + '/api/armoires.php',
        data: { id: <?php echo $sortie['armoire_id']; ?> },
        dataType: 'json',
        success: function(data) {
            if (data && data.length > 0) {
                const option = new Option(data[0].text, data[0].id, true, true);
                $('#armoire_id').append(option).trigger('change');
            }
        }
    });
    <?php endif; ?>

    // Charger employés quand service demandeur change
    $('#service_id').on('change', function() {
        let service_id = $(this).val();
        const currentEmployeId = <?php echo $sortie['employe_id']; ?>;

        $('#employe_id').html('<option value="">Sélectionner un employé...</option>').prop('disabled', !service_id);

        if (service_id) {
            $.ajax({
                url: BASE_URL + '/api/getemployebyservice.php',
                data: { service_id: service_id, search: '' },
                dataType: 'json',
                success: function(data) {
                    if (data && data.length > 0) {
                        data.forEach(function(employe) {
                            const option = new Option(employe.text, employe.id, employe.id == currentEmployeId, employe.id == currentEmployeId);
                            $('#employe_id').append(option);
                        });
                    } else {
                        $('#employe_id').html('<option value="">Aucun employé dans ce service</option>');
                    }
                }
            });
        }
    });

    // Charger employés quand service affectation change
    $('#service_affectation_id').on('change', function() {
        let service_id = $(this).val();
        $('#employe_affectation_id').html('<option value="">Aucun</option>').prop('disabled', !service_id);
        $('#bureau_id').html('<option value="">Aucun</option>');

        if (service_id) {
            $.ajax({
                url: BASE_URL + '/api/getemployebyservice.php',
                data: { service_id: service_id, search: '' },
                dataType: 'json',
                success: function(data) {
                    if (data && data.length > 0) {
                        data.forEach(function(employe) {
                            $('#employe_affectation_id').append(new Option(employe.text, employe.id));
                        });
                    } else {
                        $('#employe_affectation_id').html('<option value="">Aucun employé dans ce service</option>');
                    }
                }
            });
        }
    });

    // Charger automatiquement le bureau de l'employé affecté
    $('#employe_affectation_id').on('change', function() {
        let employe_id = $(this).val();
        $('#bureau_id').html('<option value="">Aucun</option>');

        if (employe_id) {
            $.ajax({
                url: BASE_URL + '/api/getbureaubyemploye.php',
                data: { employe_id: employe_id },
                dataType: 'json',
                success: function(response) {
                    if (response && response.bureau) {
                        const bureau = response.bureau;
                        $('#bureau_id').html('<option value="' + bureau.id + '" selected>' + bureau.text + '</option>');
                    } else {
                        $('#bureau_id').html('<option value="">Aucun bureau assigné</option>');
                    }
                }
            });
        }
    });

    // Charger les lignes existantes
    if (existingLines.length > 0) {
        existingLines.forEach(function(ligne) {
            addArticleLineWithData(ligne.article_id, ligne.code_article + ' - ' + ligne.designation, ligne.qte_sortie);
        });
    } else {
        addArticleLine();
    }
});

function addArticleLine() {
    addArticleLineWithData(null, null, null);
}

function addArticleLineWithData(articleId, articleText, quantity) {
    articleLineCounter++;
    const row = `
        <tr id="articleLine${articleLineCounter}">
            <td>
                <select class="form-select article-select" name="article_id[]" id="article_${articleLineCounter}" required>
                    <option value="">Sélectionner...</option>
                </select>
            </td>
            <td>
                <input type="number" class="form-control qte-input" name="quantite[]" min="0.01" step="0.01" required value="${quantity || ''}">
            </td>
            <td>
                <span class="stock-disponible badge bg-info">-</span>
                <span class="stock-warning text-danger" style="display:none;">
                    <i class="bi bi-exclamation-triangle"></i> Stock insuffisant
                </span>
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

    // Si on a des données existantes, les charger
    if (articleId && articleText) {
        const option = new Option(articleText, articleId, true, true);
        $(selectId).append(option).trigger('change');

        // Charger le stock disponible
        $.ajax({
            url: BASE_URL + '/api/articles.php',
            data: { id: articleId },
            dataType: 'json',
            success: function(data) {
                if (data && data.length > 0) {
                    const row = $(selectId).closest('tr');
                    row.find('.stock-disponible').text('Stock: ' + formatNumber(data[0].qte_disponible));
                    row.data('stock-disponible', data[0].qte_disponible);
                }
            }
        });
    }

    $(selectId).on('select2:select', function(e) {
        const data = e.params.data;
        const row = $(this).closest('tr');
        row.find('.stock-disponible').text('Stock: ' + formatNumber(data.qte_disponible));
        row.data('stock-disponible', data.qte_disponible);
    });

    // Vérifier stock quand quantité change
    $(selectId).closest('tr').find('.qte-input').on('input', function() {
        const row = $(this).closest('tr');
        const stock = parseFloat(row.data('stock-disponible')) || 0;
        const qte = parseFloat($(this).val()) || 0;

        if (qte > stock) {
            row.find('.stock-warning').show();
            $(this).addClass('is-invalid');
        } else {
            row.find('.stock-warning').hide();
            $(this).removeClass('is-invalid');
        }
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
