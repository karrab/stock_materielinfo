<?php
$page_title = 'Détails inventaire';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('inventaires', 'view');
$db = Database::getInstance();
$id = $_GET['id'] ?? 0;

$sql = "SELECT i.*, ei.nom as equipe_nom,
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

// Récupérer les lignes
$db->prepare("SELECT * FROM ligne_inventaires WHERE inventaire_id = :id ORDER BY code_article");
$db->bind(':id', $id);
$lignes = $db->fetchAll();

// Calculer statistiques
$total_articles = count($lignes);
$total_ecarts = 0;
$ecarts_positifs = 0;
$ecarts_negatifs = 0;
$articles_ok = 0;
$sum_theorique = 0;
$sum_physique = 0;

foreach ($lignes as $ligne) {
    $sum_theorique += $ligne['qte_theorique'];
    $sum_physique += $ligne['qte_physique'];

    if ($ligne['ecart'] > 0) $ecarts_positifs++;
    elseif ($ligne['ecart'] < 0) $ecarts_negatifs++;
    else $articles_ok++;

    $total_ecarts += abs($ligne['ecart']);
}

// État badge/label
$etat_config = match($inventaire['etat']) {
    'en_cours' => [
        'badge' => 'bg-warning text-dark',
        'label' => 'En cours',
        'icon' => 'bi-hourglass-split',
        'description' => 'Vous pouvez saisir les quantités physiques et générer les écarts.'
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
    <!-- En-tête avec état -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
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
                <div>
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

    <!-- Statistiques rapides -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-left-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total articles</div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo $total_articles; ?></div>
                        </div>
                        <div class="text-primary">
                            <i class="bi bi-box-seam fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Articles OK</div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo $articles_ok; ?></div>
                        </div>
                        <div class="text-success">
                            <i class="bi bi-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Écarts positifs</div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo $ecarts_positifs; ?></div>
                        </div>
                        <div class="text-warning">
                            <i class="bi bi-arrow-up-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-danger">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Écarts négatifs</div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo $ecarts_negatifs; ?></div>
                        </div>
                        <div class="text-danger">
                            <i class="bi bi-arrow-down-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Colonne principale: Table des articles -->
        <div class="col-md-9">
            <div class="card mb-3">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-table"></i> Articles inventoriés (<?php echo $total_articles; ?>)</span>
                    <?php if ($inventaire['etat'] == 'en_cours'): ?>
                        <a href="<?php echo BASE_URL; ?>/pages/inventaires/generer_ecarts.php?id=<?php echo $id; ?>"
                           class="btn btn-light btn-sm"
                           onclick="return confirm('Calculer les écarts pour tous les articles ?');">
                            <i class="bi bi-calculator"></i> Générer les écarts
                        </a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if ($inventaire['etat'] == 'en_cours'): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>Saisie des quantités physiques :</strong>
                            Entrez le stock compté réellement. Les modifications sont enregistrées automatiquement.
                            Cliquez sur "Générer les écarts" pour calculer les différences.
                        </div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th width="15%">Code article</th>
                                    <th width="35%">Désignation</th>
                                    <th width="15%" class="text-end">Qté théorique</th>
                                    <th width="15%" class="text-end">Qté physique</th>
                                    <th width="15%" class="text-end">Écart</th>
                                    <th width="5%" class="text-center">
                                        <i class="bi bi-info-circle" title="État"></i>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($lignes) > 0): ?>
                                    <?php foreach ($lignes as $ligne): ?>
                                        <?php
                                        $ecart_class = '';
                                        $ecart_icon = '';
                                        $row_class = '';

                                        if ($ligne['ecart'] < 0) {
                                            $ecart_class = 'text-danger fw-bold';
                                            $ecart_icon = 'bi-arrow-down-circle text-danger';
                                            $row_class = 'table-danger-light';
                                        } elseif ($ligne['ecart'] > 0) {
                                            $ecart_class = 'text-success fw-bold';
                                            $ecart_icon = 'bi-arrow-up-circle text-success';
                                            $row_class = 'table-warning-light';
                                        } else {
                                            $ecart_class = 'text-muted';
                                            $ecart_icon = 'bi-check-circle text-success';
                                            $row_class = '';
                                        }
                                        ?>
                                        <tr class="<?php echo $row_class; ?>">
                                            <td>
                                                <strong><?php echo htmlspecialchars($ligne['code_article']); ?></strong>
                                            </td>
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
                                                           step="0.01" min="0">
                                                <?php else: ?>
                                                    <span class="badge bg-primary"><?php echo number_format($ligne['qte_physique'], 2, ',', ' '); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end <?php echo $ecart_class; ?>">
                                                <?php if ($ligne['ecart'] != 0): ?>
                                                    <?php echo ($ligne['ecart'] > 0 ? '+' : '') . number_format($ligne['ecart'], 2, ',', ' '); ?>
                                                <?php else: ?>
                                                    0
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
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
                                        <td colspan="6" class="text-center text-muted py-4">
                                            <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                            <p class="mt-2">Aucun article dans cet inventaire</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <?php if (!empty($inventaire['notes'])): ?>
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <i class="bi bi-sticky"></i> Notes / Commentaires
                    </div>
                    <div class="card-body">
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($inventaire['notes'])); ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Colonne latérale: Informations et actions -->
        <div class="col-md-3">
            <!-- Informations générales -->
            <div class="card mb-3">
                <div class="card-header bg-dark text-white">
                    <i class="bi bi-info-circle"></i> Informations générales
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <strong><i class="bi bi-tag"></i> Référence :</strong><br>
                        <span class="text-primary"><?php echo htmlspecialchars($inventaire['reference']); ?></span>
                    </p>
                    <hr>
                    <p class="mb-2">
                        <strong><i class="bi bi-calendar-event"></i> Date début :</strong><br>
                        <?php echo date('d/m/Y', strtotime($inventaire['date_debut'])); ?>
                    </p>
                    <p class="mb-2">
                        <strong><i class="bi bi-calendar-check"></i> Date fin :</strong><br>
                        <?php echo $inventaire['date_fin'] ? date('d/m/Y', strtotime($inventaire['date_fin'])) : '<span class="text-muted">En cours...</span>'; ?>
                    </p>
                    <hr>
                    <p class="mb-2">
                        <strong><i class="bi bi-people"></i> Équipe :</strong><br>
                        <?php echo $inventaire['equipe_nom'] ? htmlspecialchars($inventaire['equipe_nom']) : '<span class="text-muted">Aucune équipe</span>'; ?>
                    </p>
                    <p class="mb-2">
                        <strong><i class="bi bi-person-badge"></i> Créé par :</strong><br>
                        <?php echo htmlspecialchars($inventaire['user_nom'] . ' ' . $inventaire['user_prenom']); ?>
                    </p>
                    <?php if ($inventaire['etat'] != 'en_cours' && $inventaire['user_validation_id']): ?>
                        <p class="mb-2">
                            <strong><i class="bi bi-person-check"></i> Validé par :</strong><br>
                            <?php echo htmlspecialchars($inventaire['valideur_nom'] . ' ' . $inventaire['valideur_prenom']); ?>
                        </p>
                        <?php if ($inventaire['date_validation']): ?>
                            <p class="mb-0">
                                <strong><i class="bi bi-clock"></i> Date validation :</strong><br>
                                <?php echo date('d/m/Y H:i', strtotime($inventaire['date_validation'])); ?>
                            </p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Actions -->
            <div class="card mb-3 sticky-top" style="top: 20px;">
                <div class="card-header bg-warning">
                    <i class="bi bi-gear"></i> Actions disponibles
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <?php if ($inventaire['etat'] == 'en_cours' && $auth->hasPermission('inventaires', 'update')): ?>
                            <a href="<?php echo BASE_URL; ?>/pages/inventaires/edit.php?id=<?php echo $id; ?>"
                               class="btn btn-warning">
                                <i class="bi bi-pencil"></i> Modifier l'inventaire
                            </a>
                            <a href="<?php echo BASE_URL; ?>/pages/inventaires/valider.php?id=<?php echo $id; ?>"
                               class="btn btn-info"
                               onclick="return confirm('⚠️ ATTENTION\n\nValider cet inventaire va :\n- Le rendre non modifiable\n- Permettre sa clôture par un admin\n\nContinuer ?');">
                                <i class="bi bi-check-circle"></i> Valider l'inventaire
                            </a>
                        <?php endif; ?>

                        <?php if ($inventaire['etat'] == 'valide' && $auth->isAdmin()): ?>
                            <a href="<?php echo BASE_URL; ?>/pages/inventaires/cloturer.php?id=<?php echo $id; ?>"
                               class="btn btn-success"
                               onclick="return confirm('⚠️ CLÔTURE DÉFINITIVE\n\nClôturer cet inventaire va :\n- Figer définitivement les données\n- Empêcher toute modification future\n\nCette action est irréversible. Continuer ?');">
                                <i class="bi bi-lock"></i> Clôturer (Admin)
                            </a>
                            <a href="<?php echo BASE_URL; ?>/pages/inventaires/reinitialiser.php?id=<?php echo $id; ?>"
                               class="btn btn-outline-warning"
                               onclick="return confirm('Réinitialiser cet inventaire en état \"En cours\" ?\n\nIl pourra à nouveau être modifié.');">
                                <i class="bi bi-arrow-counterclockwise"></i> Réinitialiser (Admin)
                            </a>
                        <?php endif; ?>

                        <?php if ($inventaire['etat'] == 'en_cours' && $auth->hasPermission('inventaires', 'delete')): ?>
                            <hr>
                            <a href="<?php echo BASE_URL; ?>/pages/inventaires/delete.php?id=<?php echo $id; ?>"
                               class="btn btn-danger"
                               onclick="return confirm('⚠️ SUPPRESSION\n\nSupprimer définitivement cet inventaire et toutes ses lignes ?\n\nCette action est irréversible.');">
                                <i class="bi bi-trash"></i> Supprimer
                            </a>
                        <?php endif; ?>

                        <hr>
                        <a href="<?php echo BASE_URL; ?>/pages/inventaires/index.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Retour à la liste
                        </a>
                    </div>
                </div>
            </div>

            <!-- Résumé des écarts -->
            <div class="card">
                <div class="card-header bg-info text-white">
                    <i class="bi bi-bar-chart"></i> Résumé des écarts
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span><i class="bi bi-box-seam"></i> Total articles :</span>
                        <strong><?php echo $total_articles; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-success"><i class="bi bi-check-circle"></i> Articles OK :</span>
                        <strong class="text-success"><?php echo $articles_ok; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-warning"><i class="bi bi-arrow-up-circle"></i> Écarts + :</span>
                        <strong class="text-warning"><?php echo $ecarts_positifs; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-danger"><i class="bi bi-arrow-down-circle"></i> Écarts - :</span>
                        <strong class="text-danger"><?php echo $ecarts_negatifs; ?></strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span><strong>Total abs. écarts :</strong></span>
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
$(document).ready(function() {
    // Auto-save qte_physique on change with visual feedback
    $('.qte-physique').on('change', function() {
        const ligneId = $(this).data('ligne-id');
        const qte = $(this).val();
        const input = $(this);

        // Visual feedback: processing
        input.prop('disabled', true);
        input.addClass('border-warning');

        $.ajax({
            url: BASE_URL + '/pages/inventaires/update_qte_physique.php',
            method: 'POST',
            dataType: 'json',
            data: {
                ligne_id: ligneId,
                qte_physique: qte
            },
            success: function(response) {
                console.log('Response:', response);

                // Vérifier si la mise à jour a réussi
                if (response.success) {
                    // Success feedback
                    input.removeClass('border-warning is-invalid');
                    input.addClass('is-valid border-success');

                    // Mettre à jour l'écart affiché
                    const row = input.closest('tr');
                    const ecartCell = row.find('td').eq(4);
                    const ecart = parseFloat(response.ecart);

                    let ecartText = ecart.toFixed(2).replace('.', ',');
                    if (ecart > 0) ecartText = '+' + ecartText;
                    else if (ecart === 0) ecartText = '0';

                    ecartCell.text(ecartText);
                    ecartCell.removeClass('text-danger text-success text-muted');
                    if (ecart < 0) ecartCell.addClass('text-danger fw-bold');
                    else if (ecart > 0) ecartCell.addClass('text-success fw-bold');
                    else ecartCell.addClass('text-muted');

                    // Mettre à jour l'icône d'état
                    const iconCell = row.find('td').eq(5);
                    iconCell.html(ecart < 0 ? '<i class="bi-arrow-down-circle text-danger"></i>' :
                                  ecart > 0 ? '<i class="bi-arrow-up-circle text-success"></i>' :
                                  '<i class="bi-check-circle text-success"></i>');

                    setTimeout(() => {
                        input.removeClass('is-valid border-success');
                        input.prop('disabled', false);
                    }, 1500);
                } else {
                    // Erreur retournée par le serveur
                    input.removeClass('border-warning is-valid');
                    input.addClass('is-invalid border-danger');
                    input.prop('disabled', false);

                    console.error('Erreur serveur:', response.error);
                    alert('❌ Erreur: ' + (response.error || 'Erreur inconnue') +
                          (response.debug ? '\n\nDébug: ' + response.debug : ''));
                }
            },
            error: function(xhr, status, error) {
                // Error feedback
                input.removeClass('border-warning is-valid');
                input.addClass('is-invalid border-danger');
                input.prop('disabled', false);

                console.error('AJAX Error:', xhr.responseText);
                alert('❌ Erreur lors de la mise à jour.\n\n' +
                      'Status: ' + status + '\n' +
                      'Error: ' + error + '\n\n' +
                      'Réponse serveur: ' + xhr.responseText.substring(0, 200));
            }
        });
    });

    // Enter key to move to next input
    $('.qte-physique').on('keypress', function(e) {
        if (e.which === 13) { // Enter key
            e.preventDefault();
            $(this).trigger('change');

            const nextInput = $(this).closest('tr').next('tr').find('.qte-physique');
            if (nextInput.length) {
                nextInput.focus().select();
            }
        }
    });
});
</script>
<?php endif; ?>
