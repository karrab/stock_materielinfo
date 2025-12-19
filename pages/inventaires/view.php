<?php
/**
 * Page de visualisation détaillée d'un inventaire
 * Permet la saisie des quantités physiques en temps réel (si état = en_cours)
 */

$page_title = 'Détails inventaire';
require_once __DIR__ . '/../../includes/header.php';

// Vérifier les permissions
$auth->requirePermission('inventaires', 'view');

// Récupérer l'inventaire
$db = Database::getInstance();
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$sql = "SELECT i.*,
               ei.nom as equipe_nom,
               u.nom as user_nom, u.prenom as user_prenom,
               uv.nom as valideur_nom, uv.prenom as valideur_prenom
        FROM inventaires i
        LEFT JOIN equipes_inventaire ei ON i.equipe_id = ei.id
        LEFT JOIN users u ON i.user_id = u.id
        LEFT JOIN users uv ON i.user_validation_id = uv.id
        WHERE i.id = :id";

$db->prepare($sql);
$db->bind(':id', $id);
$inventaire = $db->fetch();

if (!$inventaire) {
    $_SESSION['error'] = 'Inventaire introuvable.';
    header('Location: ' . BASE_URL . '/pages/inventaires/index.php');
    exit;
}

// Ajouter la référence si elle n'existe pas (anciens inventaires)
if (empty($inventaire['reference'])) {
    $inventaire['reference'] = 'INV-' . $id;
}

// Récupérer les lignes d'inventaire
$db->prepare("SELECT * FROM ligne_inventaires WHERE inventaire_id = :id ORDER BY code_article");
$db->bind(':id', $id);
$lignes = $db->fetchAll();

// Calculer les statistiques
$total_articles = count($lignes);
$total_ecarts = 0;
$ecarts_positifs = 0;
$ecarts_negatifs = 0;
$articles_ok = 0;
$sum_theorique = 0;
$sum_physique = 0;

foreach ($lignes as $ligne) {
    $sum_theorique += floatval($ligne['qte_theorique']);
    $sum_physique += floatval($ligne['qte_physique']);

    $ecart = floatval($ligne['ecart']);
    if ($ecart > 0) {
        $ecarts_positifs++;
    } elseif ($ecart < 0) {
        $ecarts_negatifs++;
    } else {
        $articles_ok++;
    }

    $total_ecarts += abs($ecart);
}

// Configuration de l'affichage selon l'état
$etat_config = match($inventaire['etat']) {
    'en_cours' => [
        'badge' => 'bg-warning text-dark',
        'label' => 'En cours',
        'icon' => 'bi-hourglass-split',
        'description' => 'Vous pouvez saisir les quantités physiques. Les modifications sont enregistrées automatiquement.'
    ],
    'valide' => [
        'badge' => 'bg-info',
        'label' => 'Validé',
        'icon' => 'bi-check-circle',
        'description' => 'Inventaire validé. Seul un administrateur peut le clôturer ou le réinitialiser.'
    ],
    'cloture' => [
        'badge' => 'bg-success',
        'label' => 'Clôturé',
        'icon' => 'bi-lock',
        'description' => 'Inventaire clôturé. Aucune modification possible.'
    ],
    default => [
        'badge' => 'bg-secondary',
        'label' => $inventaire['etat'],
        'icon' => 'bi-question-circle',
        'description' => ''
    ]
};
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h2>
                        <i class="bi bi-clipboard-check"></i>
                        Inventaire: <strong><?php echo htmlspecialchars($inventaire['reference']); ?></strong>
                    </h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/inventaires/index.php">Inventaires</a></li>
                            <li class="breadcrumb-item active"><?php echo htmlspecialchars($inventaire['reference']); ?></li>
                        </ol>
                    </nav>
                </div>
                <div class="mt-2 mt-md-0">
                    <span class="badge <?php echo $etat_config['badge']; ?> fs-4">
                        <i class="<?php echo $etat_config['icon']; ?>"></i>
                        <?php echo $etat_config['label']; ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerte état -->
    <?php if (!empty($etat_config['description'])): ?>
        <div class="row mb-3">
            <div class="col-12">
                <div class="alert alert-<?php echo $inventaire['etat'] == 'en_cours' ? 'warning' : ($inventaire['etat'] == 'valide' ? 'info' : 'success'); ?> mb-0">
                    <i class="<?php echo $etat_config['icon']; ?>"></i>
                    <strong><?php echo $etat_config['label']; ?> :</strong>
                    <?php echo $etat_config['description']; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3 mb-md-0">
            <div class="card border-left-primary h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total articles</div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo $total_articles; ?></div>
                        </div>
                        <div class="text-primary">
                            <i class="bi bi-box-seam" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3 mb-md-0">
            <div class="card border-left-success h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Articles OK</div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo $articles_ok; ?></div>
                        </div>
                        <div class="text-success">
                            <i class="bi bi-check-circle" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3 mb-md-0">
            <div class="card border-left-warning h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Écarts positifs</div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo $ecarts_positifs; ?></div>
                        </div>
                        <div class="text-warning">
                            <i class="bi bi-arrow-up-circle" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3 mb-md-0">
            <div class="card border-left-danger h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Écarts négatifs</div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo $ecarts_negatifs; ?></div>
                        </div>
                        <div class="text-danger">
                            <i class="bi bi-arrow-down-circle" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Colonne principale: Table des articles -->
        <div class="col-lg-9">
            <div class="card mb-3">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center flex-wrap">
                    <span><i class="bi bi-table"></i> Articles inventoriés (<?php echo $total_articles; ?>)</span>
                    <?php if ($inventaire['etat'] == 'en_cours' && count($lignes) > 0): ?>
                        <a href="<?php echo BASE_URL; ?>/pages/inventaires/generer_ecarts.php?id=<?php echo $id; ?>"
                           class="btn btn-light btn-sm mt-2 mt-md-0"
                           onclick="return confirm('Calculer les écarts pour tous les articles ?');">
                            <i class="bi bi-calculator"></i> Recalculer les écarts
                        </a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if ($inventaire['etat'] == 'en_cours' && count($lignes) > 0): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>Mode saisie activé :</strong>
                            Entrez les quantités comptées réellement. Les modifications sont enregistrées automatiquement dès que vous quittez le champ.
                        </div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th width="15%">Code article</th>
                                    <th width="35%">Désignation</th>
                                    <th width="13%" class="text-end">Qté théorique</th>
                                    <th width="15%" class="text-end">Qté physique</th>
                                    <th width="13%" class="text-end">Écart</th>
                                    <th width="9%" class="text-center">
                                        <i class="bi bi-info-circle" title="État"></i>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($lignes) > 0): ?>
                                    <?php foreach ($lignes as $ligne): ?>
                                        <?php
                                        $ecart = floatval($ligne['ecart']);
                                        $ecart_class = '';
                                        $ecart_icon = '';
                                        $row_class = '';

                                        if ($ecart < 0) {
                                            $ecart_class = 'text-danger fw-bold';
                                            $ecart_icon = 'bi-arrow-down-circle text-danger';
                                            $row_class = 'table-danger-light';
                                        } elseif ($ecart > 0) {
                                            $ecart_class = 'text-success fw-bold';
                                            $ecart_icon = 'bi-arrow-up-circle text-success';
                                            $row_class = 'table-warning-light';
                                        } else {
                                            $ecart_class = 'text-muted';
                                            $ecart_icon = 'bi-check-circle text-success';
                                            $row_class = '';
                                        }
                                        ?>
                                        <tr class="<?php echo $row_class; ?>" data-ligne-id="<?php echo $ligne['id']; ?>">
                                            <td><strong><?php echo htmlspecialchars($ligne['code_article']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($ligne['designation']); ?></td>
                                            <td class="text-end">
                                                <span class="badge bg-secondary"><?php echo number_format($ligne['qte_theorique'], 2, ',', ' '); ?></span>
                                            </td>
                                            <td class="text-end">
                                                <?php if ($inventaire['etat'] == 'en_cours'): ?>
                                                    <input type="number"
                                                           class="form-control form-control-sm text-end qte-physique"
                                                           data-ligne-id="<?php echo $ligne['id']; ?>"
                                                           value="<?php echo $ligne['qte_physique']; ?>"
                                                           step="0.01" min="0"
                                                           style="min-width: 100px;">
                                                <?php else: ?>
                                                    <span class="badge bg-primary"><?php echo number_format($ligne['qte_physique'], 2, ',', ' '); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end ecart-cell <?php echo $ecart_class; ?>">
                                                <?php
                                                if ($ecart != 0) {
                                                    echo ($ecart > 0 ? '+' : '') . number_format($ecart, 2, ',', ' ');
                                                } else {
                                                    echo '0';
                                                }
                                                ?>
                                            </td>
                                            <td class="text-center status-cell">
                                                <i class="<?php echo $ecart_icon; ?>"></i>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>

                                    <!-- Ligne de total -->
                                    <tr class="table-secondary fw-bold">
                                        <td colspan="2" class="text-end">TOTAL :</td>
                                        <td class="text-end"><?php echo number_format($sum_theorique, 2, ',', ' '); ?></td>
                                        <td class="text-end"><?php echo number_format($sum_physique, 2, ',', ' '); ?></td>
                                        <td class="text-end <?php echo ($sum_physique - $sum_theorique) < 0 ? 'text-danger' : (($sum_physique - $sum_theorique) > 0 ? 'text-success' : 'text-muted'); ?>">
                                            <?php
                                            $ecart_total = $sum_physique - $sum_theorique;
                                            echo ($ecart_total > 0 ? '+' : '') . number_format($ecart_total, 2, ',', ' ');
                                            ?>
                                        </td>
                                        <td></td>
                                    </tr>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-5">
                                            <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                            <p class="mt-3">Aucun article dans cet inventaire</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Colonne latérale: Informations et actions -->
        <div class="col-lg-3">
            <!-- Informations générales -->
            <div class="card mb-3">
                <div class="card-header bg-dark text-white">
                    <i class="bi bi-info-circle"></i> Informations
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <strong><i class="bi bi-tag"></i> Référence :</strong><br>
                        <span class="text-primary"><?php echo htmlspecialchars($inventaire['reference']); ?></span>
                    </p>
                    <hr>
                    <p class="mb-2">
                        <strong><i class="bi bi-calendar-event"></i> Date :</strong><br>
                        <?php echo date('d/m/Y', strtotime($inventaire['date'])); ?>
                    </p>
                    <p class="mb-2">
                        <strong><i class="bi bi-clock"></i> Créé le :</strong><br>
                        <?php echo date('d/m/Y H:i', strtotime($inventaire['created_at'])); ?>
                    </p>
                    <hr>
                    <p class="mb-2">
                        <strong><i class="bi bi-people"></i> Équipe :</strong><br>
                        <?php echo $inventaire['equipe_nom'] ? htmlspecialchars($inventaire['equipe_nom']) : '<span class="text-muted">Aucune</span>'; ?>
                    </p>
                    <p class="mb-2">
                        <strong><i class="bi bi-person-badge"></i> Créé par :</strong><br>
                        <?php echo htmlspecialchars($inventaire['user_nom'] . ' ' . $inventaire['user_prenom']); ?>
                    </p>
                    <?php if ($inventaire['etat'] != 'en_cours' && $inventaire['user_validation_id']): ?>
                        <hr>
                        <p class="mb-2">
                            <strong><i class="bi bi-person-check"></i> Validé par :</strong><br>
                            <?php echo htmlspecialchars($inventaire['valideur_nom'] . ' ' . $inventaire['valideur_prenom']); ?>
                        </p>
                        <?php if ($inventaire['date_validation']): ?>
                            <p class="mb-0">
                                <strong><i class="bi bi-calendar-check"></i> Date validation :</strong><br>
                                <?php echo date('d/m/Y H:i', strtotime($inventaire['date_validation'])); ?>
                            </p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Actions -->
            <div class="card mb-3">
                <div class="card-header bg-warning">
                    <i class="bi bi-gear"></i> Actions
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <?php if ($inventaire['etat'] == 'en_cours' && $auth->hasPermission('inventaires', 'update')): ?>
                            <a href="<?php echo BASE_URL; ?>/pages/inventaires/edit.php?id=<?php echo $id; ?>"
                               class="btn btn-warning">
                                <i class="bi bi-pencil"></i> Modifier
                            </a>
                            <a href="<?php echo BASE_URL; ?>/pages/inventaires/valider.php?id=<?php echo $id; ?>"
                               class="btn btn-info"
                               onclick="return confirm('Valider cet inventaire ?\n\nIl deviendra non modifiable.');">
                                <i class="bi bi-check-circle"></i> Valider
                            </a>
                        <?php endif; ?>

                        <?php if ($inventaire['etat'] == 'valide' && $auth->isAdmin()): ?>
                            <a href="<?php echo BASE_URL; ?>/pages/inventaires/cloturer.php?id=<?php echo $id; ?>"
                               class="btn btn-success"
                               onclick="return confirm('Clôturer définitivement cet inventaire ?\n\nCette action est irréversible.');">
                                <i class="bi bi-lock"></i> Clôturer
                            </a>
                            <a href="<?php echo BASE_URL; ?>/pages/inventaires/reinitialiser.php?id=<?php echo $id; ?>"
                               class="btn btn-outline-warning"
                               onclick="return confirm('Réinitialiser en \"En cours\" ?');">
                                <i class="bi bi-arrow-counterclockwise"></i> Réinitialiser
                            </a>
                        <?php endif; ?>

                        <?php if ($inventaire['etat'] == 'en_cours' && $auth->hasPermission('inventaires', 'delete')): ?>
                            <hr>
                            <a href="<?php echo BASE_URL; ?>/pages/inventaires/delete.php?id=<?php echo $id; ?>"
                               class="btn btn-danger"
                               onclick="return confirm('Supprimer cet inventaire ?\n\nCette action est irréversible.');">
                                <i class="bi bi-trash"></i> Supprimer
                            </a>
                        <?php endif; ?>

                        <hr>
                        <a href="<?php echo BASE_URL; ?>/pages/inventaires/index.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Retour
                        </a>
                    </div>
                </div>
            </div>

            <!-- Résumé des écarts -->
            <div class="card">
                <div class="card-header bg-info text-white">
                    <i class="bi bi-bar-chart"></i> Résumé
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span><i class="bi bi-box-seam"></i> Total articles :</span>
                        <strong><?php echo $total_articles; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-success"><i class="bi bi-check-circle"></i> OK :</span>
                        <strong class="text-success"><?php echo $articles_ok; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-warning"><i class="bi bi-arrow-up-circle"></i> Excédents :</span>
                        <strong class="text-warning"><?php echo $ecarts_positifs; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-danger"><i class="bi bi-arrow-down-circle"></i> Manquants :</span>
                        <strong class="text-danger"><?php echo $ecarts_negatifs; ?></strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span><strong>Total écarts abs. :</strong></span>
                        <strong class="<?php echo $total_ecarts > 0 ? 'text-danger' : 'text-success'; ?>">
                            <?php echo number_format($total_ecarts, 2, ',', ' '); ?>
                        </strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.border-left-primary { border-left: 4px solid #0d6efd; }
.border-left-success { border-left: 4px solid #198754; }
.border-left-warning { border-left: 4px solid #ffc107; }
.border-left-danger { border-left: 4px solid #dc3545; }
.text-xs { font-size: 0.75rem; }
.table-danger-light { background-color: #f8d7da; }
.table-warning-light { background-color: #fff3cd; }
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<?php if ($inventaire['etat'] == 'en_cours'): ?>
<script>
// JavaScript pour la mise à jour automatique des quantités physiques
$(document).ready(function() {
    console.log('Module inventaire chargé - BASE_URL:', BASE_URL);

    // Sélection focus automatique sur premier input
    $('.qte-physique').first().focus().select();

    // Mise à jour automatique des quantités physiques
    $('.qte-physique').on('change', function() {
        const input = $(this);
        const ligneId = input.data('ligne-id');
        const qte = input.val();
        const row = input.closest('tr');

        console.log('Changement détecté - Ligne ID:', ligneId, ', Qté:', qte);

        // Désactiver pendant traitement
        input.prop('disabled', true);
        input.removeClass('is-valid is-invalid border-success border-danger');
        input.addClass('border-warning bg-warning bg-opacity-10');

        // Afficher indicateur de chargement dans la cellule état
        row.find('.status-cell').html('<div class="spinner-border spinner-border-sm text-primary" role="status"><span class="visually-hidden">Chargement...</span></div>');

        $.ajax({
            url: BASE_URL + '/pages/inventaires/update_qte_physique.php',
            method: 'POST',
            dataType: 'json',
            data: {
                ligne_id: ligneId,
                qte_physique: qte
            },
            success: function(response) {
                console.log('Réponse serveur:', response);

                if (response.success) {
                    // Mise à jour réussie
                    input.removeClass('border-warning bg-warning bg-opacity-10');
                    input.addClass('is-valid border-success');

                    // Mettre à jour l'écart affiché
                    const ecart = parseFloat(response.ecart);
                    const ecartCell = row.find('.ecart-cell');

                    let ecartText = ecart.toFixed(2).replace('.', ',');
                    if (ecart > 0) ecartText = '+' + ecartText;
                    else if (ecart === 0) ecartText = '0';

                    ecartCell.text(ecartText);
                    ecartCell.removeClass('text-danger text-success text-muted fw-bold');

                    if (ecart < 0) {
                        ecartCell.addClass('text-danger fw-bold');
                    } else if (ecart > 0) {
                        ecartCell.addClass('text-success fw-bold');
                    } else {
                        ecartCell.addClass('text-muted');
                    }

                    // Mettre à jour l'icône d'état
                    const icon = ecart < 0 ? '<i class="bi-arrow-down-circle text-danger"></i>' :
                                (ecart > 0 ? '<i class="bi-arrow-up-circle text-success"></i>' :
                                '<i class="bi-check-circle text-success"></i>');
                    row.find('.status-cell').html(icon);

                    // Mettre à jour la couleur de la ligne
                    row.removeClass('table-danger-light table-warning-light');
                    if (ecart < 0) row.addClass('table-danger-light');
                    else if (ecart > 0) row.addClass('table-warning-light');

                    // Enlever le feedback après 1.5 secondes
                    setTimeout(() => {
                        input.removeClass('is-valid border-success');
                        input.prop('disabled', false);
                    }, 1500);

                } else {
                    // Erreur retournée par le serveur
                    input.removeClass('border-warning bg-warning bg-opacity-10');
                    input.addClass('is-invalid border-danger');
                    row.find('.status-cell').html('<i class="bi-x-circle text-danger"></i>');

                    console.error('Erreur:', response.error);
                    alert('❌ Erreur: ' + (response.error || 'Erreur inconnue'));

                    input.prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                // Erreur Ajax
                input.removeClass('border-warning bg-warning bg-opacity-10');
                input.addClass('is-invalid border-danger');
                row.find('.status-cell').html('<i class="bi-x-circle text-danger"></i>');

                console.error('Erreur Ajax:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });

                alert('❌ Erreur de communication avec le serveur.\n\n' +
                      'Status: ' + status + '\n' +
                      'Error: ' + error + '\n\n' +
                      'Réponse: ' + xhr.responseText.substring(0, 200));

                input.prop('disabled', false);
            }
        });
    });

    // Touche Entrée pour passer au champ suivant
    $('.qte-physique').on('keypress', function(e) {
        if (e.which === 13) { // Entrée
            e.preventDefault();
            $(this).trigger('change');

            // Focus sur le prochain input
            const nextInput = $(this).closest('tr').next('tr').find('.qte-physique');
            if (nextInput.length) {
                setTimeout(() => {
                    nextInput.focus().select();
                }, 200);
            }
        }
    });

    // Focus automatique au clic sur la ligne
    $('tr[data-ligne-id]').on('click', function(e) {
        if (!$(e.target).hasClass('qte-physique')) {
            $(this).find('.qte-physique').focus().select();
        }
    });
});
</script>
<?php endif; ?>
