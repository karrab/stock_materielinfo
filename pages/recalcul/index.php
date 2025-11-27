<?php
$page_title = 'Recalcul du stock';
require_once __DIR__ . '/../../includes/header.php';

$auth->requireAdmin();
$db = Database::getInstance();

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_recalcul'])) {
    try {
        $db->beginTransaction();

        // Recalculer les stocks pour tous les articles
        $sql = "UPDATE articles a
                SET a.qte_entree = COALESCE((SELECT SUM(qte_entree) FROM ligne_entrees WHERE article_id = a.id), 0),
                    a.qte_sortie = COALESCE((SELECT SUM(qte_sortie) FROM ligne_sorties WHERE article_id = a.id), 0),
                    a.qte_retour = COALESCE((SELECT SUM(qte_retour) FROM ligne_retours WHERE article_id = a.id), 0),
                    a.qte_disponible = a.stock_initial +
                                       COALESCE((SELECT SUM(qte_entree) FROM ligne_entrees WHERE article_id = a.id), 0) -
                                       COALESCE((SELECT SUM(qte_sortie) FROM ligne_sorties WHERE article_id = a.id), 0) +
                                       COALESCE((SELECT SUM(qte_retour) FROM ligne_retours WHERE article_id = a.id), 0)";

        $db->prepare($sql);
        $db->execute();

        $rows_affected = $db->rowCount();

        $auth->logTrace($auth->getUserId(), 'recalcul', 'execute', 'articles', 0, "Recalcul stock global - $rows_affected articles mis à jour");

        $db->commit();
        $success_message = "Recalcul effectué avec succès ! $rows_affected article(s) mis à jour.";

    } catch (Exception $e) {
        $db->rollback();
        $error_message = 'Erreur lors du recalcul: ' . $e->getMessage();
    }
}

// Récupérer les articles avec écarts potentiels
$sql = "SELECT a.*,
               COALESCE((SELECT SUM(qte_entree) FROM ligne_entrees WHERE article_id = a.id), 0) as calc_entree,
               COALESCE((SELECT SUM(qte_sortie) FROM ligne_sorties WHERE article_id = a.id), 0) as calc_sortie,
               COALESCE((SELECT SUM(qte_retour) FROM ligne_retours WHERE article_id = a.id), 0) as calc_retour,
               (a.stock_initial +
                COALESCE((SELECT SUM(qte_entree) FROM ligne_entrees WHERE article_id = a.id), 0) -
                COALESCE((SELECT SUM(qte_sortie) FROM ligne_sorties WHERE article_id = a.id), 0) +
                COALESCE((SELECT SUM(qte_retour) FROM ligne_retours WHERE article_id = a.id), 0)) as calc_disponible
        FROM articles a
        HAVING ABS(a.qte_disponible - calc_disponible) > 0.01
        OR ABS(a.qte_entree - calc_entree) > 0.01
        OR ABS(a.qte_sortie - calc_sortie) > 0.01
        OR ABS(a.qte_retour - calc_retour) > 0.01";

$db->prepare($sql);
$articles_avec_ecarts = $db->fetchAll();
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-arrow-clockwise"></i> Recalcul du stock</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                    <li class="breadcrumb-item active">Recalcul du stock</li>
                </ol>
            </nav>
        </div>
    </div>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle"></i> <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle"></i> <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-3">
                <div class="card-header">
                    <i class="bi bi-info-circle"></i> Informations
                </div>
                <div class="card-body">
                    <h5>Qu'est-ce que le recalcul du stock ?</h5>
                    <p>Le recalcul du stock permet de recalculer automatiquement les quantités de stock pour tous les articles en se basant sur les mouvements enregistrés (entrées, sorties, retours).</p>

                    <h5 class="mt-4">Quand utiliser cette fonction ?</h5>
                    <ul>
                        <li>En cas de doute sur la cohérence des stocks</li>
                        <li>Après une correction manuelle en base de données</li>
                        <li>Suite à une migration ou import de données</li>
                        <li>Pour vérifier l'intégrité des données</li>
                    </ul>

                    <h5 class="mt-4">Formule appliquée :</h5>
                    <code class="d-block bg-light p-3">
                        Stock disponible = Stock initial + Total entrées - Total sorties + Total retours
                    </code>
                </div>
            </div>

            <?php if (count($articles_avec_ecarts) > 0): ?>
                <div class="card">
                    <div class="card-header bg-warning">
                        <i class="bi bi-exclamation-triangle"></i> Articles avec écarts détectés
                    </div>
                    <div class="card-body">
                        <p><strong><?php echo count($articles_avec_ecarts); ?> article(s)</strong> présentent des incohérences entre le stock enregistré et le stock calculé :</p>

                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Désignation</th>
                                        <th class="text-end">Stock actuel</th>
                                        <th class="text-end">Stock calculé</th>
                                        <th class="text-end">Écart</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($articles_avec_ecarts as $article): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($article['code_article']); ?></td>
                                            <td><?php echo htmlspecialchars($article['designation']); ?></td>
                                            <td class="text-end"><?php echo number_format($article['qte_disponible'], 2, ',', ' '); ?></td>
                                            <td class="text-end"><?php echo number_format($article['calc_disponible'], 2, ',', ' '); ?></td>
                                            <td class="text-end text-danger fw-bold">
                                                <?php echo number_format($article['qte_disponible'] - $article['calc_disponible'], 2, ',', ' '); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i> Aucun écart détecté. Les stocks sont cohérents.
                </div>
            <?php endif; ?>
        </div>

        <div class="col-md-4">
            <div class="card bg-danger text-white mb-3">
                <div class="card-header">
                    <i class="bi bi-exclamation-triangle"></i> <strong>ATTENTION</strong>
                </div>
                <div class="card-body">
                    <p><strong>Action irréversible !</strong></p>
                    <p class="mb-0">Cette opération va recalculer les stocks de TOUS les articles. Assurez-vous que les entrées, sorties et retours sont corrects avant de lancer le recalcul.</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <i class="bi bi-play-circle"></i> Lancer le recalcul
                </div>
                <div class="card-body">
                    <form method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir recalculer tous les stocks ? Cette action ne peut pas être annulée.');">
                        <div class="d-grid">
                            <button type="submit" name="confirm_recalcul" value="1" class="btn btn-danger btn-lg">
                                <i class="bi bi-arrow-clockwise"></i> Recalculer maintenant
                            </button>
                        </div>
                    </form>
                    <hr>
                    <a href="<?php echo BASE_URL; ?>/index.php" class="btn btn-secondary w-100">
                        <i class="bi bi-arrow-left"></i> Retour
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
