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

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_affectation_id = !empty($_POST['service_affectation_id']) ? intval($_POST['service_affectation_id']) : null;
    $employe_affectation_id = !empty($_POST['employe_affectation_id']) ? intval($_POST['employe_affectation_id']) : null;
    $bureau_id = !empty($_POST['bureau_id']) ? intval($_POST['bureau_id']) : null;
    $armoire_id = !empty($_POST['armoire_id']) ? intval($_POST['armoire_id']) : null;
    $notes = trim($_POST['notes'] ?? '');

    $errors = [];

    if (empty($errors)) {
        try {
            $sql = "UPDATE sorties
                    SET service_affectation_id = :service_affectation_id,
                        employe_affectation_id = :employe_affectation_id,
                        bureau_id = :bureau_id,
                        armoire_id = :armoire_id,
                        notes = :notes,
                        updated_at = NOW()
                    WHERE id = :id";

            $db->prepare($sql);
            $db->bind(':service_affectation_id', $service_affectation_id);
            $db->bind(':employe_affectation_id', $employe_affectation_id);
            $db->bind(':bureau_id', $bureau_id);
            $db->bind(':armoire_id', $armoire_id);
            $db->bind(':notes', $notes);
            $db->bind(':id', $id);

            if ($db->execute()) {
                $auth->logTrace($auth->getUserId(), 'sorties', 'update', 'sorties', $id, "Modification sortie #$id");

                $_SESSION['success'] = 'Sortie modifiée avec succès.';
                header('Location: ' . BASE_URL . '/pages/sorties/view.php?id=' . $id);
                exit;
            }
        } catch (Exception $e) {
            $errors[] = 'Erreur lors de la modification: ' . $e->getMessage();
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
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="row">
            <div class="col-md-8">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    <strong>Note:</strong> Seules les informations d'affectation et les notes peuvent être modifiées.
                    Les articles, quantités et informations du demandeur ne peuvent pas être modifiés pour préserver l'intégrité du stock.
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

                <!-- Notes -->
                <div class="card mb-3">
                    <div class="card-header"><i class="bi bi-sticky"></i> Notes</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="4"><?php echo htmlspecialchars($sortie['notes'] ?? ''); ?></textarea>
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
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    // Initialiser Select2 avec AJAX pour les services
    initServiceSelect('#service_affectation_id', 'Sélectionner un service...');

    // Charger la valeur initiale du service_affectation_id
    <?php if (!empty($sortie['service_affectation_id'])): ?>
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

    // Charger la valeur initiale de employe_affectation_id
    <?php if (!empty($sortie['employe_affectation_id'])): ?>
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

    // Charger la valeur initiale du bureau_id
    <?php if (!empty($sortie['bureau_id'])): ?>
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

    // Charger la valeur initiale de armoire_id
    <?php if (!empty($sortie['armoire_id'])): ?>
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
                },
                error: function(xhr, status, error) {
                    console.error('Error loading employes affectation:', error);
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
                },
                error: function(xhr, status, error) {
                    console.error('Error loading bureau:', error);
                }
            });
        }
    });
});
</script>
