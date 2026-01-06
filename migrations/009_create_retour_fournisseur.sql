-- Migration: Création des tables pour les retours fournisseurs
-- Date: 2026-01-06

-- Table retour_fournisseur
CREATE TABLE IF NOT EXISTS retour_fournisseur (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fournisseur_id INT NOT NULL,
    date DATE NOT NULL,
    fichier VARCHAR(255) NULL,
    notes TEXT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_fournisseur_id (fournisseur_id),
    INDEX idx_date (date),
    INDEX idx_user_id (user_id),

    FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs(id) ON DELETE RESTRICT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table ligne_retour_fournisseur
CREATE TABLE IF NOT EXISTS ligne_retour_fournisseur (
    id INT AUTO_INCREMENT PRIMARY KEY,
    retour_fournisseur_id INT NOT NULL,
    article_id INT NOT NULL,
    code_article VARCHAR(50) NOT NULL,
    designation VARCHAR(255) NOT NULL,
    qte DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_retour_fournisseur_id (retour_fournisseur_id),
    INDEX idx_article_id (article_id),

    FOREIGN KEY (retour_fournisseur_id) REFERENCES retour_fournisseur(id) ON DELETE CASCADE,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Modifier la table historique_article pour ajouter 'retour_fournisseur' dans l'enum
ALTER TABLE historique_article
MODIFY COLUMN operation ENUM('entree', 'sortie', 'retour', 'retour_fournisseur') NOT NULL;

-- Ajouter la colonne retour_fournisseur_id à la table historique_article
ALTER TABLE historique_article
ADD COLUMN retour_fournisseur_id INT NULL AFTER retour_id,
ADD INDEX idx_retour_fournisseur_id (retour_fournisseur_id),
ADD FOREIGN KEY (retour_fournisseur_id) REFERENCES retour_fournisseur(id) ON DELETE SET NULL;
