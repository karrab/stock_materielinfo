-- ============================================
-- Migration: Ajouter les permissions pour retour_fournisseur
-- Date: 2026-01-07
-- Description: Ajouter les permissions CRUD et PDF pour le module retour_fournisseur
-- ============================================

-- Ajouter les permissions pour retour_fournisseur
INSERT INTO `permissions` (`nom`, `module`, `action`, `description`) VALUES
('Retours Fournisseur - Voir', 'retour_fournisseur', 'view', 'Voir les retours fournisseur'),
('Retours Fournisseur - Ajouter', 'retour_fournisseur', 'create', 'Ajouter des retours fournisseur'),
('Retours Fournisseur - Modifier', 'retour_fournisseur', 'update', 'Modifier des retours fournisseur'),
('Retours Fournisseur - Supprimer', 'retour_fournisseur', 'delete', 'Supprimer des retours fournisseur'),
('Retours Fournisseur - PDF', 'retour_fournisseur', 'pdf', 'Générer PDF des retours fournisseur');

-- Attribution automatique au rôle Admin (id=1)
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, `id` FROM `permissions` WHERE `module` = 'retour_fournisseur';

-- Attribution au rôle Gestionnaire (id=2) - toutes sauf delete
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 2, `id` FROM `permissions`
WHERE `module` = 'retour_fournisseur' AND `action` != 'delete';
