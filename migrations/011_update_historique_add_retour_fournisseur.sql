-- ============================================
-- Migration: Ajouter retour_fournisseur à l'ENUM operation
-- Date: 2026-01-07
-- Description: Mise à jour de l'ENUM operation pour inclure retour_fournisseur
-- ============================================

-- Ajouter retour_fournisseur à l'ENUM operation dans historique_article
ALTER TABLE historique_article
MODIFY COLUMN operation ENUM('entree', 'sortie', 'retour', 'retour_fournisseur') NOT NULL;

-- Ajouter colonne retour_fournisseur_id si elle n'existe pas déjà
ALTER TABLE historique_article
ADD COLUMN IF NOT EXISTS retour_fournisseur_id INT NULL,
ADD FOREIGN KEY IF NOT EXISTS fk_historique_retour_fournisseur (retour_fournisseur_id)
    REFERENCES retour_fournisseur(id) ON DELETE SET NULL;
