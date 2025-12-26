<?php
/**
 * Script pour exécuter la migration 008 - Création table historique_article
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';

try {
    $db = Database::getInstance()->getConnection();

    // Lire le fichier SQL
    $sql = file_get_contents(__DIR__ . '/008_create_historique_article.sql');

    // Exécuter la migration
    $db->exec($sql);

    echo "✅ Migration 008 exécutée avec succès !\n";
    echo "📊 Table historique_article créée.\n";

} catch (PDOException $e) {
    echo "❌ Erreur lors de la migration: " . $e->getMessage() . "\n";
    exit(1);
}
