<?php
$page_title = 'Historique des mouvements';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('mouvements', 'view');
$db = Database::getInstance();
$historique = new HistoriqueArticle();

// Récupération des paramètres de filtrage
$code_article = $_GET['code_article'] ?? '';
$designation = $_GET['designation'] ?? '';
$operation = $_GET['operation'] ?? '';
$date_debut = $_GET['date_debut'] ?? '';
$date_fin = $_GET['date_fin'] ?? '';
$article_id = $_GET['article_id'] ?? '';

// Construire les filtres
$filters = [];
if (!empty($code_article)) $filters['code_article'] = $code_article;
if (!empty($designation)) $filters['designation'] = $designation;
if (!empty($operation)) $filters['operation'] = $operation;
if (!empty($date_debut)) $filters['date_debut'] = $date_debut;
if (!empty($date_fin)) $filters['date_fin'] = $date_fin;
if (!empty($article_id)) $filters['article_id'] = $article_id;

// Récupérer TOUS les mouvements (sans pagination pour DataTables)
$mouvements = $historique->getAllWithoutPagination($filters);

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
                            <div class="col-md-3">
                                <div class="card mb-3
                                    <?php
                                        echo $stat['operation'] === 'entree' ? 'border-success' :
                                            ($stat['operation'] === 'sortie' ? 'border-danger' :
                                            ($stat['operation'] === 'retour' ? 'border-info' : 'border-warning'));
                                    ?>">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">
                                            <?php
                                                if ($stat['operation'] === 'entree') {
                                                    echo '<i class="bi bi-box-arrow-in-down text-success"></i> Entrées';
                                                } elseif ($stat['operation'] === 'sortie') {
                                                    echo '<i class="bi bi-box-arrow-up text-danger"></i> Sorties';
                                                } elseif ($stat['operation'] === 'retour') {
                                                    echo '<i class="bi bi-arrow-counterclockwise text-info"></i> Retours Employé';
                                                } elseif ($stat['operation'] === 'retour_fournisseur') {
                                                    echo '<i class="bi bi-box-arrow-left text-warning"></i> Retours Fournisseur';
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
                            <option value="retour" <?php echo $operation === 'retour' ? 'selected' : ''; ?>>Retour Employé</option>
                            <option value="retour_fournisseur" <?php echo $operation === 'retour_fournisseur' ? 'selected' : ''; ?>>Retour Fournisseur</option>
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
        <div class="card-header">
            <i class="bi bi-list"></i> Liste des mouvements
            <span class="badge bg-secondary"><?php echo number_format(count($mouvements), 0, ',', ' '); ?> résultat(s)</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-striped table-bordered" id="mouvementsTable">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Code Article</th>
                            <th>Désignation</th>
                            <th>Opération</th>
                            <th class="text-end">Quantité_A</th>
                            <th class="text-end">Quantité</th>
                            <th class="text-end">Stock Avant</th>
                            <th class="text-end">Stock Après</th>
                            <th class="text-end">Stock Min</th>
                            <th class="text-end">Stock Max</th>
                            <th>Utilisateur</th>
                            <th>Commentaire</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($mouvements)): ?>
                            <tr>
                                <td colspan="12" class="text-center py-4">
                                    <i class="bi bi-inbox"></i> Aucun mouvement trouvé
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($mouvements as $mouvement): ?>
                                <tr>
                                    <td data-order="<?php echo strtotime($mouvement['date_operation']); ?>">
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
                                            'retour' => '<span class="badge bg-info"><i class="bi bi-arrow-counterclockwise"></i> Retour Employé</span>',
                                            'retour_fournisseur' => '<span class="badge bg-warning text-dark"><i class="bi bi-box-arrow-left"></i> Retour Fournisseur</span>'
                                        ];
                                        echo $badges[$mouvement['operation']] ?? $mouvement['operation'];
                                        ?>
                                    </td>
                                    <td class="text-end" data-order="<?php echo $mouvement['qte']; ?>">
                                        <?php
                                        // Colonne Quantité_A : afficher le type de quantité selon l'opération
                                        $qte_value = number_format($mouvement['qte'], 2, ',', ' ');
                                        if ($mouvement['operation'] === 'entree') {
                                            echo '<span class="text-success"><strong>Qté Entrée:</strong> ' . $qte_value . '</span>';
                                        } elseif ($mouvement['operation'] === 'sortie') {
                                            echo '<span class="text-danger"><strong>Qté Sortie:</strong> ' . $qte_value . '</span>';
                                        } elseif ($mouvement['operation'] === 'retour') {
                                            echo '<span class="text-info"><strong>Qté Retour Employé:</strong> ' . $qte_value . '</span>';
                                        } elseif ($mouvement['operation'] === 'retour_fournisseur') {
                                            echo '<span class="text-warning"><strong>Qté Retour Fournisseur:</strong> ' . $qte_value . '</span>';
                                        }
                                        ?>
                                    </td>
                                    <td class="text-end" data-order="<?php echo $mouvement['qte']; ?>">
                                        <?php
                                        // Afficher le bon badge selon l'opération
                                        $qte = number_format($mouvement['qte'], 2, ',', ' ');
                                        if ($mouvement['operation'] === 'entree') {
                                            echo '<span class="badge bg-success">+ ' . $qte . '</span>';
                                        } elseif ($mouvement['operation'] === 'sortie') {
                                            echo '<span class="badge bg-danger">- ' . $qte . '</span>';
                                        } elseif ($mouvement['operation'] === 'retour') {
                                            echo '<span class="badge bg-info">+ ' . $qte . '</span>';
                                        } elseif ($mouvement['operation'] === 'retour_fournisseur') {
                                            echo '<span class="badge bg-warning text-dark">- ' . $qte . '</span>';
                                        }
                                        ?>
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
                                    <td>
                                        <?php if (!empty($mouvement['commentaire'])): ?>
                                            <small><?php echo htmlspecialchars($mouvement['commentaire']); ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialiser DataTables avec tri par colonnes
    $('#mouvementsTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/fr-FR.json'
        },
        pageLength: 50,
        lengthMenu: [[10, 50, 100, 200, 500, -1], [10, 50, 100, 200, 500, "Tous"]],
        order: [[0, 'desc']], // Tri par date décroissant par défaut
        columnDefs: [
            {
                targets: '_all', // Toutes les colonnes sont triables
                orderable: true
            }
        ],
        stateSave: true,
        stateDuration: 60 * 60 * 24 * 7, // 7 jours
        colReorder: true,
        fixedHeader: true,
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"Bf>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        buttons: [
            {
                extend: 'copy',
                text: '<i class="bi bi-clipboard"></i> Copier',
                className: 'btn btn-sm btn-secondary',
                exportOptions: {
                    columns: ':visible'
                }
            },
            {
                extend: 'excel',
                text: '<i class="bi bi-file-earmark-excel"></i> Excel',
                className: 'btn btn-sm btn-success',
                exportOptions: {
                    columns: ':visible'
                }
            },
            {
                extend: 'pdf',
                text: '<i class="bi bi-file-earmark-pdf"></i> PDF',
                className: 'btn btn-sm btn-danger',
                exportOptions: {
                    columns: ':visible'
                },
                orientation: 'landscape',
                pageSize: 'A4'
            },
            {
                extend: 'print',
                text: '<i class="bi bi-printer"></i> Imprimer',
                className: 'btn btn-sm btn-info',
                exportOptions: {
                    columns: ':visible'
                }
            }
        ]
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
