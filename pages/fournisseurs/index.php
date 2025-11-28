<?php
$page_title = 'Fournisseurs';
require_once __DIR__ . '/../../includes/header.php';

$db = Database::getInstance();
$search = $_GET['search'] ?? '';

$sql = "SELECT * FROM fournisseurs WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (nom_complet LIKE :search OR ville LIKE :search OR pays LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

$sql .= " ORDER BY nom_complet ASC";

$stmt = $db->getConnection()->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$fournisseurs = $stmt->fetchAll();
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-truck"></i> Fournisseurs</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                            <li class="breadcrumb-item active">Fournisseurs</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="<?php echo BASE_URL; ?>/pages/fournisseurs/create.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Nouveau fournisseur
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header"><i class="bi bi-list-ul"></i> Liste des fournisseurs</div>
                <div class="card-body">
                    <form method="GET" class="mb-3">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search" placeholder="Rechercher..." value="<?php echo htmlspecialchars($search); ?>">
                                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Rechercher</button>
                                    <?php if (!empty($search)): ?>
                                        <a href="<?php echo BASE_URL; ?>/pages/fournisseurs/index.php" class="btn btn-secondary"><i class="bi bi-x"></i></a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover" id="fournisseursTable">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Ville</th>
                                    <th>Pays</th>
                                    <th>Téléphone</th>
                                    <th>Statut</th>
                                    <th class="text-center no-sort no-export">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($fournisseurs) > 0): ?>
                                    <?php foreach ($fournisseurs as $fournisseur): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($fournisseur['nom_complet']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($fournisseur['ville'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($fournisseur['pays'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($fournisseur['tel1'] ?? '-'); ?></td>
                                            <td>
                                                <?php if ($fournisseur['actif']): ?>
                                                    <span class="badge bg-success">Actif</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactif</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center action-buttons no-print">
                                                <a href="<?php echo BASE_URL; ?>/pages/fournisseurs/view.php?id=<?php echo $fournisseur['id']; ?>"
                                                   class="btn btn-sm btn-info" title="Voir"><i class="bi bi-eye"></i></a>
                                                <a href="<?php echo BASE_URL; ?>/pages/fournisseurs/edit.php?id=<?php echo $fournisseur['id']; ?>"
                                                   class="btn btn-sm btn-warning" title="Modifier"><i class="bi bi-pencil"></i></a>
                                                <a href="<?php echo BASE_URL; ?>/pages/fournisseurs/delete.php?id=<?php echo $fournisseur['id']; ?>"
                                                   class="btn btn-sm btn-danger delete-confirm" title="Supprimer"
                                                   onclick="return confirm('Voulez-vous vraiment supprimer ce fournisseur ?');"><i class="bi bi-trash"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">
                                            <i class="bi bi-inbox"></i> Aucun fournisseur trouvé
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
    initDataTable('#fournisseursTable');
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
