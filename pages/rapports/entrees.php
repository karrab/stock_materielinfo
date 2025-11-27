<?php
$page_title = 'Rapport des entrées';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('rapports', 'view');
$db = Database::getInstance();

$date_debut = $_GET['date_debut'] ?? date('Y-m-01');
$date_fin = $_GET['date_fin'] ?? date('Y-m-d');
$fournisseur_id = $_GET['fournisseur_id'] ?? '';

$sql = "SELECT e.*, f.nom_complet as fournisseur,
               (SELECT COUNT(*) FROM ligne_entrees WHERE entree_id = e.id) as nb_articles,
               (SELECT SUM(qte_entree) FROM ligne_entrees WHERE entree_id = e.id) as qte_totale
        FROM entrees e
        INNER JOIN fournisseurs f ON e.fournisseur_id = f.id
        WHERE e.date BETWEEN :date_debut AND :date_fin";

$params = [':date_debut' => $date_debut, ':date_fin' => $date_fin];

if (!empty($fournisseur_id)) {
    $sql .= " AND e.fournisseur_id = :fournisseur_id";
    $params[':fournisseur_id'] = $fournisseur_id;
}

$sql .= " ORDER BY e.date DESC";

$stmt = $db->getConnection()->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$entrees = $stmt->fetchAll();

$db->prepare("SELECT id, nom_complet FROM fournisseurs ORDER BY nom_complet");
$fournisseurs = $db->fetchAll();
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <h2><i class="bi bi-bar-chart"></i> Rapport des entrées</h2>

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
                <div class="col-md-4">
                    <label class="form-label">Fournisseur</label>
                    <select class="form-select" name="fournisseur_id">
                        <option value="">Tous</option>
                        <?php foreach ($fournisseurs as $f): ?>
                            <option value="<?php echo $f['id']; ?>" <?php echo $fournisseur_id == $f['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($f['nom_complet']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                </div>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Date</th>
                        <th>Fournisseur</th>
                        <th class="text-center">Articles</th>
                        <th class="text-end">Quantité totale</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $total_qte = 0;
                    foreach ($entrees as $e):
                        $total_qte += $e['qte_totale'];
                    ?>
                        <tr>
                            <td><?php echo $e['id']; ?></td>
                            <td><?php echo date('d/m/Y', strtotime($e['date'])); ?></td>
                            <td><?php echo htmlspecialchars($e['fournisseur']); ?></td>
                            <td class="text-center"><?php echo $e['nb_articles']; ?></td>
                            <td class="text-end"><?php echo number_format($e['qte_totale'], 2, ',', ' '); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="fw-bold">
                        <td colspan="4" class="text-end">TOTAL:</td>
                        <td class="text-end"><?php echo number_format($total_qte, 2, ',', ' '); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
