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
    $_SESSION['error'] = 'Inventaire introuvable ou non modifiable.';
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

    // Vérifier unicité
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
            <h2><i class="bi bi-clipboard-check"></i> Modifier l'inventaire</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/inventaires/index.php">Inventaires</a></li>
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
                <div class="card">
                    <div class="card-header"><i class="bi bi-info-circle"></i> Informations de l'inventaire</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="reference" class="form-label required">Référence</label>
                                <input type="text" class="form-control" id="reference" name="reference" required value="<?php echo htmlspecialchars($reference); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="date_debut" class="form-label required">Date début</label>
                                <input type="date" class="form-control" id="date_debut" name="date_debut" required value="<?php echo $date_debut; ?>">
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
                            <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($notes ?? ''); ?></textarea>
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
                            <a href="<?php echo BASE_URL; ?>/pages/inventaires/view.php?id=<?php echo $id; ?>" class="btn btn-info">
                                <i class="bi bi-eye"></i> Voir
                            </a>
                            <a href="<?php echo BASE_URL; ?>/pages/inventaires/index.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Retour
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><i class="bi bi-info-circle"></i> Note</div>
                    <div class="card-body">
                        <p class="mb-0 small text-muted">Les articles inventoriés ne peuvent pas être modifiés ici. Vous devez créer un nouvel inventaire si nécessaire.</p>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
$(document).ready(function() {
    initEquipeSelect('#equipe_id');
    <?php if (!empty($equipe_id)): ?>
        $('#equipe_id').val('<?php echo $equipe_id; ?>').trigger('change');
    <?php endif; ?>
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
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
