<?php
$page_title = 'Entrées';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('entrees', 'view');
$db = Database::getInstance();

$search = $_GET['search'] ?? '';
$fournisseur_id = $_GET['fournisseur_id'] ?? '';
$date_debut = $_GET['date_debut'] ?? '';
$date_fin = $_GET['date_fin'] ?? '';

$sql = "SELECT e.*, f.nom_complet as fournisseur, COUNT(le.id) as nb_articles,
               SUM(le.qte_entree) as qte_totale
        FROM entrees e
        INNER JOIN fournisseurs f ON e.fournisseur_id = f.id
        LEFT JOIN ligne_entrees le ON e.id = le.entree_id
        WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (e.notes LIKE :search OR f.nom_complet LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($fournisseur_id)) {
    $sql .= " AND e.fournisseur_id = :fournisseur_id";
    $params[':fournisseur_id'] = $fournisseur_id;
}

if (!empty($date_debut)) {
    $sql .= " AND e.date >= :date_debut";
    $params[':date_debut'] = $date_debut;
}

if (!empty($date_fin)) {
    $sql .= " AND e.date <= :date_fin";
    $params[':date_fin'] = $date_fin;
}

$sql .= " GROUP BY e.id ORDER BY e.date DESC, e.id DESC";

$stmt = $db->getConnection()->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$entrees = $stmt->fetchAll();

// Liste fournisseurs pour filtre
$db->prepare("SELECT id, nom_complet FROM fournisseurs WHERE actif = 1 ORDER BY nom_complet");
$fournisseurs = $db->fetchAll();
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-box-arrow-in-down"></i> Entrées de matériel</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                            <li class="breadcrumb-item active">Entrées</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <?php if ($auth->hasPermission('entrees', 'create')): ?>
                        <a href="<?php echo BASE_URL; ?>/pages/entrees/create.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Nouvelle entrée
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
                    <i class="bi bi-list-ul"></i> Liste des entrées
                </div>
                <div class="card-body">
                    <!-- Filtres -->
                    <form method="GET" class="mb-3">
                        <div class="row">
                            <div class="col-md-3">
                                <input type="text" class="form-control" name="search" placeholder="Rechercher..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="fournisseur_id">
                                    <option value="">Tous les fournisseurs</option>
                                    <?php foreach ($fournisseurs as $f): ?>
                                        <option value="<?php echo $f['id']; ?>" <?php echo $fournisseur_id == $f['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($f['nom_complet']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="date" class="form-control" name="date_debut" value="<?php echo $date_debut; ?>" placeholder="Date début">
                            </div>
                            <div class="col-md-2">
                                <input type="date" class="form-control" name="date_fin" value="<?php echo $date_fin; ?>" placeholder="Date fin">
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Rechercher</button>
                                <?php if (!empty($search) || !empty($fournisseur_id) || !empty($date_debut) || !empty($date_fin)): ?>
                                    <a href="<?php echo BASE_URL; ?>/pages/entrees/index.php" class="btn btn-secondary"><i class="bi bi-x"></i></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>

                    <!-- Tableau -->
                    <div class="table-responsive">
                        <table class="table table-hover" id="entreesTable">
                            <thead>
                                <tr>
                                    <th>N°</th>
                                    <th>Date</th>
                                    <th>Fournisseur</th>
                                    <th class="text-center">Articles</th>
                                    <th class="text-center">Quantité totale</th>
                                    <th>Fichier</th>
                                    <th class="text-center no-sort">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($entrees) > 0): ?>
                                    <?php foreach ($entrees as $entree): ?>
                                        <tr>
                                            <td><strong>#<?php echo $entree['id']; ?></strong></td>
                                            <td><?php echo date('d/m/Y', strtotime($entree['date'])); ?></td>
                                            <td><?php echo htmlspecialchars($entree['fournisseur']); ?></td>
                                            <td class="text-center"><span class="badge bg-info"><?php echo $entree['nb_articles']; ?></span></td>
                                            <td class="text-center"><?php echo number_format($entree['qte_totale'], 2, ',', ' '); ?></td>
                                            <td>
                                                <?php if (!empty($entree['fichier'])): ?>
                                                    <a href="<?php echo UPLOAD_ENTREES_URL . '/' . $entree['fichier']; ?>"
                                                       target="_blank" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-download"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center action-buttons no-print">
                                                <a href="<?php echo BASE_URL; ?>/pages/entrees/view.php?id=<?php echo $entree['id']; ?>"
                                                   class="btn btn-sm btn-info" title="Voir"><i class="bi bi-eye"></i></a>
                                                <a href="<?php echo BASE_URL; ?>/pages/entrees/pdf.php?id=<?php echo $entree['id']; ?>"
                                                   class="btn btn-sm btn-secondary" title="PDF" target="_blank"><i class="bi bi-file-pdf"></i></a>
                                                <?php if ($auth->hasPermission('entrees', 'update')): ?>
                                                    <a href="<?php echo BASE_URL; ?>/pages/entrees/edit.php?id=<?php echo $entree['id']; ?>"
                                                       class="btn btn-sm btn-warning" title="Modifier"><i class="bi bi-pencil"></i></a>
                                                <?php endif; ?>
                                                <?php if ($auth->hasPermission('entrees', 'delete')): ?>
                                                    <a href="<?php echo BASE_URL; ?>/pages/entrees/delete.php?id=<?php echo $entree['id']; ?>"
                                                       class="btn btn-sm btn-danger delete-confirm" title="Supprimer"
                                                       onclick="return confirm('Voulez-vous vraiment supprimer cette entrée ?');"><i class="bi bi-trash"></i></a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">
                                            <i class="bi bi-inbox"></i> Aucune entrée trouvée
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
    initDataTable('#entreesTable');
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
