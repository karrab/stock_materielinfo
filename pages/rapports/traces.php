<?php
$page_title = 'Traces d\'activité';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('rapports', 'view');
$db = Database::getInstance();

$user_id = $_GET['user_id'] ?? '';
$module = $_GET['module'] ?? '';
$date_debut = $_GET['date_debut'] ?? date('Y-m-d', strtotime('-7 days'));
$date_fin = $_GET['date_fin'] ?? date('Y-m-d');

$sql = "SELECT t.*, u.nom as user_nom
        FROM traces t
        INNER JOIN users u ON t.user_id = u.id
        WHERE t.created_at BETWEEN :date_debut AND :date_fin";

$params = [':date_debut' => $date_debut . ' 00:00:00', ':date_fin' => $date_fin . ' 23:59:59'];

if (!empty($user_id)) {
    $sql .= " AND t.user_id = :user_id";
    $params[':user_id'] = $user_id;
}

if (!empty($module)) {
    $sql .= " AND t.module = :module";
    $params[':module'] = $module;
}

$sql .= " ORDER BY t.created_at DESC LIMIT 500";

$stmt = $db->getConnection()->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$traces = $stmt->fetchAll();

$db->prepare("SELECT id, nom FROM users ORDER BY nom");
$users = $db->fetchAll();
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <h2><i class="bi bi-clock-history"></i> Traces d'activité</h2>

    <form method="GET" class="card mb-3">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label">Date début</label>
                    <input type="date" class="form-control" name="date_debut" value="<?php echo $date_debut; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date fin</label>
                    <input type="date" class="form-control" name="date_fin" value="<?php echo $date_fin; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Utilisateur</label>
                    <select class="form-select" name="user_id">
                        <option value="">Tous</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?php echo $u['id']; ?>" <?php echo $user_id == $u['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($u['nom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Module</label>
                    <input type="text" class="form-control" name="module" value="<?php echo htmlspecialchars($module); ?>" placeholder="articles, sorties...">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                </div>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th>Date/Heure</th>
                            <th>Utilisateur</th>
                            <th>Module</th>
                            <th>Action</th>
                            <th>Détails</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($traces as $t): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i:s', strtotime($t['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($t['user_nom']); ?></td>
                                <td><span class="badge bg-primary"><?php echo htmlspecialchars($t['module']); ?></span></td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($t['action']); ?></span></td>
                                <td><?php echo htmlspecialchars($t['details']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="text-muted small mt-2">Affichage limité aux 500 dernières traces</p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
