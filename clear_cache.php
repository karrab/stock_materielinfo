<?php
/**
 * Script de vidage du cache PHP OPcache et APCu
 * À exécuter pour forcer le rechargement des fichiers PHP modifiés
 */

$results = [];

// Vider OPcache
if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        $results[] = '✅ OPcache vidé avec succès';
    } else {
        $results[] = '❌ Échec du vidage OPcache';
    }
} else {
    $results[] = '⚠️ OPcache non disponible';
}

// Vider APCu
if (function_exists('apcu_clear_cache')) {
    if (apcu_clear_cache()) {
        $results[] = '✅ APCu vidé avec succès';
    } else {
        $results[] = '❌ Échec du vidage APCu';
    }
} else {
    $results[] = '⚠️ APCu non disponible';
}

// Informations système
$results[] = '';
$results[] = '📋 Informations système:';
$results[] = 'PHP Version: ' . PHP_VERSION;
$results[] = 'Server API: ' . php_sapi_name();
$results[] = 'OPcache enabled: ' . (function_exists('opcache_get_status') && opcache_get_status() ? 'Oui' : 'Non');

// Tester le fichier update_qte_physique.php
$file_path = __DIR__ . '/pages/inventaires/update_qte_physique.php';
$results[] = '';
$results[] = '🔍 Vérification update_qte_physique.php:';
$results[] = 'Chemin: ' . $file_path;
$results[] = 'Existe: ' . (file_exists($file_path) ? 'Oui' : 'Non');
if (file_exists($file_path)) {
    $results[] = 'Taille: ' . filesize($file_path) . ' octets';
    $results[] = 'Dernière modification: ' . date('Y-m-d H:i:s', filemtime($file_path));

    // Lire les 30 premières lignes pour vérifier le contenu
    $lines = file($file_path);
    $results[] = '';
    $results[] = '📄 Ligne 25 du fichier:';
    if (isset($lines[24])) {
        $results[] = htmlspecialchars(trim($lines[24]));
        if (strpos($lines[24], 'Auth::getInstance()') !== false) {
            $results[] = '❌ ERREUR: Le fichier contient encore Auth::getInstance()';
            $results[] = '⚠️ Le fichier n\'a pas été mis à jour ou le cache empêche la lecture';
        } elseif (strpos($lines[24], 'new Auth()') !== false) {
            $results[] = '✅ OK: Le fichier utilise new Auth()';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vidage Cache PHP</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px;
            margin: 0;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
        }
        h1 {
            color: #333;
            margin-top: 0;
            font-size: 28px;
        }
        .result {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px 20px;
            margin: 10px 0;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
        }
        .success { border-left-color: #28a745; background: #d4edda; }
        .error { border-left-color: #dc3545; background: #f8d7da; }
        .warning { border-left-color: #ffc107; background: #fff3cd; }
        .info { border-left-color: #17a2b8; background: #d1ecf1; }
        .actions {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #eee;
            display: flex;
            gap: 15px;
        }
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Vidage du Cache PHP</h1>

        <?php foreach ($results as $result): ?>
            <?php
            $class = 'result';
            if (strpos($result, '✅') !== false) $class .= ' success';
            elseif (strpos($result, '❌') !== false) $class .= ' error';
            elseif (strpos($result, '⚠️') !== false) $class .= ' warning';
            elseif (strpos($result, '📋') !== false || strpos($result, '🔍') !== false || strpos($result, '📄') !== false) $class .= ' info';
            ?>
            <div class="<?php echo $class; ?>"><?php echo $result; ?></div>
        <?php endforeach; ?>

        <div class="actions">
            <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-primary">
                🔄 Vider à nouveau
            </a>
            <a href="pages/test/direct_qte_test.php" class="btn btn-secondary">
                🧪 Retour au test
            </a>
        </div>
    </div>
</body>
</html>
