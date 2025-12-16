<?php
/**
 * Script de diagnostic pour le système qte_physique
 * Accédez à : /pages/inventaires/diagnostic_qte_physique.php
 */
require_once __DIR__ . '/../../config/config.php';
$auth = Auth::getInstance();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Diagnostic qte_physique</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .test { margin: 10px 0; padding: 10px; background: white; border-left: 4px solid #ccc; }
        .test.ok { border-color: #28a745; }
        .test.error { border-color: #dc3545; background: #fff5f5; }
        .test.warning { border-color: #ffc107; background: #fff9e6; }
        h1 { color: #333; }
        h2 { color: #666; margin-top: 30px; }
        pre { background: #f8f9fa; padding: 10px; overflow: auto; }
    </style>
</head>
<body>
    <h1>🔍 Diagnostic système qte_physique</h1>
    <p>Ce script vérifie tous les composants nécessaires au fonctionnement de la saisie des quantités physiques</p>

    <h2>1. Configuration PHP</h2>
    <?php
    $tests = [];

    // Test 1: Version PHP
    $phpVersion = phpversion();
    $tests[] = [
        'name' => 'Version PHP',
        'result' => version_compare($phpVersion, '7.4', '>='),
        'message' => "PHP $phpVersion " . (version_compare($phpVersion, '7.4', '>=') ? '✓' : '✗ (requis >= 7.4)')
    ];

    // Test 2: PDO disponible
    $tests[] = [
        'name' => 'Extension PDO',
        'result' => extension_loaded('pdo'),
        'message' => extension_loaded('pdo') ? '✓ PDO disponible' : '✗ PDO non disponible'
    ];

    // Test 3: PDO MySQL disponible
    $tests[] = [
        'name' => 'Extension PDO MySQL',
        'result' => extension_loaded('pdo_mysql'),
        'message' => extension_loaded('pdo_mysql') ? '✓ PDO MySQL disponible' : '✗ PDO MySQL non disponible'
    ];

    foreach ($tests as $test) {
        echo '<div class="test ' . ($test['result'] ? 'ok' : 'error') . '">';
        echo '<strong>' . $test['name'] . ':</strong> ' . $test['message'];
        echo '</div>';
    }
    ?>

    <h2>2. Permissions et fichiers</h2>
    <?php
    // Test 4: Fichier update_qte_physique.php existe
    $updateFile = __DIR__ . '/update_qte_physique.php';
    $fileExists = file_exists($updateFile);
    echo '<div class="test ' . ($fileExists ? 'ok' : 'error') . '">';
    echo '<strong>Fichier update_qte_physique.php:</strong> ' . ($fileExists ? '✓ Existe' : '✗ Introuvable');
    if ($fileExists) {
        echo '<br><code>' . $updateFile . '</code>';
        echo '<br>Taille: ' . filesize($updateFile) . ' octets';
        echo '<br>Lisible: ' . (is_readable($updateFile) ? '✓' : '✗');
    }
    echo '</div>';

    // Test 5: Répertoire logs
    $logsDir = __DIR__ . '/../../logs';
    $logsDirExists = is_dir($logsDir);
    $logsDirWritable = is_writable($logsDir);
    echo '<div class="test ' . ($logsDirExists && $logsDirWritable ? 'ok' : 'warning') . '">';
    echo '<strong>Répertoire logs:</strong> ';
    if ($logsDirExists) {
        echo '✓ Existe ';
        echo ($logsDirWritable ? '(✓ écriture possible)' : '(✗ écriture impossible - vérifier chmod)');
    } else {
        echo '✗ N\'existe pas';
    }
    echo '</div>';

    // Test 6: Fichier de log
    $logFile = $logsDir . '/qte_physique_debug.log';
    if (file_exists($logFile)) {
        $logSize = filesize($logFile);
        echo '<div class="test ok">';
        echo '<strong>Fichier de log:</strong> ✓ Existe ('. $logSize . ' octets)';
        echo '<br><strong>Dernières lignes:</strong>';
        $logContent = file_get_contents($logFile);
        $logLines = explode("\n", $logContent);
        $lastLines = array_slice($logLines, -10);
        echo '<pre>' . htmlspecialchars(implode("\n", $lastLines)) . '</pre>';
        echo '</div>';
    } else {
        echo '<div class="test warning">';
        echo '<strong>Fichier de log:</strong> ⚠ Aucun log trouvé (normal si jamais utilisé)';
        echo '</div>';
    }
    ?>

    <h2>3. Session et authentification</h2>
    <?php
    // Test 7: Session démarrée
    $sessionActive = session_status() === PHP_SESSION_ACTIVE;
    echo '<div class="test ' . ($sessionActive ? 'ok' : 'error') . '">';
    echo '<strong>Session PHP:</strong> ' . ($sessionActive ? '✓ Active' : '✗ Inactive');
    if ($sessionActive) {
        echo '<br>Session ID: ' . session_id();
    }
    echo '</div>';

    // Test 8: Utilisateur connecté
    $isLoggedIn = $auth->isLoggedIn();
    echo '<div class="test ' . ($isLoggedIn ? 'ok' : 'error') . '">';
    echo '<strong>Authentification:</strong> ' . ($isLoggedIn ? '✓ Connecté' : '✗ Non connecté');
    if ($isLoggedIn) {
        $user = $auth->getUser();
        echo '<br>Utilisateur: ' . htmlspecialchars($user['nom']) . ' (ID: ' . $user['id'] . ')';
        echo '<br>Rôle: ' . htmlspecialchars($user['role']);
    }
    echo '</div>';

    // Test 9: Permission inventaires update
    if ($isLoggedIn) {
        $hasPermission = $auth->hasPermission('inventaires', 'update');
        echo '<div class="test ' . ($hasPermission ? 'ok' : 'error') . '">';
        echo '<strong>Permission inventaires.update:</strong> ' . ($hasPermission ? '✓ Accordée' : '✗ Refusée');
        echo '</div>';
    }
    ?>

    <h2>4. Base de données</h2>
    <?php
    // Test 10: Connexion DB
    try {
        $db = Database::getInstance();
        echo '<div class="test ok">';
        echo '<strong>Connexion DB:</strong> ✓ Établie';
        echo '</div>';

        // Test 11: Table ligne_inventaires
        $stmt = $db->getConnection()->query("SHOW TABLES LIKE 'ligne_inventaires'");
        $tableExists = $stmt->rowCount() > 0;
        echo '<div class="test ' . ($tableExists ? 'ok' : 'error') . '">';
        echo '<strong>Table ligne_inventaires:</strong> ' . ($tableExists ? '✓ Existe' : '✗ N\'existe pas');
        echo '</div>';

        if ($tableExists) {
            // Test 12: Colonnes qte_physique et ecart
            $stmt = $db->getConnection()->query("DESCRIBE ligne_inventaires");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $hasQtePhysique = in_array('qte_physique', $columns);
            $hasEcart = in_array('ecart', $columns);

            echo '<div class="test ' . ($hasQtePhysique ? 'ok' : 'error') . '">';
            echo '<strong>Colonne qte_physique:</strong> ' . ($hasQtePhysique ? '✓ Existe' : '✗ N\'existe pas');
            echo '</div>';

            echo '<div class="test ' . ($hasEcart ? 'ok' : 'error') . '">';
            echo '<strong>Colonne ecart:</strong> ' . ($hasEcart ? '✓ Existe' : '✗ N\'existe pas');
            echo '</div>';

            // Test 13: Compter les inventaires en cours
            $stmt = $db->getConnection()->query("SELECT COUNT(*) FROM inventaires WHERE etat = 'en_cours'");
            $countEnCours = $stmt->fetchColumn();
            echo '<div class="test ' . ($countEnCours > 0 ? 'ok' : 'warning') . '">';
            echo '<strong>Inventaires en cours:</strong> ' . $countEnCours;
            if ($countEnCours === 0) {
                echo ' ⚠ Aucun inventaire en cours (créez-en un pour tester)';
            }
            echo '</div>';

            // Test 14: Exemple de ligne d'inventaire
            $stmt = $db->getConnection()->query("
                SELECT li.id, li.qte_physique, li.ecart, i.reference, i.etat
                FROM ligne_inventaires li
                INNER JOIN inventaires i ON li.inventaire_id = i.id
                LIMIT 1
            ");
            $exemple = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($exemple) {
                echo '<div class="test ok">';
                echo '<strong>Exemple ligne inventaire:</strong>';
                echo '<pre>' . print_r($exemple, true) . '</pre>';
                echo '</div>';
            }
        }

    } catch (Exception $e) {
        echo '<div class="test error">';
        echo '<strong>Connexion DB:</strong> ✗ Erreur';
        echo '<br>' . htmlspecialchars($e->getMessage());
        echo '</div>';
    }
    ?>

    <h2>5. Test AJAX</h2>
    <div class="test">
        <strong>Test manuel:</strong>
        <p>Pour tester l'AJAX manuellement, ouvrez la console du navigateur sur une page d'inventaire en cours et exécutez :</p>
        <pre>$.ajax({
    url: BASE_URL + '/pages/inventaires/update_qte_physique.php',
    method: 'POST',
    data: { ligne_id: 1, qte_physique: 100 },
    success: function(response) { console.log('Success:', response); },
    error: function(xhr) { console.log('Error:', xhr.responseText); }
});</pre>
    </div>

    <h2>Résumé</h2>
    <div class="test">
        <p><strong>Si tout est ✓ (vert) :</strong> Le système devrait fonctionner. Le problème pourrait être :</p>
        <ul>
            <li>Un problème de mise en cache du navigateur (Ctrl+F5 pour recharger)</li>
            <li>Un conflit JavaScript sur la page</li>
            <li>Le serveur WAMP utilise une ancienne version des fichiers</li>
        </ul>

        <p><strong>Si vous voyez des ✗ (rouge) :</strong></p>
        <ul>
            <li>Vérifiez les permissions PHP et base de données</li>
            <li>Assurez-vous que l'utilisateur a les permissions nécessaires</li>
            <li>Vérifiez que les tables existent avec les bonnes colonnes</li>
        </ul>

        <p><strong>Pour déboguer davantage :</strong></p>
        <ul>
            <li>Ouvrez la console du navigateur (F12)</li>
            <li>Onglet "Network" pour voir les requêtes AJAX</li>
            <li>Vérifiez le fichier <code><?php echo htmlspecialchars($logFile); ?></code></li>
        </ul>
    </div>

    <p style="text-align: center; margin-top: 40px;">
        <a href="<?php echo BASE_URL; ?>/pages/inventaires/index.php" style="padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;">
            Retour aux inventaires
        </a>
    </p>
</body>
</html>
