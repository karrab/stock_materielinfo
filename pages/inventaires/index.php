<?php
$page_title = 'Inventaires';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('inventaires', 'view');
$db = Database::getInstance();

$search = $_GET['search'] ?? '';
$etat = $_GET['etat'] ?? '';

$sql = "SELECT i.*,
               ei.nom as equipe_nom,
               (SELECT COUNT(*) FROM ligne_inventaires WHERE inventaire_id = i.id) as nb_articles,
               (SELECT COUNT(*) FROM ligne_inventaires WHERE inventaire_id = i.id AND ecart != 0) as nb_ecarts
        FROM inventaires i
        LEFT JOIN equipes_inventaire ei ON i.equipe_id = ei.id
        WHERE 1=1";

$params = [];

if (!empty($search)) {
    $sql .= " AND (i.reference LIKE :search OR ei.nom LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($etat)) {
    $sql .= " AND i.etat = :etat";
    $params[':etat'] = $etat;
}

$sql .= " ORDER BY i.date_debut DESC, i.id DESC";

$stmt = $db->getConnection()->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$inventaires = $stmt->fetchAll();
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-clipboard-check"></i> Inventaires</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                            <li class="breadcrumb-item active">Inventaires</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <?php if ($auth->hasPermission('inventaires', 'create')): ?>
                        <a href="<?php echo BASE_URL; ?>/pages/inventaires/create.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Nouvel inventaire
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-list-ul"></i> Liste des inventaires
                </div>
                <div class="card-body">
                    <!-- Filtres -->
                    <form method="GET" class="mb-3">
                        <div class="row g-2">
                            <div class="col-md-5">
                                <input type="text" class="form-control" name="search" placeholder="Rechercher..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="etat">
                                    <option value="">Tous les états</option>
                                    <option value="en_cours" <?php echo $etat == 'en_cours' ? 'selected' : ''; ?>>En cours</option>
                                    <option value="valide" <?php echo $etat == 'valide' ? 'selected' : ''; ?>>Validé</option>
                                    <option value="cloture" <?php echo $etat == 'cloture' ? 'selected' : ''; ?>>Clôturé</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Filtrer</button>
                                <?php if (!empty($search) || !empty($etat)): ?>
                                    <a href="<?php echo BASE_URL; ?>/pages/inventaires/index.php" class="btn btn-secondary"><i class="bi bi-x"></i></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>

                    <!-- Tableau -->
                    <div class="table-responsive">
                        <table class="table table-hover" id="inventairesTable">
                            <thead>
                                <tr>
                                    <th>Référence</th>
                                    <th>Date début</th>
                                    <th>Date fin</th>
                                    <th>Équipe</th>
                                    <th class="text-center">État</th>
                                    <th class="text-center">Articles</th>
                                    <th class="text-center">Écarts</th>
                                    <th class="text-center no-sort">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($inventaires) > 0): ?>
                                    <?php foreach ($inventaires as $inv): ?>
                                        <?php
                                        $badge_class = match($inv['etat']) {
                                            'en_cours' => 'bg-warning',
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
                                        ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($inv['reference']); ?></strong></td>
                                            <td><?php echo date('d/m/Y', strtotime($inv['date_debut'])); ?></td>
                                            <td><?php echo $inv['date_fin'] ? date('d/m/Y', strtotime($inv['date_fin'])) : '<span class="text-muted">-</span>'; ?></td>
                                            <td><?php echo htmlspecialchars($inv['equipe_nom'] ?? '-'); ?></td>
                                            <td class="text-center">
                                                <span class="badge <?php echo $badge_class; ?>"><?php echo $etat_label; ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-primary"><?php echo $inv['nb_articles']; ?></span>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($inv['nb_ecarts'] > 0): ?>
                                                    <span class="badge bg-danger"><?php echo $inv['nb_ecarts']; ?></span>
                                                <?php else: ?>
                                                    <span class="text-success"><i class="bi bi-check-circle"></i></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center action-buttons">
                                                <a href="<?php echo BASE_URL; ?>/pages/inventaires/view.php?id=<?php echo $inv['id']; ?>" class="btn btn-sm btn-info" title="Voir">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <?php if ($inv['etat'] == 'en_cours' && $auth->hasPermission('inventaires', 'update')): ?>
                                                    <a href="<?php echo BASE_URL; ?>/pages/inventaires/edit.php?id=<?php echo $inv['id']; ?>" class="btn btn-sm btn-warning" title="Modifier">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($inv['etat'] == 'en_cours' && $auth->hasPermission('inventaires', 'delete')): ?>
                                                    <a href="<?php echo BASE_URL; ?>/pages/inventaires/delete.php?id=<?php echo $inv['id']; ?>" class="btn btn-sm btn-danger" title="Supprimer"
                                                       onclick="return confirm('Voulez-vous vraiment supprimer cet inventaire ?');">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">
                                            <i class="bi bi-inbox"></i> Aucun inventaire trouvé
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    initDataTable('#inventairesTable');
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
