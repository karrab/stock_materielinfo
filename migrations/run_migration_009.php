#!/usr/bin/env php
<?php
/**
 * Script pour exécuter la migration 009 - Création module retour fournisseur
 */

echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║        MIGRATION 009: MODULE RETOUR FOURNISSEUR              ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

require_once __DIR__ . '/../config/config.php';

try {
    echo "📊 Connexion à la base de données...\n";
    $db = Database::getInstance()->getConnection();
    echo "✅ Connexion réussie\n\n";

    // Lire le fichier SQL
    echo "📄 Lecture du fichier de migration...\n";
    $sql = file_get_contents(__DIR__ . '/009_create_retour_fournisseur.sql');

    if (!$sql) {
        throw new Exception("Impossible de lire le fichier de migration");
    }

    // Exécuter les requêtes une par une
    echo "🔨 Exécution de la migration...\n\n";

    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $index => $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }

        echo "   ► Exécution statement " . ($index + 1) . "...\n";
        $db->exec($statement);
    }

    echo "\n✅ Migration 009 exécutée avec succès !\n\n";

    echo "📋 Tables créées:\n";
    echo "   • retour_fournisseur\n";
    echo "   • ligne_retour_fournisseur\n\n";

    echo "📋 Modifications:\n";
    echo "   • historique_article : ajout operation 'retour_fournisseur'\n";
    echo "   • historique_article : ajout colonne retour_fournisseur_id\n\n";

    echo "🎯 Prochaines étapes:\n";
    echo "   1. Créer le dossier pages/retour_fournisseur/\n";
    echo "   2. Créer les fichiers CRUD\n";
    echo "   3. Ajouter au menu de navigation\n\n";

} catch (PDOException $e) {
    echo "\n❌ Erreur lors de la migration: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n\n";
    exit(1);
} catch (Exception $e) {
    echo "\n❌ Erreur: " . $e->getMessage() . "\n\n";
    exit(1);
}
