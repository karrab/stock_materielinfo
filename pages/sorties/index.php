<?php
$page_title = 'Sorties';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('sorties', 'view');
$db = Database::getInstance();

// Filtres
$search = $_GET['search'] ?? '';
$service_id = $_GET['service_id'] ?? '';
$date_debut = $_GET['date_debut'] ?? '';
$date_fin = $_GET['date_fin'] ?? '';

$sql = "SELECT s.*,
               serv.nom as service_nom,
               emp.nom as employe_nom, emp.prenom as employe_prenom,
               serv_aff.nom as service_affectation_nom,
               emp_aff.nom as employe_affectation_nom, emp_aff.prenom as employe_affectation_prenom,
               bur.code_local,
               arm.nom as armoire_nom,
               (SELECT COUNT(*) FROM ligne_sorties WHERE sortie_id = s.id) as nb_articles,
               (SELECT SUM(qte_sortie) FROM ligne_sorties WHERE sortie_id = s.id) as qte_totale
        FROM sorties s
        INNER JOIN services serv ON s.service_id = serv.id
        INNER JOIN employes emp ON s.employe_id = emp.id
        LEFT JOIN services serv_aff ON s.service_affectation_id = serv_aff.id
        LEFT JOIN employes emp_aff ON s.employe_affectation_id = emp_aff.id
        LEFT JOIN bureaux bur ON s.bureau_id = bur.id
        LEFT JOIN armoires arm ON s.armoire_id = arm.id
        WHERE 1=1";

$params = [];

if (!empty($search)) {
    $sql .= " AND (serv.nom LIKE :search OR emp.nom LIKE :search OR emp.prenom LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($service_id)) {
    $sql .= " AND s.service_id = :service_id";
    $params[':service_id'] = $service_id;
}

if (!empty($date_debut)) {
    $sql .= " AND s.date >= :date_debut";
    $params[':date_debut'] = $date_debut;
}

if (!empty($date_fin)) {
    $sql .= " AND s.date <= :date_fin";
    $params[':date_fin'] = $date_fin;
}

$sql .= " ORDER BY s.date DESC, s.id DESC";

$stmt = $db->getConnection()->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$sorties = $stmt->fetchAll();

// Récupérer liste services pour le filtre
$db->prepare("SELECT id, nom FROM services ORDER BY nom");
$services = $db->fetchAll();
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-box-arrow-up"></i> Sorties</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                            <li class="breadcrumb-item active">Sorties</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <?php if ($auth->hasPermission('sorties', 'create')): ?>
                        <a href="<?php echo BASE_URL; ?>/pages/sorties/create.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Nouvelle sortie
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
                    <i class="bi bi-list-ul"></i> Liste des sorties
                </div>
                <div class="card-body">
                    <!-- Filtres -->
                    <form method="GET" class="mb-3">
                        <div class="row g-2">
                            <div class="col-md-3">
                                <input type="text" class="form-control" name="search" placeholder="Rechercher (service ou employé)..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="service_id">
                                    <option value="">Tous les services</option>
                                    <?php foreach ($services as $service): ?>
                                        <option value="<?php echo $service['id']; ?>" <?php echo $service_id == $service['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($service['nom']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="date" class="form-control" name="date_debut" placeholder="Date début" value="<?php echo htmlspecialchars($date_debut); ?>">
                            </div>
                            <div class="col-md-2">
                                <input type="date" class="form-control" name="date_fin" placeholder="Date fin" value="<?php echo htmlspecialchars($date_fin); ?>">
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Filtrer</button>
                                <?php if (!empty($search) || !empty($service_id) || !empty($date_debut) || !empty($date_fin)): ?>
                                    <a href="<?php echo BASE_URL; ?>/pages/sorties/index.php" class="btn btn-secondary"><i class="bi bi-x"></i></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>

                    <!-- Tableau -->
                    <div class="table-responsive">
                        <table class="table table-hover table-sm" id="sortiesTable">
                            <thead>
                                <tr>
                                    <th>N°</th>
                                    <th>Date</th>
                                    <th>Service demandeur</th>
                                    <th>Employé demandeur</th>
                                    <th>Affectation</th>
                                    <th class="text-center">Articles</th>
                                    <th class="text-end">Qté totale</th>
                                    <th class="text-center">Fichier</th>
                                    <th class="text-center no-sort no-export">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($sorties) > 0): ?>
                                    <?php foreach ($sorties as $sortie): ?>
                                        <tr>
                                            <td><strong>#<?php echo $sortie['id']; ?></strong></td>
                                            <td><?php echo date('d/m/Y', strtotime($sortie['date'])); ?></td>
                                            <td><?php echo htmlspecialchars($sortie['service_nom']); ?></td>
                                            <td><?php echo htmlspecialchars($sortie['employe_nom'] . ' ' . $sortie['employe_prenom']); ?></td>
                                            <td>
                                                <?php if (!empty($sortie['service_affectation_nom'])): ?>
                                                    <small>
                                                        <i class="bi bi-building"></i> <?php echo htmlspecialchars($sortie['service_affectation_nom']); ?><br>
                                                        <?php if (!empty($sortie['employe_affectation_nom'])): ?>
                                                            <i class="bi bi-person"></i> <?php echo htmlspecialchars($sortie['employe_affectation_nom'] . ' ' . $sortie['employe_affectation_prenom']); ?><br>
                                                        <?php endif; ?>
                                                        <?php if (!empty($sortie['code_local'])): ?>
                                                            <i class="bi bi-door-open"></i> <?php echo htmlspecialchars($sortie['code_local']); ?><br>
                                                        <?php endif; ?>
                                                        <?php if (!empty($sortie['armoire_nom'])): ?>
                                                            <i class="bi bi-archive"></i> <?php echo htmlspecialchars($sortie['armoire_nom']); ?>
                                                        <?php endif; ?>
                                                    </small>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-primary"><?php echo $sortie['nb_articles']; ?></span>
                                            </td>
                                            <td class="text-end">
                                                <strong><?php echo number_format($sortie['qte_totale'], 2, ',', ' '); ?></strong>
                                            </td>
                                            <td class="text-center">
                                                <?php if (!empty($sortie['fichier'])): ?>
                                                    <a href="<?php echo BASE_URL . '/uploads/sorties/' . $sortie['fichier']; ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Télécharger">
                                                        <i class="bi bi-file-earmark-arrow-down"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center action-buttons no-print">
                                                <a href="<?php echo BASE_URL; ?>/pages/sorties/view.php?id=<?php echo $sortie['id']; ?>" class="btn btn-sm btn-info" title="Voir">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="<?php echo BASE_URL; ?>/pages/sorties/pdf.php?id=<?php echo $sortie['id']; ?>" class="btn btn-sm btn-danger" title="PDF" target="_blank">
                                                    <i class="bi bi-file-pdf"></i>
                                                </a>
                                                <?php if ($auth->hasPermission('sorties', 'update')): ?>
                                                    <a href="<?php echo BASE_URL; ?>/pages/sorties/edit.php?id=<?php echo $sortie['id']; ?>" class="btn btn-sm btn-warning" title="Modifier">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($auth->hasPermission('sorties', 'delete')): ?>
                                                    <a href="<?php echo BASE_URL; ?>/pages/sorties/delete.php?id=<?php echo $sortie['id']; ?>" class="btn btn-sm btn-danger delete-confirm" title="Supprimer"
                                                       onclick="return confirm('Voulez-vous vraiment supprimer cette sortie ? Le stock sera recalculé.');">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center text-muted">
                                            <i class="bi bi-inbox"></i> Aucune sortie trouvée
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
    initDataTable('#sortiesTable', {
        order: [[0, 'desc']] // Tri par ID décroissant (plus récentes en premier)
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
