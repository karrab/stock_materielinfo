<?php
$page_title = 'Retours';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('retours', 'view');
$db = Database::getInstance();

$search = $_GET['search'] ?? '';
$service_id = $_GET['service_id'] ?? '';
$date_debut = $_GET['date_debut'] ?? '';
$date_fin = $_GET['date_fin'] ?? '';

$sql = "SELECT r.*,
               s.nom as service_nom,
               e.nom as employe_nom, e.prenom as employe_prenom,
               COUNT(lr.id) as nb_articles,
               SUM(lr.qte_retour) as qte_totale,
               u.nom as user_nom, u.prenom as user_prenom
        FROM retours r
        INNER JOIN services s ON r.service_id = s.id
        INNER JOIN employes e ON r.employe_id = e.id
        LEFT JOIN ligne_retours lr ON r.id = lr.retour_id
        LEFT JOIN users u ON r.user_id = u.id
        WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (r.notes LIKE :search OR s.nom LIKE :search OR e.nom LIKE :search OR e.prenom LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($service_id)) {
    $sql .= " AND r.service_id = :service_id";
    $params[':service_id'] = $service_id;
}

if (!empty($date_debut)) {
    $sql .= " AND r.date >= :date_debut";
    $params[':date_debut'] = $date_debut;
}

if (!empty($date_fin)) {
    $sql .= " AND r.date <= :date_fin";
    $params[':date_fin'] = $date_fin;
}

$sql .= " GROUP BY r.id ORDER BY r.date DESC, r.id DESC";

$stmt = $db->getConnection()->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$retours = $stmt->fetchAll();

// Liste services pour filtre
$db->prepare("SELECT id, nom FROM services ORDER BY nom");
$services = $db->fetchAll();
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-box-arrow-in-up"></i> Retours de matériel</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                            <li class="breadcrumb-item active">Retours</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <?php if ($auth->hasPermission('retours', 'create')): ?>
                        <a href="<?php echo BASE_URL; ?>/pages/retours/create.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Nouveau retour
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
                    <i class="bi bi-list-ul"></i> Liste des retours
                </div>
                <div class="card-body">
                    <!-- Filtres -->
                    <form method="GET" class="mb-3">
                        <div class="row">
                            <div class="col-md-3">
                                <input type="text" class="form-control" name="search" placeholder="Rechercher..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="service_id">
                                    <option value="">Tous les services</option>
                                    <?php foreach ($services as $s): ?>
                                        <option value="<?php echo $s['id']; ?>" <?php echo $service_id == $s['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($s['nom']); ?>
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
                                <?php if (!empty($search) || !empty($service_id) || !empty($date_debut) || !empty($date_fin)): ?>
                                    <a href="<?php echo BASE_URL; ?>/pages/retours/index.php" class="btn btn-secondary"><i class="bi bi-x"></i></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>

                    <!-- Tableau -->
                    <div class="table-responsive">
                        <table class="table table-hover" id="retoursTable">
                            <thead>
                                <tr>
                                    <th>N°</th>
                                    <th>Date</th>
                                    <th>Service</th>
                                    <th>Employé</th>
                                    <th class="text-center">Articles</th>
                                    <th class="text-center">Quantité totale</th>
                                    <th>Fichier</th>
                                    <th class="text-center no-sort no-export">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($retours) > 0): ?>
                                    <?php foreach ($retours as $retour): ?>
                                        <tr>
                                            <td><strong>#<?php echo $retour['id']; ?></strong></td>
                                            <td><?php echo date('d/m/Y', strtotime($retour['date'])); ?></td>
                                            <td><?php echo htmlspecialchars($retour['service_nom']); ?></td>
                                            <td><?php echo htmlspecialchars($retour['employe_nom'] . ' ' . $retour['employe_prenom']); ?></td>
                                            <td class="text-center"><span class="badge bg-info"><?php echo $retour['nb_articles']; ?></span></td>
                                            <td class="text-center"><?php echo number_format($retour['qte_totale'], 2, ',', ' '); ?></td>
                                            <td>
                                                <?php if (!empty($retour['fichier'])): ?>
                                                    <a href="<?php echo UPLOAD_ENTREES_URL . '/' . $retour['fichier']; ?>"
                                                       target="_blank" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-download"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center action-buttons no-print">
                                                <a href="<?php echo BASE_URL; ?>/pages/retours/view.php?id=<?php echo $retour['id']; ?>"
                                                   class="btn btn-sm btn-info" title="Voir"><i class="bi bi-eye"></i></a>
                                                <a href="<?php echo BASE_URL; ?>/pages/retours/pdf.php?id=<?php echo $retour['id']; ?>"
                                                   class="btn btn-sm btn-secondary" title="PDF" target="_blank"><i class="bi bi-file-pdf"></i></a>
                                                <?php if ($auth->hasPermission('retours', 'update')): ?>
                                                    <a href="<?php echo BASE_URL; ?>/pages/retours/edit.php?id=<?php echo $retour['id']; ?>"
                                                       class="btn btn-sm btn-warning" title="Modifier"><i class="bi bi-pencil"></i></a>
                                                <?php endif; ?>
                                                <?php if ($auth->hasPermission('retours', 'delete')): ?>
                                                    <a href="<?php echo BASE_URL; ?>/pages/retours/delete.php?id=<?php echo $retour['id']; ?>"
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
    initDataTable('#retoursTable');
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
