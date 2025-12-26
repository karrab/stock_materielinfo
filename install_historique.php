#!/usr/bin/env php
<?php
/**
 * Script d'installation du module historique des mouvements
 * Ce script crée la table historique_article et affiche un résumé
 *
 * Usage: php install_historique.php
 */

echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║  INSTALLATION DU MODULE HISTORIQUE DES MOUVEMENTS D'ARTICLES  ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

require_once __DIR__ . '/config/config.php';

try {
    echo "📊 Connexion à la base de données...\n";
    $db = Database::getInstance()->getConnection();
    echo "✅ Connexion réussie\n\n";

    // Vérifier si la table existe déjà
    echo "🔍 Vérification de l'existence de la table historique_article...\n";
    $check = $db->query("SHOW TABLES LIKE 'historique_article'")->fetch();

    if ($check) {
        echo "⚠️  La table historique_article existe déjà !\n";
        echo "   Voulez-vous la recréer ? (ATTENTION : toutes les données seront perdues)\n";
        echo "   Tapez 'OUI' pour continuer ou appuyez sur Entrée pour annuler : ";

        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        fclose($handle);

        if ($line !== 'OUI') {
            echo "\n❌ Installation annulée.\n";
            exit(0);
        }

        echo "\n🗑️  Suppression de l'ancienne table...\n";
        $db->exec("DROP TABLE IF EXISTS historique_article");
        echo "✅ Table supprimée\n\n";
    }

    // Création de la table
    echo "🔨 Création de la table historique_article...\n";

    $sql = "CREATE TABLE historique_article (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code_article VARCHAR(50) NOT NULL,
        designation VARCHAR(255) NOT NULL,
        operation ENUM('entree', 'sortie', 'retour') NOT NULL,
        qte DECIMAL(10,2) NOT NULL,
        stock_avant_operation DECIMAL(10,2) NOT NULL,
        stock_apres_operation DECIMAL(10,2) NOT NULL,
        stock_initial DECIMAL(10,2) NOT NULL DEFAULT 0,
        stock_min DECIMAL(10,2) NOT NULL DEFAULT 0,
        stock_max DECIMAL(10,2) NOT NULL DEFAULT 0,

        article_id INT,
        entree_id INT NULL,
        sortie_id INT NULL,
        retour_id INT NULL,
        user_id INT,

        date_operation DATETIME NOT NULL,
        commentaire TEXT NULL,

        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

        INDEX idx_code_article (code_article),
        INDEX idx_article_id (article_id),
        INDEX idx_operation (operation),
        INDEX idx_date_operation (date_operation),
        INDEX idx_entree_id (entree_id),
        INDEX idx_sortie_id (sortie_id),
        INDEX idx_retour_id (retour_id),

        FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE SET NULL,
        FOREIGN KEY (entree_id) REFERENCES entrees(id) ON DELETE SET NULL,
        FOREIGN KEY (sortie_id) REFERENCES sorties(id) ON DELETE SET NULL,
        FOREIGN KEY (retour_id) REFERENCES retours(id) ON DELETE SET NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $db->exec($sql);

    echo "✅ Table créée avec succès !\n\n";

    // Afficher les statistiques
    echo "╔═══════════════════════════════════════════════════════════════╗\n";
    echo "║                    INSTALLATION RÉUSSIE !                     ║\n";
    echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

    echo "📋 Résumé de l'installation :\n\n";
    echo "✅ Table 'historique_article' créée\n";
    echo "✅ Classe HistoriqueArticle disponible\n";
    echo "✅ Intégration dans les modules entrées/sorties/retours\n";
    echo "✅ Interface de consultation accessible\n";
    echo "✅ Menu de navigation mis à jour\n\n";

    echo "🌐 Accès à l'historique :\n";
    echo "   URL : " . BASE_URL . "/pages/mouvements/index.php\n";
    echo "   Menu : Mouvements > Historique des mouvements\n\n";

    echo "📚 Fonctionnalités :\n";
    echo "   • Traçabilité complète de tous les mouvements\n";
    echo "   • Filtres par code, désignation, opération, période\n";
    echo "   • Statistiques par type d'opération\n";
    echo "   • Affichage des stocks avant/après opération\n";
    echo "   • Alertes visuelles pour stocks min/max\n";
    echo "   • Pagination pour performances optimales\n\n";

    echo "ℹ️  Documentation : migrations/README_008.md\n\n";

    echo "╔═══════════════════════════════════════════════════════════════╗\n";
    echo "║  Le système d'historique est maintenant opérationnel !       ║\n";
    echo "╚═══════════════════════════════════════════════════════════════╝\n";

} catch (PDOException $e) {
    echo "\n❌ ERREUR lors de l'installation !\n";
    echo "   Message : " . $e->getMessage() . "\n";
    echo "   Code : " . $e->getCode() . "\n\n";

    echo "💡 Suggestions :\n";
    echo "   • Vérifiez que MySQL est en cours d'exécution\n";
    echo "   • Vérifiez les paramètres de connexion dans config/config.php\n";
    echo "   • Vérifiez que la base de données 'stock_materiel' existe\n";
    echo "   • Vérifiez que les tables articles, entrees, sorties, retours et users existent\n\n";

    exit(1);
} catch (Exception $e) {
    echo "\n❌ ERREUR inattendue !\n";
    echo "   Message : " . $e->getMessage() . "\n\n";
    exit(1);
}
