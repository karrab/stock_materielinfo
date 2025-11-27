<?php
$page_title = 'Rapport état du stock';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('rapports', 'view');
$db = Database::getInstance();

$filtre = $_GET['filtre'] ?? 'tous';

$sql = "SELECT * FROM articles WHERE 1=1";

if ($filtre == 'rupture') {
    $sql .= " AND qte_disponible <= 0";
} elseif ($filtre == 'faible') {
    $sql .= " AND qte_disponible > 0 AND qte_disponible <= stock_min";
} elseif ($filtre == 'normal') {
    $sql .= " AND qte_disponible > stock_min AND qte_disponible < stock_max";
} elseif ($filtre == 'eleve') {
    $sql .= " AND qte_disponible >= stock_max";
}

$sql .= " ORDER BY qte_disponible ASC";

$db->prepare($sql);
$articles = $db->fetchAll();
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <h2><i class="bi bi-bar-chart"></i> Rapport état du stock</h2>

    <form method="GET" class="card mb-3">
        <div class="card-body">
            <div class="btn-group" role="group">
                <a href="?filtre=tous" class="btn <?php echo $filtre == 'tous' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                    Tous (<?php $db->prepare("SELECT COUNT(*) as c FROM articles"); echo $db->fetch()['c']; ?>)
                </a>
                <a href="?filtre=rupture" class="btn <?php echo $filtre == 'rupture' ? 'btn-danger' : 'btn-outline-danger'; ?>">
                    Rupture
                </a>
                <a href="?filtre=faible" class="btn <?php echo $filtre == 'faible' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                    Stock faible
                </a>
                <a href="?filtre=normal" class="btn <?php echo $filtre == 'normal' ? 'btn-success' : 'btn-outline-success'; ?>">
                    Stock normal
                </a>
                <a href="?filtre=eleve" class="btn <?php echo $filtre == 'eleve' ? 'btn-info' : 'btn-outline-info'; ?>">
                    Stock élevé
                </a>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-body">
            <table class="table table-sm table-striped">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Désignation</th>
                        <th class="text-end">Stock initial</th>
                        <th class="text-end">Entrées</th>
                        <th class="text-end">Sorties</th>
                        <th class="text-end">Disponible</th>
                        <th class="text-center">État</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($articles as $a): ?>
                        <?php
                        $badge = 'secondary';
                        $etat = 'Normal';
                        if ($a['qte_disponible'] <= 0) { $badge = 'danger'; $etat = 'Rupture'; }
                        elseif ($a['qte_disponible'] <= $a['stock_min']) { $badge = 'warning'; $etat = 'Faible'; }
                        elseif ($a['qte_disponible'] >= $a['stock_max']) { $badge = 'info'; $etat = 'Élevé'; }
                        else { $badge = 'success'; $etat = 'OK'; }
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($a['code_article']); ?></td>
                            <td><?php echo htmlspecialchars($a['designation']); ?></td>
                            <td class="text-end"><?php echo number_format($a['stock_initial'], 2, ',', ' '); ?></td>
                            <td class="text-end"><?php echo number_format($a['qte_entree'], 2, ',', ' '); ?></td>
                            <td class="text-end"><?php echo number_format($a['qte_sortie'], 2, ',', ' '); ?></td>
                            <td class="text-end fw-bold"><?php echo number_format($a['qte_disponible'], 2, ',', ' '); ?></td>
                            <td class="text-center"><span class="badge bg-<?php echo $badge; ?>"><?php echo $etat; ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
