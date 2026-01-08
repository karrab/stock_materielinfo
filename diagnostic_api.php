<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Diagnostic API</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .success { color: green; }
        .error { color: red; }
        pre { background: white; padding: 10px; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <h1>Diagnostic API last_entree_articles.php</h1>

    <h2>1. Vérification fichier</h2>
    <?php
    $file = __DIR__ . '/api/last_entree_articles.php';
    if (file_exists($file)) {
        echo '<p class="success">✓ Fichier existe : ' . $file . '</p>';
        echo '<p>Permissions : ' . substr(sprintf('%o', fileperms($file)), -4) . '</p>';
        echo '<p>Taille : ' . filesize($file) . ' octets</p>';
        echo '<p>Modifié : ' . date('Y-m-d H:i:s', filemtime($file)) . '</p>';
    } else {
        echo '<p class="error">✗ Fichier introuvable : ' . $file . '</p>';
    }
    ?>

    <h2>2. Test direct PHP</h2>
    <?php
    $_GET['fournisseur_id'] = 1;
    ob_start();
    include $file;
    $result = ob_get_clean();
    echo '<pre>' . htmlspecialchars($result) . '</pre>';
    ?>

    <h2>3. Test AJAX depuis JavaScript</h2>
    <button onclick="testAjax()">Tester l'appel AJAX</button>
    <div id="ajaxResult"></div>

    <script>
        const BASE_URL = '<?php echo 'http://' . $_SERVER['HTTP_HOST'] . '/stock_materielinfo'; ?>';

        function testAjax() {
            const url = BASE_URL + '/api/last_entree_articles.php?fournisseur_id=1';
            document.getElementById('ajaxResult').innerHTML = '<p>Test en cours vers : ' + url + '</p>';

            fetch(url)
                .then(response => {
                    document.getElementById('ajaxResult').innerHTML += '<p class="success">Status : ' + response.status + '</p>';
                    return response.json();
                })
                .then(data => {
                    document.getElementById('ajaxResult').innerHTML += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                })
                .catch(error => {
                    document.getElementById('ajaxResult').innerHTML += '<p class="error">Erreur : ' + error + '</p>';
                });
        }
    </script>

    <h2>4. Informations serveur</h2>
    <pre><?php
    echo 'Document Root : ' . $_SERVER['DOCUMENT_ROOT'] . "\n";
    echo 'Script Filename : ' . $_SERVER['SCRIPT_FILENAME'] . "\n";
    echo 'Current Dir : ' . __DIR__ . "\n";
    echo 'Server Software : ' . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "\n";
    echo 'PHP Version : ' . PHP_VERSION . "\n";
    ?></pre>

    <hr>
    <p><a href="/stock_materielinfo/pages/retour_fournisseur/create.php">← Retour à create.php</a></p>
</body>
</html>
