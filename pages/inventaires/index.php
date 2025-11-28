<?php
$page_title = 'Inventaires';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('inventaires', 'view');
$db = Database::getInstance();

// Filtres
$search = $_GET['search'] ?? '';
$etat = $_GET['etat'] ?? '';
$equipe_id = $_GET['equipe_id'] ?? '';

$sql = "SELECT i.*,
               ei.nom as equipe_nom,
               u.nom as user_nom,
               (SELECT COUNT(*) FROM ligne_inventaires WHERE inventaire_id = i.id) as nb_articles,
               (SELECT COUNT(*) FROM ligne_inventaires WHERE inventaire_id = i.id AND ecart != 0) as nb_ecarts,
               (SELECT SUM(ABS(ecart)) FROM ligne_inventaires WHERE inventaire_id = i.id) as total_ecarts
        FROM inventaires i
        LEFT JOIN equipes_inventaire ei ON i.equipe_id = ei.id
        LEFT JOIN users u ON i.user_id = u.id
        WHERE 1=1";

$params = [];

if (!empty($search)) {
    $sql .= " AND i.reference LIKE :search";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($etat)) {
    $sql .= " AND i.etat = :etat";
    $params[':etat'] = $etat;
}

if (!empty($equipe_id)) {
    $sql .= " AND i.equipe_id = :equipe_id";
    $params[':equipe_id'] = $equipe_id;
}

$sql .= " ORDER BY i.date_debut DESC, i.id DESC";

$stmt = $db->getConnection()->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$inventaires = $stmt->fetchAll();

// Récupérer équipes pour filtre
$db->prepare("SELECT id, nom FROM equipes_inventaire ORDER BY nom");
$equipes = $db->fetchAll();

// Statistiques
$stats = [
    'en_cours' => 0,
    'valide' => 0,
    'cloture' => 0,
    'total' => count($inventaires)
];
foreach ($inventaires as $inv) {
    $stats[$inv['etat']]++;
}
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-clipboard-check"></i> Gestion des inventaires</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                            <li class="breadcrumb-item active">Inventaires</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <?php if ($auth->hasPermission('inventaires', 'create')): ?>
                        <a href="<?php echo BASE_URL; ?>/pages/inventaires/create.php" class="btn btn-primary btn-lg">
                            <i class="bi bi-plus-circle"></i> Nouvel inventaire
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-left-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">En cours</div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo $stats['en_cours']; ?></div>
                        </div>
                        <div class="text-warning">
                            <i class="bi bi-hourglass-split fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Validés</div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo $stats['valide']; ?></div>
                        </div>
                        <div class="text-info">
                            <i class="bi bi-check-circle fa-2x"></i>
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
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Clôturés</div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo $stats['cloture']; ?></div>
                        </div>
                        <div class="text-success">
                            <i class="bi bi-lock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total</div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo $stats['total']; ?></div>
                        </div>
                        <div class="text-primary">
                            <i class="bi bi-clipboard-data fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-3">
                    <label class="form-label small">Recherche</label>
                    <input type="text" class="form-control" name="search" placeholder="Référence..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">État</label>
                    <select class="form-select" name="etat">
                        <option value="">Tous les états</option>
                        <option value="en_cours" <?php echo $etat == 'en_cours' ? 'selected' : ''; ?>>En cours</option>
                        <option value="valide" <?php echo $etat == 'valide' ? 'selected' : ''; ?>>Validé</option>
                        <option value="cloture" <?php echo $etat == 'cloture' ? 'selected' : ''; ?>>Clôturé</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Équipe</label>
                    <select class="form-select" name="equipe_id">
                        <option value="">Toutes les équipes</option>
                        <?php foreach ($equipes as $equipe): ?>
                            <option value="<?php echo $equipe['id']; ?>" <?php echo $equipe_id == $equipe['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($equipe['nom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2"><i class="bi bi-search"></i> Filtrer</button>
                    <?php if (!empty($search) || !empty($etat) || !empty($equipe_id)): ?>
                        <a href="<?php echo BASE_URL; ?>/pages/inventaires/index.php" class="btn btn-secondary"><i class="bi bi-x"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des inventaires -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <i class="bi bi-list-ul"></i> Liste des inventaires (<?php echo count($inventaires); ?>)
        </div>
        <div class="card-body">
            <?php if (count($inventaires) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-sm" id="inventairesTable">
                        <thead class="table-light">
                            <tr>
                                <th width="15%">Référence</th>
                                <th width="10%">Date début</th>
                                <th width="10%">Date fin</th>
                                <th width="15%">Équipe</th>
                                <th width="10%" class="text-center">État</th>
                                <th width="10%" class="text-center">Articles</th>
                                <th width="10%" class="text-center">Écarts</th>
                                <th width="10%" class="text-center">Total écarts</th>
                                <th width="10%" class="text-center no-sort">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inventaires as $inv): ?>
                                <?php
                                $badge_class = match($inv['etat']) {
                                    'en_cours' => 'bg-warning text-dark',
                                    'valide' => 'bg-info',
                                    'cloture' => 'bg-success',
                                    default => 'bg-secondary'
                                };
                                $etat_label = match($inv['etat']) {
                                    'en_cours' => 'En cours',
                                    'valide' => 'Validé',
                                    'cloture' => 'Clôturé',
                                    default => $inv['etat']
                                };
                                $icon_etat = match($inv['etat']) {
                                    'en_cours' => 'bi-hourglass-split',
                                    'valide' => 'bi-check-circle',
                                    'cloture' => 'bi-lock',
                                    default => 'bi-question-circle'
                                };
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($inv['reference']); ?></strong>
                                        <br><small class="text-muted">Par: <?php echo htmlspecialchars($inv['user_nom']); ?></small>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($inv['date_debut'])); ?></td>
                                    <td>
                                        <?php if ($inv['date_fin']): ?>
                                            <?php echo date('d/m/Y', strtotime($inv['date_fin'])); ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $inv['equipe_nom'] ? htmlspecialchars($inv['equipe_nom']) : '<span class="text-muted">Aucune</span>'; ?></td>
                                    <td class="text-center">
                                        <span class="badge <?php echo $badge_class; ?>">
                                            <i class="<?php echo $icon_etat; ?>"></i> <?php echo $etat_label; ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary"><?php echo $inv['nb_articles']; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($inv['nb_ecarts'] > 0): ?>
                                            <span class="badge bg-danger"><?php echo $inv['nb_ecarts']; ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-success">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($inv['total_ecarts'] > 0): ?>
                                            <span class="text-danger fw-bold"><?php echo number_format($inv['total_ecarts'], 2, ',', ' '); ?></span>
                                        <?php else: ?>
                                            <span class="text-success">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="<?php echo BASE_URL; ?>/pages/inventaires/view.php?id=<?php echo $inv['id']; ?>"
                                               class="btn btn-info" title="Voir détails">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <?php if ($inv['etat'] == 'en_cours' && $auth->hasPermission('inventaires', 'update')): ?>
                                                <a href="<?php echo BASE_URL; ?>/pages/inventaires/edit.php?id=<?php echo $inv['id']; ?>"
                                                   class="btn btn-warning" title="Modifier">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($inv['etat'] == 'en_cours' && $auth->hasPermission('inventaires', 'delete')): ?>
                                                <a href="<?php echo BASE_URL; ?>/pages/inventaires/delete.php?id=<?php echo $inv['id']; ?>"
                                                   class="btn btn-danger" title="Supprimer"
                                                   onclick="return confirm('Voulez-vous vraiment supprimer cet inventaire ?');">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox" style="font-size: 4rem; color: #ccc;"></i>
                    <p class="text-muted mt-3">Aucun inventaire trouvé</p>
                    <?php if ($auth->hasPermission('inventaires', 'create')): ?>
                        <a href="<?php echo BASE_URL; ?>/pages/inventaires/create.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Créer le premier inventaire
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.border-left-warning { border-left: 4px solid #ffc107; }
.border-left-info { border-left: 4px solid #0dcaf0; }
.border-left-success { border-left: 4px solid #198754; }
.border-left-primary { border-left: 4px solid #0d6efd; }
.text-xs { font-size: 0.75rem; }
</style>

<script>
$(document).ready(function() {
    initDataTable('#inventairesTable', {
        order: [[1, 'desc']] // Tri par date de début décroissant
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
