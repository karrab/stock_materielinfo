-- Migration: Création de la table historique_article pour tracer tous les mouvements de stock
-- Date: 2025-12-26

CREATE TABLE IF NOT EXISTS historique_article (
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

    -- Références pour traçabilité complète
    article_id INT,
    entree_id INT NULL,
    sortie_id INT NULL,
    retour_id INT NULL,
    user_id INT,

    -- Informations additionnelles
    date_operation DATETIME NOT NULL,
    commentaire TEXT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Index pour performances
    INDEX idx_code_article (code_article),
    INDEX idx_article_id (article_id),
    INDEX idx_operation (operation),
    INDEX idx_date_operation (date_operation),
    INDEX idx_entree_id (entree_id),
    INDEX idx_sortie_id (sortie_id),
    INDEX idx_retour_id (retour_id),

    -- Clés étrangères
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE SET NULL,
    FOREIGN KEY (entree_id) REFERENCES entrees(id) ON DELETE SET NULL,
    FOREIGN KEY (sortie_id) REFERENCES sorties(id) ON DELETE SET NULL,
    FOREIGN KEY (retour_id) REFERENCES retours(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
