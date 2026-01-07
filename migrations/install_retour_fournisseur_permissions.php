<?php
/**
 * Script d'installation des permissions pour retour_fournisseur
 * Exécuter ce fichier via navigateur ou ligne de commande
 */

require_once __DIR__ . '/../config/config.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    echo "=== Installation des permissions retour_fournisseur ===\n\n";

    // Lire le fichier SQL
    $sql = file_get_contents(__DIR__ . '/010_add_retour_fournisseur_permissions.sql');

    // Retirer les commentaires
    $sql = preg_replace('/--.*$/m', '', $sql);

    // Séparer les requêtes
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt);
        }
    );

    $conn->beginTransaction();

    foreach ($statements as $statement) {
        if (!empty($statement)) {
            echo "Exécution: " . substr($statement, 0, 50) . "...\n";
            $conn->exec($statement);
        }
    }

    $conn->commit();

    echo "\n✓ Migration 010_add_retour_fournisseur_permissions.sql exécutée avec succès !\n";
    echo "✓ Les permissions retour_fournisseur ont été ajoutées.\n";
    echo "✓ Permissions attribuées aux rôles Admin et Gestionnaire.\n\n";

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    echo "✗ Erreur lors de l'installation: " . $e->getMessage() . "\n";
    exit(1);
}
