-- ============================================
-- Script d'insertion de données de test
-- ============================================

-- Vérifier et insérer des services de test
INSERT INTO `services` (`nom`, `notes`) VALUES
('Direction Générale', 'Service de direction'),
('Ressources Humaines', 'Gestion du personnel'),
('Informatique', 'Service IT'),
('Comptabilité', 'Service financier'),
('Logistique', 'Gestion des stocks et approvisionnements')
ON DUPLICATE KEY UPDATE nom=nom;

-- Vérifier et insérer des fournisseurs de test
INSERT INTO `fournisseurs` (`nom_complet`, `adresse`, `ville`, `pays`, `tel1`, `mail`, `actif`) VALUES
('Fournisseur Informatique SARL', '123 Rue de la Tech', 'Paris', 'France', '0123456789', 'contact@fournisseur-it.fr', 1),
('Bureautique Pro', '456 Avenue du Bureau', 'Lyon', 'France', '0987654321', 'info@bureautique-pro.fr', 1),
('Matériel Express', '789 Boulevard du Stock', 'Marseille', 'France', '0147258369', 'contact@materiel-express.fr', 1)
ON DUPLICATE KEY UPDATE nom_complet=nom_complet;

-- Récupérer les IDs des services pour les employés
SET @service_direction = (SELECT id FROM services WHERE nom = 'Direction Générale' LIMIT 1);
SET @service_rh = (SELECT id FROM services WHERE nom = 'Ressources Humaines' LIMIT 1);
SET @service_it = (SELECT id FROM services WHERE nom = 'Informatique' LIMIT 1);

-- Insérer des employés de test
INSERT INTO `employes` (`matricule`, `nom`, `prenom`, `service_id`, `mail`, `tel1`, `actif`) VALUES
('EMP001', 'DUPONT', 'Jean', @service_direction, 'j.dupont@entreprise.com', '0601020304', 1),
('EMP002', 'MARTIN', 'Marie', @service_rh, 'm.martin@entreprise.com', '0605060708', 1),
('EMP003', 'BERNARD', 'Pierre', @service_it, 'p.bernard@entreprise.com', '0609101112', 1),
('EMP004', 'DURAND', 'Sophie', @service_it, 's.durand@entreprise.com', '0613141516', 1)
ON DUPLICATE KEY UPDATE matricule=matricule;

-- Récupérer les IDs des employés
SET @emp_dupont = (SELECT id FROM employes WHERE matricule = 'EMP001' LIMIT 1);
SET @emp_martin = (SELECT id FROM employes WHERE matricule = 'EMP002' LIMIT 1);
SET @emp_bernard = (SELECT id FROM employes WHERE matricule = 'EMP003' LIMIT 1);
SET @emp_durand = (SELECT id FROM employes WHERE matricule = 'EMP004' LIMIT 1);

-- Insérer des bureaux de test avec employés assignés
INSERT INTO `bureaux` (`code_local`, `service_id`, `employe_id`) VALUES
('B001', @service_direction, @emp_dupont),
('B002', @service_rh, @emp_martin),
('B003', @service_it, @emp_bernard),
('B004', @service_it, @emp_durand)
ON DUPLICATE KEY UPDATE code_local=code_local;

-- Insérer des armoires de test
INSERT INTO `armoires` (`numero`, `nom`) VALUES
('ARM-001', 'Armoire Fournitures Bureau'),
('ARM-002', 'Armoire Matériel Informatique'),
('ARM-003', 'Armoire Archive'),
('ARM-004', 'Armoire Stock Principal')
ON DUPLICATE KEY UPDATE numero=numero;

-- Insérer des équipes d'inventaire de test
INSERT INTO `equipes_inventaire` (`nom`, `description`) VALUES
('Équipe Alpha', 'Équipe principale d\'inventaire'),
('Équipe Beta', 'Équipe secondaire'),
('Équipe Gamma', 'Équipe spécialisée matériel IT')
ON DUPLICATE KEY UPDATE nom=nom;

-- Insérer des catégories d'articles
INSERT INTO `categories` (`nom`, `description`) VALUES
('Informatique', 'Matériel et fournitures informatiques'),
('Bureautique', 'Fournitures de bureau'),
('Mobilier', 'Meubles et équipements'),
('Consommables', 'Produits consommables')
ON DUPLICATE KEY UPDATE nom=nom;

-- Récupérer les IDs des catégories
SET @cat_informatique = (SELECT id FROM categories WHERE nom = 'Informatique' LIMIT 1);
SET @cat_bureautique = (SELECT id FROM categories WHERE nom = 'Bureautique' LIMIT 1);
SET @cat_mobilier = (SELECT id FROM categories WHERE nom = 'Mobilier' LIMIT 1);
SET @cat_consommables = (SELECT id FROM categories WHERE nom = 'Consommables' LIMIT 1);

-- Insérer des articles de test
INSERT INTO `articles` (`reference`, `designation`, `categorie_id`, `qte_disponible`, `qte_min`, `unite`, `actif`) VALUES
('PC-001', 'Ordinateur portable Dell Latitude', @cat_informatique, 10, 2, 'Unité', 1),
('PC-002', 'Souris sans fil Logitech', @cat_informatique, 25, 5, 'Unité', 1),
('PC-003', 'Clavier USB standard', @cat_informatique, 20, 5, 'Unité', 1),
('PC-004', 'Écran 24 pouces HP', @cat_informatique, 8, 2, 'Unité', 1),
('BUR-001', 'Ramette papier A4', @cat_bureautique, 50, 10, 'Ramette', 1),
('BUR-002', 'Stylo bille bleu', @cat_bureautique, 100, 20, 'Boîte', 1),
('BUR-003', 'Classeur à levier', @cat_bureautique, 30, 10, 'Unité', 1),
('MOB-001', 'Chaise de bureau ergonomique', @cat_mobilier, 15, 3, 'Unité', 1),
('MOB-002', 'Bureau 160x80 cm', @cat_mobilier, 5, 1, 'Unité', 1),
('CONS-001', 'Cartouche d\'encre noir HP', @cat_consommables, 40, 10, 'Unité', 1)
ON DUPLICATE KEY UPDATE reference=reference;

-- Message de confirmation
SELECT '✅ Données de test insérées avec succès !' as message;

-- Vérification
SELECT 'Services' as table_name, COUNT(*) as count FROM services
UNION ALL
SELECT 'Fournisseurs', COUNT(*) FROM fournisseurs WHERE actif = 1
UNION ALL
SELECT 'Employés', COUNT(*) FROM employes WHERE actif = 1
UNION ALL
SELECT 'Bureaux', COUNT(*) FROM bureaux
UNION ALL
SELECT 'Armoires', COUNT(*) FROM armoires
UNION ALL
SELECT 'Équipes', COUNT(*) FROM equipes_inventaire
UNION ALL
SELECT 'Catégories', COUNT(*) FROM categories
UNION ALL
SELECT 'Articles', COUNT(*) FROM articles WHERE actif = 1;
