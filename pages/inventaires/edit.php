<?php
$page_title = 'Modifier inventaire';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('inventaires', 'update');
$db = Database::getInstance();
$id = $_GET['id'] ?? 0;

$db->prepare("SELECT * FROM inventaires WHERE id = :id AND etat = 'en_cours'");
$db->bind(':id', $id);
$inventaire = $db->fetch();

if (!$inventaire) {
    $_SESSION['error'] = 'Inventaire introuvable ou non modifiable (état non "En cours").';
    header('Location: ' . BASE_URL . '/pages/inventaires/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reference = trim($_POST['reference'] ?? '');
    $date_debut = $_POST['date_debut'] ?? '';
    $equipe_id = !empty($_POST['equipe_id']) ? intval($_POST['equipe_id']) : null;
    $notes = trim($_POST['notes'] ?? '');

    $errors = [];
    if (empty($reference)) $errors[] = 'La référence est obligatoire.';
    if (empty($date_debut)) $errors[] = 'La date de début est obligatoire.';

    // Vérifier unicité référence
    $db->prepare("SELECT COUNT(*) as count FROM inventaires WHERE reference = :ref AND id != :id");
    $db->bind(':ref', $reference);
    $db->bind(':id', $id);
    if ($db->fetch()['count'] > 0) {
        $errors[] = 'Un inventaire avec cette référence existe déjà.';
    }

    if (empty($errors)) {
        try {
            $sql = "UPDATE inventaires SET
                        reference = :ref,
                        date_debut = :date_debut,
                        equipe_id = :equipe_id,
                        notes = :notes,
                        updated_at = NOW()
                    WHERE id = :id";

            $db->prepare($sql);
            $db->bind(':ref', $reference);
            $db->bind(':date_debut', $date_debut);
            $db->bind(':equipe_id', $equipe_id);
            $db->bind(':notes', $notes);
            $db->bind(':id', $id);

            if ($db->execute()) {
                $auth->logTrace($auth->getUserId(), 'inventaires', 'update', 'inventaires', $id, "Modification: $reference");
                $_SESSION['success'] = 'Inventaire modifié avec succès.';
                header('Location: ' . BASE_URL . '/pages/inventaires/view.php?id=' . $id);
                exit;
            }
        } catch (Exception $e) {
            $errors[] = 'Erreur: ' . $e->getMessage();
        }
    }
} else {
    $reference = $inventaire['reference'];
    $date_debut = $inventaire['date_debut'];
    $equipe_id = $inventaire['equipe_id'];
    $notes = $inventaire['notes'];
}
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>
                        <i class="bi bi-pencil"></i>
                        Modifier l'inventaire: <strong><?php echo htmlspecialchars($inventaire['reference']); ?></strong>
                    </h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/inventaires/index.php">Inventaires</a></li>
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/inventaires/view.php?id=<?php echo $id; ?>"><?php echo htmlspecialchars($inventaire['reference']); ?></a></li>
                            <li class="breadcrumb-item active">Modifier</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <span class="badge bg-warning text-dark fs-5">
                        <i class="bi bi-hourglass-split"></i> En cours
                    </span>
                </div>
            </div>
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

    <!-- Alerte d'information -->
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i>
        <strong>Information :</strong> Seules les informations générales (référence, dates, équipe, notes) peuvent être modifiées ici.
        Pour modifier les articles inventoriés ou les quantités physiques, retournez à la <a href="<?php echo BASE_URL; ?>/pages/inventaires/view.php?id=<?php echo $id; ?>" class="alert-link">page de visualisation</a>.
    </div>

    <form method="POST">
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-header bg-primary text-white">
                        <i class="bi bi-info-circle"></i> Informations générales
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="reference" class="form-label required">
                                    <i class="bi bi-tag"></i> Référence de l'inventaire
                                </label>
                                <input type="text" class="form-control form-control-lg" id="reference" name="reference"
                                       required placeholder="Ex: INV-2024-001"
                                       value="<?php echo htmlspecialchars($reference); ?>">
                                <div class="form-text">Référence unique pour identifier cet inventaire</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="date_debut" class="form-label required">
                                    <i class="bi bi-calendar-event"></i> Date de début
                                </label>
                                <input type="date" class="form-control form-control-lg" id="date_debut" name="date_debut"
                                       required value="<?php echo $date_debut; ?>">
                                <div class="form-text">Date de démarrage de l'inventaire</div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="equipe_id" class="form-label">
                                <i class="bi bi-people"></i> Équipe d'inventaire
                            </label>
                            <select class="form-select select2" id="equipe_id" name="equipe_id">
                                <option value="">Aucune équipe assignée</option>
                            </select>
                            <div class="form-text">Optionnel - Équipe responsable de l'inventaire</div>
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">
                                <i class="bi bi-sticky"></i> Notes / Commentaires
                            </label>
                            <textarea class="form-control" id="notes" name="notes" rows="4"
                                      placeholder="Remarques, objectifs de l'inventaire..."><?php echo htmlspecialchars($notes ?? ''); ?></textarea>
                            <div class="form-text">Informations complémentaires sur cet inventaire</div>
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
                                <i class="bi bi-save"></i> Enregistrer les modifications
                            </button>
                            <a href="<?php echo BASE_URL; ?>/pages/inventaires/view.php?id=<?php echo $id; ?>" class="btn btn-info">
                                <i class="bi bi-eye"></i> Voir l'inventaire
                            </a>
                            <a href="<?php echo BASE_URL; ?>/pages/inventaires/index.php" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Annuler
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Limitations -->
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <i class="bi bi-exclamation-triangle"></i> Limitations
                    </div>
                    <div class="card-body">
                        <p class="mb-2">
                            <i class="bi bi-x-circle text-danger"></i>
                            <strong>Non modifiable :</strong>
                        </p>
                        <ul class="small mb-0">
                            <li>Articles inventoriés</li>
                            <li>Quantités physiques</li>
                            <li>Écarts calculés</li>
                        </ul>
                        <hr>
                        <p class="mb-0 small text-muted">
                            <i class="bi bi-info-circle"></i>
                            Pour modifier les articles, créez un nouvel inventaire.
                            Pour saisir les quantités physiques, utilisez la page de visualisation.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
$(document).ready(function() {
    // Initialiser Select2 pour équipe
    initEquipeSelect('#equipe_id');

    // Précharger la valeur si équipe déjà sélectionnée
    <?php if (!empty($equipe_id)): ?>
        // Charger l'équipe sélectionnée via AJAX
        $.ajax({
            url: BASE_URL + '/api/equipes.php',
            dataType: 'json',
            data: { id: <?php echo $equipe_id; ?> },
            success: function(data) {
                if (data && data.length > 0) {
                    const option = new Option(data[0].text, data[0].id, true, true);
                    $('#equipe_id').append(option).trigger('change');
                }
            }
        });
    <?php endif; ?>
});

function initEquipeSelect(selector) {
    $(selector).select2({
        theme: 'bootstrap-5',
        placeholder: 'Sélectionner une équipe...',
        allowClear: true,
        ajax: {
            url: BASE_URL + '/api/equipes.php',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return { search: params.term };
            },
            processResults: function(data) {
                return { results: data };
            },
            cache: true
        }
    });
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
