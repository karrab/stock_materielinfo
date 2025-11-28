<?php
$page_title = 'Employés';
require_once __DIR__ . '/../../includes/header.php';

$db = Database::getInstance();

// Filtres
$search = $_GET['search'] ?? '';
$service_id = $_GET['service_id'] ?? '';

$sql = "SELECT e.*, s.nom as service_nom
        FROM employes e
        INNER JOIN services s ON e.service_id = s.id
        WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (e.matricule LIKE :search OR e.nom LIKE :search OR e.prenom LIKE :search OR e.mail LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($service_id)) {
    $sql .= " AND e.service_id = :service_id";
    $params[':service_id'] = $service_id;
}

$sql .= " ORDER BY e.nom ASC, e.prenom ASC";

$stmt = $db->getConnection()->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$employes = $stmt->fetchAll();

// Liste des services pour le filtre
$db->prepare("SELECT * FROM services ORDER BY nom");
$services = $db->fetchAll();
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-people"></i> Employés</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                            <li class="breadcrumb-item active">Employés</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="<?php echo BASE_URL; ?>/pages/employes/create.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Nouvel employé
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-list-ul"></i> Liste des employés
                </div>
                <div class="card-body">
                    <!-- Filtres -->
                    <form method="GET" class="mb-3">
                        <div class="row">
                            <div class="col-md-4">
                                <input type="text" class="form-control" name="search" placeholder="Rechercher (matricule, nom, prénom, email)..." value="<?php echo htmlspecialchars($search); ?>">
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
                            <div class="col-md-5">
                                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Rechercher</button>
                                <?php if (!empty($search) || !empty($service_id)): ?>
                                    <a href="<?php echo BASE_URL; ?>/pages/employes/index.php" class="btn btn-secondary"><i class="bi bi-x"></i> Réinitialiser</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>

                    <!-- Tableau -->
                    <div class="table-responsive">
                        <table class="table table-hover" id="employesTable">
                            <thead>
                                <tr>
                                    <th>Matricule</th>
                                    <th>Nom</th>
                                    <th>Prénom</th>
                                    <th>Service</th>
                                    <th>Email</th>
                                    <th>Téléphone</th>
                                    <th>Statut</th>
                                    <th class="text-center no-sort no-export">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($employes) > 0): ?>
                                    <?php foreach ($employes as $employe): ?>
                                        <tr>
                                            <td><code><?php echo htmlspecialchars($employe['matricule']); ?></code></td>
                                            <td><strong><?php echo htmlspecialchars($employe['nom']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($employe['prenom']); ?></td>
                                            <td><span class="badge bg-info"><?php echo htmlspecialchars($employe['service_nom']); ?></span></td>
                                            <td><?php echo htmlspecialchars($employe['mail'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($employe['tel1'] ?? '-'); ?></td>
                                            <td>
                                                <?php if ($employe['actif']): ?>
                                                    <span class="badge bg-success">Actif</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactif</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center action-buttons no-print">
                                                <a href="<?php echo BASE_URL; ?>/pages/employes/view.php?id=<?php echo $employe['id']; ?>"
                                                   class="btn btn-sm btn-info" title="Voir">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="<?php echo BASE_URL; ?>/pages/employes/edit.php?id=<?php echo $employe['id']; ?>"
                                                   class="btn btn-sm btn-warning" title="Modifier">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="<?php echo BASE_URL; ?>/pages/employes/delete.php?id=<?php echo $employe['id']; ?>"
                                                   class="btn btn-sm btn-danger delete-confirm" title="Supprimer"
                                                   onclick="return confirm('Voulez-vous vraiment supprimer cet employé ?');">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">
                                            <i class="bi bi-inbox"></i> Aucun employé trouvé
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
    initDataTable('#employesTable');
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
