-- ============================================
-- Migration: Ajouter les permissions pour mouvements (historique)
-- Date: 2026-01-07
-- Description: Ajouter les permissions pour le module historique des mouvements
-- ============================================

-- Ajouter les permissions pour mouvements
INSERT INTO `permissions` (`nom`, `module`, `action`, `description`) VALUES
('Mouvements - Voir', 'mouvements', 'view', 'Voir l\'historique des mouvements'),
('Mouvements - Exporter', 'mouvements', 'export', 'Exporter l\'historique des mouvements');

-- Attribution automatique au rôle Admin (id=1)
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, `id` FROM `permissions` WHERE `module` = 'mouvements';

-- Attribution au rôle Gestionnaire (id=2)
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 2, `id` FROM `permissions` WHERE `module` = 'mouvements';
