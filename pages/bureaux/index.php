<?php
$page_title = 'Bureaux';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('bureaux', 'view');
$db = Database::getInstance();

// Filtres
$search = $_GET['search'] ?? '';
$service_id = $_GET['service_id'] ?? '';

$sql = "SELECT b.*,
               s.nom as service_nom,
               e.nom as employe_nom, e.prenom as employe_prenom
        FROM bureaux b
        LEFT JOIN services s ON b.service_id = s.id
        LEFT JOIN employes e ON b.employe_id = e.id
        WHERE 1=1";

$params = [];

if (!empty($search)) {
    $sql .= " AND (b.code_local LIKE :search OR b.batiment LIKE :search OR b.etage LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($service_id)) {
    $sql .= " AND b.service_id = :service_id";
    $params[':service_id'] = $service_id;
}

$sql .= " ORDER BY b.code_local ASC";

$stmt = $db->getConnection()->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$bureaux = $stmt->fetchAll();

// Services pour filtre
$db->prepare("SELECT id, nom FROM services ORDER BY nom");
$services = $db->fetchAll();
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-door-open"></i> Bureaux</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                            <li class="breadcrumb-item active">Bureaux</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <?php if ($auth->hasPermission('bureaux', 'create')): ?>
                        <a href="<?php echo BASE_URL; ?>/pages/bureaux/create.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Nouveau bureau
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
                    <i class="bi bi-list-ul"></i> Liste des bureaux
                </div>
                <div class="card-body">
                    <!-- Filtres -->
                    <form method="GET" class="mb-3">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <input type="text" class="form-control" name="search" placeholder="Rechercher (code, bâtiment, étage)..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="service_id">
                                    <option value="">Tous les services</option>
                                    <?php foreach ($services as $service): ?>
                                        <option value="<?php echo $service['id']; ?>" <?php echo $service_id == $service['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($service['nom']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Filtrer</button>
                                <?php if (!empty($search) || !empty($service_id)): ?>
                                    <a href="<?php echo BASE_URL; ?>/pages/bureaux/index.php" class="btn btn-secondary"><i class="bi bi-x"></i></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>

                    <!-- Tableau -->
                    <div class="table-responsive">
                        <table class="table table-hover" id="bureauxTable">
                            <thead>
                                <tr>
                                    <th>Code local</th>
                                    <th>Bâtiment</th>
                                    <th>Étage</th>
                                    <th>Service</th>
                                    <th>Employé</th>
                                    <th class="text-center no-sort">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($bureaux) > 0): ?>
                                    <?php foreach ($bureaux as $bureau): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($bureau['code_local']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($bureau['batiment']); ?></td>
                                            <td><?php echo htmlspecialchars($bureau['etage']); ?></td>
                                            <td><?php echo $bureau['service_nom'] ? htmlspecialchars($bureau['service_nom']) : '<span class="text-muted">-</span>'; ?></td>
                                            <td><?php echo $bureau['employe_nom'] ? htmlspecialchars($bureau['employe_nom'] . ' ' . $bureau['employe_prenom']) : '<span class="text-muted">-</span>'; ?></td>
                                            <td class="text-center action-buttons">
                                                <a href="<?php echo BASE_URL; ?>/pages/bureaux/view.php?id=<?php echo $bureau['id']; ?>" class="btn btn-sm btn-info" title="Voir">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <?php if ($auth->hasPermission('bureaux', 'update')): ?>
                                                    <a href="<?php echo BASE_URL; ?>/pages/bureaux/edit.php?id=<?php echo $bureau['id']; ?>" class="btn btn-sm btn-warning" title="Modifier">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($auth->hasPermission('bureaux', 'delete')): ?>
                                                    <a href="<?php echo BASE_URL; ?>/pages/bureaux/delete.php?id=<?php echo $bureau['id']; ?>" class="btn btn-sm btn-danger" title="Supprimer"
                                                       onclick="return confirm('Voulez-vous vraiment supprimer ce bureau ?');">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">
                                            <i class="bi bi-inbox"></i> Aucun bureau trouvé
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
    initDataTable('#bureauxTable');
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
