<?php
$page_title = 'Recherche globale';
require_once __DIR__ . '/../includes/header.php';

$auth->requireLogin();
$db = Database::getInstance();
$q = $_GET['q'] ?? '';

$results = ['articles' => [], 'employes' => [], 'services' => [], 'fournisseurs' => []];

if (!empty($q) && strlen($q) >= 2) {
    $search = '%' . $q . '%';

    // Recherche articles
    $db->prepare("SELECT id, code_article, designation FROM articles WHERE (code_article LIKE :search OR designation LIKE :search) AND actif = 1 LIMIT 10");
    $db->bind(':search', $search);
    $results['articles'] = $db->fetchAll();

    // Recherche employés
    $db->prepare("SELECT id, matricule, nom, prenom FROM employes WHERE (matricule LIKE :search OR nom LIKE :search OR prenom LIKE :search) AND actif = 1 LIMIT 10");
    $db->bind(':search', $search);
    $results['employes'] = $db->fetchAll();

    // Recherche services
    $db->prepare("SELECT id, nom FROM services WHERE nom LIKE :search LIMIT 10");
    $db->bind(':search', $search);
    $results['services'] = $db->fetchAll();

    // Recherche fournisseurs
    $db->prepare("SELECT id, nom_complet FROM fournisseurs WHERE nom_complet LIKE :search AND actif = 1 LIMIT 10");
    $db->bind(':search', $search);
    $results['fournisseurs'] = $db->fetchAll();
}
?>

<?php require_once __DIR__ . '/../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-search"></i> Recherche globale</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                    <li class="breadcrumb-item active">Recherche</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET">
                        <div class="input-group input-group-lg">
                            <input type="text" class="form-control" name="q" placeholder="Rechercher..." value="<?php echo htmlspecialchars($q); ?>" autofocus>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Rechercher</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($q)): ?>
        <?php
        $total_results = count($results['articles']) + count($results['employes']) + count($results['services']) + count($results['fournisseurs']);
        ?>

        <div class="row mt-4">
            <div class="col-12">
                <h4><?php echo $total_results; ?> résultat(s) pour "<?php echo htmlspecialchars($q); ?>"</h4>
            </div>
        </div>

        <?php if (count($results['articles']) > 0): ?>
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header"><i class="bi bi-box-seam"></i> Articles (<?php echo count($results['articles']); ?>)</div>
                        <div class="card-body">
                            <ul class="list-group">
                                <?php foreach ($results['articles'] as $item): ?>
                                    <li class="list-group-item">
                                        <a href="<?php echo BASE_URL; ?>/pages/articles/view.php?id=<?php echo $item['id']; ?>">
                                            <strong><?php echo htmlspecialchars($item['code_article']); ?></strong> - <?php echo htmlspecialchars($item['designation']); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (count($results['employes']) > 0): ?>
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header"><i class="bi bi-people"></i> Employés (<?php echo count($results['employes']); ?>)</div>
                        <div class="card-body">
                            <ul class="list-group">
                                <?php foreach ($results['employes'] as $item): ?>
                                    <li class="list-group-item">
                                        <a href="<?php echo BASE_URL; ?>/pages/employes/view.php?id=<?php echo $item['id']; ?>">
                                            <strong><?php echo htmlspecialchars($item['matricule']); ?></strong> - <?php echo htmlspecialchars($item['nom'] . ' ' . $item['prenom']); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($total_results === 0): ?>
            <div class="row mt-3">
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Aucun résultat trouvé pour "<?php echo htmlspecialchars($q); ?>"
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
