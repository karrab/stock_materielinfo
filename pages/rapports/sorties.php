<?php
$page_title = 'Rapport des sorties';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('rapports', 'view');
$db = Database::getInstance();

$date_debut = $_GET['date_debut'] ?? date('Y-m-01');
$date_fin = $_GET['date_fin'] ?? date('Y-m-d');
$service_id = $_GET['service_id'] ?? '';

$sql = "SELECT s.*, serv.nom as service_nom, emp.nom as employe_nom, emp.prenom,
               (SELECT COUNT(*) FROM ligne_sorties WHERE sortie_id = s.id) as nb_articles,
               (SELECT SUM(qte_sortie) FROM ligne_sorties WHERE sortie_id = s.id) as qte_totale
        FROM sorties s
        INNER JOIN services serv ON s.service_id = serv.id
        INNER JOIN employes emp ON s.employe_id = emp.id
        WHERE s.date BETWEEN :date_debut AND :date_fin";

$params = [':date_debut' => $date_debut, ':date_fin' => $date_fin];

if (!empty($service_id)) {
    $sql .= " AND s.service_id = :service_id";
    $params[':service_id'] = $service_id;
}

$sql .= " ORDER BY s.date DESC";

$stmt = $db->getConnection()->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$sorties = $stmt->fetchAll();

$db->prepare("SELECT id, nom FROM services ORDER BY nom");
$services = $db->fetchAll();
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <h2><i class="bi bi-bar-chart"></i> Rapport des sorties</h2>

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
                    <label class="form-label">Service</label>
                    <select class="form-select" name="service_id">
                        <option value="">Tous</option>
                        <?php foreach ($services as $s): ?>
                            <option value="<?php echo $s['id']; ?>" <?php echo $service_id == $s['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($s['nom']); ?>
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
                        <th>Service</th>
                        <th>Employé</th>
                        <th class="text-center">Articles</th>
                        <th class="text-end">Quantité totale</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $total_qte = 0;
                    foreach ($sorties as $sortie):
                        $total_qte += $sortie['qte_totale'];
                    ?>
                        <tr>
                            <td><?php echo $sortie['id']; ?></td>
                            <td><?php echo date('d/m/Y', strtotime($sortie['date'])); ?></td>
                            <td><?php echo htmlspecialchars($sortie['service_nom']); ?></td>
                            <td><?php echo htmlspecialchars($sortie['employe_nom'] . ' ' . $sortie['prenom']); ?></td>
                            <td class="text-center"><?php echo $sortie['nb_articles']; ?></td>
                            <td class="text-end"><?php echo number_format($sortie['qte_totale'], 2, ',', ' '); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="fw-bold">
                        <td colspan="5" class="text-end">TOTAL:</td>
                        <td class="text-end"><?php echo number_format($total_qte, 2, ',', ' '); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
