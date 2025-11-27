<?php
$page_title = 'Articles';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('articles', 'view');
$db = Database::getInstance();

$search = $_GET['search'] ?? '';
$alerte_stock = $_GET['alerte_stock'] ?? '';

$sql = "SELECT * FROM articles WHERE actif = 1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (code_article LIKE :search OR designation LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if ($alerte_stock == 'faible') {
    $sql .= " AND qte_disponible < stock_min AND stock_min > 0";
} elseif ($alerte_stock == 'rupture') {
    $sql .= " AND qte_disponible <= 0";
}

$sql .= " ORDER BY designation ASC";

$stmt = $db->getConnection()->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$articles = $stmt->fetchAll();
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-box-seam"></i> Articles</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                            <li class="breadcrumb-item active">Articles</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <?php if ($auth->hasPermission('articles', 'create')): ?>
                        <a href="<?php echo BASE_URL; ?>/pages/articles/create.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Nouvel article
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
                    <i class="bi bi-list-ul"></i> Liste des articles
                </div>
                <div class="card-body">
                    <!-- Filtres -->
                    <form method="GET" class="mb-3">
                        <div class="row">
                            <div class="col-md-5">
                                <input type="text" class="form-control" name="search" placeholder="Rechercher (code ou désignation)..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="alerte_stock">
                                    <option value="">Tous les articles</option>
                                    <option value="faible" <?php echo $alerte_stock == 'faible' ? 'selected' : ''; ?>>Stock faible</option>
                                    <option value="rupture" <?php echo $alerte_stock == 'rupture' ? 'selected' : ''; ?>>En rupture</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Rechercher</button>
                                <?php if (!empty($search) || !empty($alerte_stock)): ?>
                                    <a href="<?php echo BASE_URL; ?>/pages/articles/index.php" class="btn btn-secondary"><i class="bi bi-x"></i></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>

                    <!-- Tableau -->
                    <div class="table-responsive">
                        <table class="table table-hover table-sm" id="articlesTable">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Désignation</th>
                                    <th class="text-end">Stock initial</th>
                                    <th class="text-end">Entrées</th>
                                    <th class="text-end">Sorties</th>
                                    <th class="text-end">Disponible</th>
                                    <th class="text-center">Statut</th>
                                    <th class="text-center no-sort">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($articles) > 0): ?>
                                    <?php foreach ($articles as $article): ?>
                                        <?php
                                        $stock_status = 'good';
                                        $stock_label = 'OK';
                                        $stock_class = 'bg-success';

                                        if ($article['qte_disponible'] <= 0) {
                                            $stock_status = 'critical';
                                            $stock_label = 'Rupture';
                                            $stock_class = 'bg-danger';
                                        } elseif ($article['stock_min'] > 0 && $article['qte_disponible'] < $article['stock_min']) {
                                            $stock_status = 'low';
                                            $stock_label = 'Faible';
                                            $stock_class = 'bg-warning';
                                        } elseif ($article['stock_max'] > 0 && $article['qte_disponible'] > $article['stock_max']) {
                                            $stock_status = 'high';
                                            $stock_label = 'Élevé';
                                            $stock_class = 'bg-info';
                                        }
                                        ?>
                                        <tr>
                                            <td><code><?php echo htmlspecialchars($article['code_article']); ?></code></td>
                                            <td><strong><?php echo htmlspecialchars($article['designation']); ?></strong></td>
                                            <td class="text-end"><?php echo number_format($article['stock_initial'], 2, ',', ' '); ?></td>
                                            <td class="text-end text-success"><?php echo number_format($article['qte_entree'], 2, ',', ' '); ?></td>
                                            <td class="text-end text-danger"><?php echo number_format($article['qte_sortie'], 2, ',', ' '); ?></td>
                                            <td class="text-end"><strong><?php echo number_format($article['qte_disponible'], 2, ',', ' '); ?></strong></td>
                                            <td class="text-center">
                                                <span class="badge <?php echo $stock_class; ?>"><?php echo $stock_label; ?></span>
                                            </td>
                                            <td class="text-center action-buttons no-print">
                                                <a href="<?php echo BASE_URL; ?>/pages/articles/view.php?id=<?php echo $article['id']; ?>"
                                                   class="btn btn-sm btn-info" title="Voir"><i class="bi bi-eye"></i></a>
                                                <?php if ($auth->hasPermission('articles', 'update')): ?>
                                                    <a href="<?php echo BASE_URL; ?>/pages/articles/edit.php?id=<?php echo $article['id']; ?>"
                                                       class="btn btn-sm btn-warning" title="Modifier"><i class="bi bi-pencil"></i></a>
                                                <?php endif; ?>
                                                <?php if ($auth->hasPermission('articles', 'delete')): ?>
                                                    <a href="<?php echo BASE_URL; ?>/pages/articles/delete.php?id=<?php echo $article['id']; ?>"
                                                       class="btn btn-sm btn-danger delete-confirm" title="Supprimer"
                                                       onclick="return confirm('Voulez-vous vraiment supprimer cet article ?');"><i class="bi bi-trash"></i></a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">
                                            <i class="bi bi-inbox"></i> Aucun article trouvé
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
    initDataTable('#articlesTable', {
        order: [[5, 'asc']] // Tri par quantité disponible
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
