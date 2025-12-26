<?php
$page_title = 'Historique des mouvements';
require_once __DIR__ . '/../../includes/header.php';

$db = Database::getInstance();
$historique = new HistoriqueArticle();

// Récupération des paramètres de filtrage
$code_article = $_GET['code_article'] ?? '';
$designation = $_GET['designation'] ?? '';
$operation = $_GET['operation'] ?? '';
$date_debut = $_GET['date_debut'] ?? '';
$date_fin = $_GET['date_fin'] ?? '';
$article_id = $_GET['article_id'] ?? '';

// Pagination
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = isset($_GET['perPage']) && in_array($_GET['perPage'], PER_PAGE_OPTIONS) ? (int)$_GET['perPage'] : DEFAULT_PER_PAGE;

// Construire les filtres
$filters = [];
if (!empty($code_article)) $filters['code_article'] = $code_article;
if (!empty($designation)) $filters['designation'] = $designation;
if (!empty($operation)) $filters['operation'] = $operation;
if (!empty($date_debut)) $filters['date_debut'] = $date_debut;
if (!empty($date_fin)) $filters['date_fin'] = $date_fin;
if (!empty($article_id)) $filters['article_id'] = $article_id;

// Récupérer les données
$mouvements = $historique->getAll($page, $perPage, $filters);
$total = $historique->count($filters);
$totalPages = ceil($total / $perPage);

// Récupérer les statistiques
$stats = $historique->getStatistiques($date_debut ?: null, $date_fin ?: null);
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-clock-history"></i> Historique des mouvements de stock</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                    <li class="breadcrumb-item active">Historique mouvements</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Statistiques -->
    <?php if (!empty($stats)): ?>
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-graph-up"></i> Statistiques des mouvements
                    <?php if (!empty($date_debut) || !empty($date_fin)): ?>
                        <small class="text-muted">
                            (<?php echo !empty($date_debut) ? 'Du ' . date('d/m/Y', strtotime($date_debut)) : ''; ?>
                            <?php echo !empty($date_fin) ? ' au ' . date('d/m/Y', strtotime($date_fin)) : ''; ?>)
                        </small>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($stats as $stat): ?>
                            <div class="col-md-4">
                                <div class="card mb-3
                                    <?php
                                        echo $stat['operation'] === 'entree' ? 'border-success' :
                                            ($stat['operation'] === 'sortie' ? 'border-danger' : 'border-info');
                                    ?>">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">
                                            <?php
                                                if ($stat['operation'] === 'entree') {
                                                    echo '<i class="bi bi-box-arrow-in-down text-success"></i> Entrées';
                                                } elseif ($stat['operation'] === 'sortie') {
                                                    echo '<i class="bi bi-box-arrow-up text-danger"></i> Sorties';
                                                } else {
                                                    echo '<i class="bi bi-arrow-counterclockwise text-info"></i> Retours';
                                                }
                                            ?>
                                        </h5>
                                        <p class="mb-1"><strong><?php echo number_format($stat['nombre_mouvements'], 0, ',', ' '); ?></strong> mouvements</p>
                                        <p class="mb-0"><strong><?php echo number_format($stat['quantite_totale'], 2, ',', ' '); ?></strong> unités</p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Filtres -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-funnel"></i> Filtres de recherche
        </div>
        <div class="card-body">
            <form method="GET" action="" id="filterForm">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="code_article" class="form-label">Code article</label>
                        <input type="text" class="form-control" id="code_article" name="code_article"
                               value="<?php echo htmlspecialchars($code_article); ?>" placeholder="Rechercher par code">
                    </div>

                    <div class="col-md-3 mb-3">
                        <label for="designation" class="form-label">Désignation</label>
                        <input type="text" class="form-control" id="designation" name="designation"
                               value="<?php echo htmlspecialchars($designation); ?>" placeholder="Rechercher par désignation">
                    </div>

                    <div class="col-md-2 mb-3">
                        <label for="operation" class="form-label">Opération</label>
                        <select class="form-select" id="operation" name="operation">
                            <option value="">Toutes</option>
                            <option value="entree" <?php echo $operation === 'entree' ? 'selected' : ''; ?>>Entrée</option>
                            <option value="sortie" <?php echo $operation === 'sortie' ? 'selected' : ''; ?>>Sortie</option>
                            <option value="retour" <?php echo $operation === 'retour' ? 'selected' : ''; ?>>Retour</option>
                        </select>
                    </div>

                    <div class="col-md-2 mb-3">
                        <label for="date_debut" class="form-label">Date début</label>
                        <input type="date" class="form-control" id="date_debut" name="date_debut"
                               value="<?php echo htmlspecialchars($date_debut); ?>">
                    </div>

                    <div class="col-md-2 mb-3">
                        <label for="date_fin" class="form-label">Date fin</label>
                        <input type="date" class="form-control" id="date_fin" name="date_fin"
                               value="<?php echo htmlspecialchars($date_fin); ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Filtrer
                        </button>
                        <a href="<?php echo BASE_URL; ?>/pages/mouvements/index.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Réinitialiser
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des mouvements -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>
                <i class="bi bi-list"></i> Liste des mouvements
                <span class="badge bg-secondary"><?php echo number_format($total, 0, ',', ' '); ?> résultat(s)</span>
            </span>
            <div>
                <label for="perPage" class="form-label me-2 mb-0">Par page:</label>
                <select id="perPage" name="perPage" class="form-select form-select-sm d-inline-block" style="width: auto;" onchange="changePerPage(this.value)">
                    <?php foreach (PER_PAGE_OPTIONS as $option): ?>
                        <option value="<?php echo $option; ?>" <?php echo $perPage == $option ? 'selected' : ''; ?>>
                            <?php echo $option; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Code Article</th>
                            <th>Désignation</th>
                            <th>Opération</th>
                            <th class="text-end">Quantité</th>
                            <th class="text-end">Stock Avant</th>
                            <th class="text-end">Stock Après</th>
                            <th class="text-end">Stock Min</th>
                            <th class="text-end">Stock Max</th>
                            <th>Utilisateur</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($mouvements)): ?>
                            <tr>
                                <td colspan="10" class="text-center py-4">
                                    <i class="bi bi-inbox"></i> Aucun mouvement trouvé
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($mouvements as $mouvement): ?>
                                <tr>
                                    <td>
                                        <small><?php echo date('d/m/Y H:i', strtotime($mouvement['date_operation'])); ?></small>
                                    </td>
                                    <td>
                                        <code><?php echo htmlspecialchars($mouvement['code_article']); ?></code>
                                    </td>
                                    <td><?php echo htmlspecialchars($mouvement['designation']); ?></td>
                                    <td>
                                        <?php
                                        $badges = [
                                            'entree' => '<span class="badge bg-success"><i class="bi bi-box-arrow-in-down"></i> Entrée</span>',
                                            'sortie' => '<span class="badge bg-danger"><i class="bi bi-box-arrow-up"></i> Sortie</span>',
                                            'retour' => '<span class="badge bg-info"><i class="bi bi-arrow-counterclockwise"></i> Retour</span>'
                                        ];
                                        echo $badges[$mouvement['operation']] ?? $mouvement['operation'];
                                        ?>
                                    </td>
                                    <td class="text-end">
                                        <strong><?php echo number_format($mouvement['qte'], 2, ',', ' '); ?></strong>
                                    </td>
                                    <td class="text-end"><?php echo number_format($mouvement['stock_avant_operation'], 2, ',', ' '); ?></td>
                                    <td class="text-end">
                                        <strong><?php echo number_format($mouvement['stock_apres_operation'], 2, ',', ' '); ?></strong>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($mouvement['stock_apres_operation'] <= $mouvement['stock_min']): ?>
                                            <span class="badge bg-warning text-dark"><?php echo number_format($mouvement['stock_min'], 2, ',', ' '); ?></span>
                                        <?php else: ?>
                                            <?php echo number_format($mouvement['stock_min'], 2, ',', ' '); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($mouvement['stock_apres_operation'] >= $mouvement['stock_max']): ?>
                                            <span class="badge bg-danger"><?php echo number_format($mouvement['stock_max'], 2, ',', ' '); ?></span>
                                        <?php else: ?>
                                            <?php echo number_format($mouvement['stock_max'], 2, ',', ' '); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small><?php echo htmlspecialchars($mouvement['user_nom'] ?? '') . ' ' . htmlspecialchars($mouvement['user_prenom'] ?? ''); ?></small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="card-footer">
                <nav aria-label="Pagination">
                    <ul class="pagination pagination-sm mb-0 justify-content-center">
                        <!-- Première page -->
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=1&perPage=<?php echo $perPage; ?><?php echo http_build_query(array_diff_key($_GET, ['page' => '', 'perPage' => ''])) ? '&' . http_build_query(array_diff_key($_GET, ['page' => '', 'perPage' => ''])) : ''; ?>">
                                <i class="bi bi-chevron-bar-left"></i>
                            </a>
                        </li>

                        <!-- Page précédente -->
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo max(1, $page - 1); ?>&perPage=<?php echo $perPage; ?><?php echo http_build_query(array_diff_key($_GET, ['page' => '', 'perPage' => ''])) ? '&' . http_build_query(array_diff_key($_GET, ['page' => '', 'perPage' => ''])) : ''; ?>">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>

                        <!-- Numéros de page -->
                        <?php
                        $start = max(1, $page - 2);
                        $end = min($totalPages, $page + 2);

                        for ($i = $start; $i <= $end; $i++):
                        ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&perPage=<?php echo $perPage; ?><?php echo http_build_query(array_diff_key($_GET, ['page' => '', 'perPage' => ''])) ? '&' . http_build_query(array_diff_key($_GET, ['page' => '', 'perPage' => ''])) : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <!-- Page suivante -->
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo min($totalPages, $page + 1); ?>&perPage=<?php echo $perPage; ?><?php echo http_build_query(array_diff_key($_GET, ['page' => '', 'perPage' => ''])) ? '&' . http_build_query(array_diff_key($_GET, ['page' => '', 'perPage' => ''])) : ''; ?>">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>

                        <!-- Dernière page -->
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $totalPages; ?>&perPage=<?php echo $perPage; ?><?php echo http_build_query(array_diff_key($_GET, ['page' => '', 'perPage' => ''])) ? '&' . http_build_query(array_diff_key($_GET, ['page' => '', 'perPage' => ''])) : ''; ?>">
                                <i class="bi bi-chevron-bar-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
                <div class="text-center mt-2">
                    <small class="text-muted">
                        Page <?php echo $page; ?> sur <?php echo $totalPages; ?>
                        (<?php echo number_format($total, 0, ',', ' '); ?> résultat(s))
                    </small>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function changePerPage(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('perPage', value);
    url.searchParams.set('page', '1'); // Reset to first page
    window.location.href = url.toString();
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
