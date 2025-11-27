<?php
$page_title = 'Détails inventaire';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('inventaires', 'view');
$db = Database::getInstance();
$id = $_GET['id'] ?? 0;

$sql = "SELECT i.*, ei.nom as equipe_nom, u.nom as user_nom
        FROM inventaires i
        LEFT JOIN equipes_inventaire ei ON i.equipe_id = ei.id
        LEFT JOIN users u ON i.user_id = u.id
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

$etat_badge = match($inventaire['etat']) {
    'en_cours' => 'bg-warning',
    'valide' => 'bg-info',
    'cloture' => 'bg-success',
    default => 'bg-secondary'
};

$etat_label = match($inventaire['etat']) {
    'en_cours' => 'En cours',
    'valide' => 'Validé',
    'cloture' => 'Clôturé',
    default => $inventaire['etat']
};
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-clipboard-check"></i> Inventaire: <?php echo htmlspecialchars($inventaire['reference']); ?></h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/inventaires/index.php">Inventaires</a></li>
                            <li class="breadcrumb-item active">Détails</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <span class="badge <?php echo $etat_badge; ?> fs-5"><?php echo $etat_label; ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-9">
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-table"></i> Articles inventoriés</span>
                    <?php if ($inventaire['etat'] == 'en_cours'): ?>
                        <a href="<?php echo BASE_URL; ?>/pages/inventaires/generer_ecarts.php?id=<?php echo $id; ?>" class="btn btn-sm btn-warning">
                            <i class="bi bi-calculator"></i> Générer les écarts
                        </a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Code</th>
                                    <th>Désignation</th>
                                    <th class="text-end">Stock théorique</th>
                                    <th class="text-end">Stock physique</th>
                                    <th class="text-end">Écart</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($lignes) > 0): ?>
                                    <?php foreach ($lignes as $ligne): ?>
                                        <?php
                                        $ecart_class = '';
                                        if ($ligne['ecart'] < 0) $ecart_class = 'text-danger fw-bold';
                                        elseif ($ligne['ecart'] > 0) $ecart_class = 'text-success fw-bold';
                                        else $ecart_class = 'text-muted';
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($ligne['code_article']); ?></td>
                                            <td><?php echo htmlspecialchars($ligne['designation']); ?></td>
                                            <td class="text-end"><?php echo number_format($ligne['qte_theorique'], 2, ',', ' '); ?></td>
                                            <td class="text-end">
                                                <?php if ($inventaire['etat'] == 'en_cours'): ?>
                                                    <input type="number" class="form-control form-control-sm text-end qte-physique"
                                                           data-ligne-id="<?php echo $ligne['id']; ?>"
                                                           value="<?php echo $ligne['qte_physique']; ?>"
                                                           step="0.01" min="0">
                                                <?php else: ?>
                                                    <?php echo number_format($ligne['qte_physique'], 2, ',', ' '); ?>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end <?php echo $ecart_class; ?>">
                                                <?php if ($ligne['ecart'] != 0): ?>
                                                    <?php echo ($ligne['ecart'] > 0 ? '+' : '') . number_format($ligne['ecart'], 2, ',', ' '); ?>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">Aucun article</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <?php if (!empty($inventaire['notes'])): ?>
                <div class="card">
                    <div class="card-header"><i class="bi bi-sticky"></i> Notes</div>
                    <div class="card-body">
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($inventaire['notes'])); ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-md-3">
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-info-circle"></i> Informations</div>
                <div class="card-body">
                    <p><strong>Référence:</strong><br><?php echo htmlspecialchars($inventaire['reference']); ?></p>
                    <p><strong>Date début:</strong><br><?php echo date('d/m/Y', strtotime($inventaire['date_debut'])); ?></p>
                    <p><strong>Date fin:</strong><br><?php echo $inventaire['date_fin'] ? date('d/m/Y', strtotime($inventaire['date_fin'])) : '<span class="text-muted">-</span>'; ?></p>
                    <p><strong>Équipe:</strong><br><?php echo htmlspecialchars($inventaire['equipe_nom'] ?? '-'); ?></p>
                    <p class="mb-0"><strong>Créé par:</strong><br><?php echo htmlspecialchars($inventaire['user_nom']); ?></p>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-gear"></i> Actions</div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <?php if ($inventaire['etat'] == 'en_cours' && $auth->hasPermission('inventaires', 'update')): ?>
                            <a href="<?php echo BASE_URL; ?>/pages/inventaires/edit.php?id=<?php echo $id; ?>" class="btn btn-warning">
                                <i class="bi bi-pencil"></i> Modifier
                            </a>
                            <a href="<?php echo BASE_URL; ?>/pages/inventaires/valider.php?id=<?php echo $id; ?>" class="btn btn-info"
                               onclick="return confirm('Voulez-vous valider cet inventaire ? Il ne sera plus modifiable.');">
                                <i class="bi bi-check-circle"></i> Valider
                            </a>
                        <?php endif; ?>

                        <?php if ($inventaire['etat'] == 'valide' && $auth->isAdmin()): ?>
                            <a href="<?php echo BASE_URL; ?>/pages/inventaires/cloturer.php?id=<?php echo $id; ?>" class="btn btn-success"
                               onclick="return confirm('Voulez-vous clôturer cet inventaire ? Cette action est définitive.');">
                                <i class="bi bi-lock"></i> Clôturer
                            </a>
                            <a href="<?php echo BASE_URL; ?>/pages/inventaires/reinitialiser.php?id=<?php echo $id; ?>" class="btn btn-warning"
                               onclick="return confirm('Voulez-vous réinitialiser cet inventaire en En cours ?');">
                                <i class="bi bi-arrow-counterclockwise"></i> Réinitialiser
                            </a>
                        <?php endif; ?>

                        <?php if ($inventaire['etat'] == 'en_cours' && $auth->hasPermission('inventaires', 'delete')): ?>
                            <a href="<?php echo BASE_URL; ?>/pages/inventaires/delete.php?id=<?php echo $id; ?>" class="btn btn-danger"
                               onclick="return confirm('Voulez-vous vraiment supprimer cet inventaire ?');">
                                <i class="bi bi-trash"></i> Supprimer
                            </a>
                        <?php endif; ?>

                        <a href="<?php echo BASE_URL; ?>/pages/inventaires/index.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Retour
                        </a>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><i class="bi bi-bar-chart"></i> Statistiques</div>
                <div class="card-body">
                    <?php
                    $total_articles = count($lignes);
                    $total_ecarts = 0;
                    $ecarts_positifs = 0;
                    $ecarts_negatifs = 0;

                    foreach ($lignes as $ligne) {
                        if ($ligne['ecart'] > 0) $ecarts_positifs++;
                        elseif ($ligne['ecart'] < 0) $ecarts_negatifs++;
                        $total_ecarts += abs($ligne['ecart']);
                    }
                    ?>
                    <p><strong>Total articles:</strong> <?php echo $total_articles; ?></p>
                    <p><strong>Écarts positifs:</strong> <span class="text-success"><?php echo $ecarts_positifs; ?></span></p>
                    <p><strong>Écarts négatifs:</strong> <span class="text-danger"><?php echo $ecarts_negatifs; ?></span></p>
                    <p class="mb-0"><strong>Total écarts:</strong> <?php echo number_format($total_ecarts, 2, ',', ' '); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($inventaire['etat'] == 'en_cours'): ?>
<script>
$(document).ready(function() {
    // Auto-save qte_physique on change
    $('.qte-physique').on('change', function() {
        const ligneId = $(this).data('ligne-id');
        const qte = $(this).val();
        const input = $(this);

        $.ajax({
            url: BASE_URL + '/pages/inventaires/update_qte_physique.php',
            method: 'POST',
            data: {
                ligne_id: ligneId,
                qte_physique: qte
            },
            success: function(response) {
                input.addClass('is-valid');
                setTimeout(() => input.removeClass('is-valid'), 1000);
            },
            error: function() {
                alert('Erreur lors de la mise à jour');
                input.addClass('is-invalid');
            }
        });
    });
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
