# Migration 008 - Historique des mouvements d'articles

## Description
Cette migration crée le système complet de traçabilité des mouvements de stock.

## Table créée : `historique_article`

### Structure
- **id** : Identifiant unique du mouvement
- **code_article** : Code de l'article
- **designation** : Désignation de l'article
- **operation** : Type d'opération (entree, sortie, retour)
- **qte** : Quantité du mouvement
- **stock_avant_operation** : Stock avant le mouvement
- **stock_apres_operation** : Stock après le mouvement
- **stock_initial** : Stock initial de l'article
- **stock_min** : Stock minimum de l'article
- **stock_max** : Stock maximum de l'article
- **article_id** : ID de l'article (FK)
- **entree_id** : ID de l'entrée (FK, nullable)
- **sortie_id** : ID de la sortie (FK, nullable)
- **retour_id** : ID du retour (FK, nullable)
- **user_id** : ID de l'utilisateur (FK)
- **date_operation** : Date et heure du mouvement
- **commentaire** : Commentaire optionnel
- **created_at** : Date de création de l'enregistrement

## Installation

### Option 1 : Via phpMyAdmin
1. Connectez-vous à phpMyAdmin
2. Sélectionnez la base de données `stock_materiel`
3. Allez dans l'onglet "SQL"
4. Copiez-collez le contenu du fichier `008_create_historique_article.sql`
5. Exécutez la requête

### Option 2 : Via ligne de commande
```bash
mysql -u root -p stock_materiel < migrations/008_create_historique_article.sql
```

### Option 3 : Via le script PHP
```bash
php migrations/run_migration_008.php
```

## Fonctionnalités implémentées

### 1. Classe HistoriqueArticle
Fichier : `classes/HistoriqueArticle.php`

Méthodes principales :
- `enregistrerEntree()` : Enregistre un mouvement d'entrée
- `enregistrerSortie()` : Enregistre un mouvement de sortie
- `enregistrerRetour()` : Enregistre un mouvement de retour
- `getAll()` : Récupère tous les mouvements avec filtres et pagination
- `getByArticle()` : Récupère l'historique d'un article spécifique
- `getStatistiques()` : Récupère les statistiques des mouvements

### 2. Modifications des fichiers de mouvements

#### Entrées
- `pages/entrees/create.php` : Enregistrement automatique dans l'historique
- `pages/entrees/delete.php` : Suppression des mouvements lors de la suppression d'une entrée

#### Sorties
- `pages/sorties/create.php` : Enregistrement automatique dans l'historique
- `pages/sorties/delete.php` : Suppression des mouvements lors de la suppression d'une sortie

#### Retours
- `pages/retours/create.php` : Enregistrement automatique dans l'historique
- `pages/retours/delete.php` : Suppression des mouvements lors de la suppression d'un retour

### 3. Interface de consultation
Fichier : `pages/mouvements/index.php`

Fonctionnalités :
- Liste complète des mouvements avec pagination
- Filtres de recherche :
  - Par code article
  - Par désignation
  - Par type d'opération (entrée/sortie/retour)
  - Par période (date début/fin)
- Statistiques des mouvements (nombre et quantités par type)
- Affichage des stocks avant/après opération
- Alertes visuelles pour stocks min/max
- Export possible (à implémenter si besoin)

### 4. Menu de navigation
Le lien "Historique des mouvements" a été ajouté dans le menu "Mouvements" de la barre de navigation.

## Utilisation

### Consultation de l'historique
1. Connectez-vous à l'application
2. Menu "Mouvements" > "Historique des mouvements"
3. Utilisez les filtres pour affiner la recherche
4. Consultez les statistiques en haut de page

### Traçabilité
Chaque fois qu'une entrée, sortie ou retour est créé(e), un enregistrement est automatiquement ajouté dans l'historique avec :
- La quantité du mouvement
- Le stock avant l'opération
- Le stock après l'opération
- Les seuils min/max de l'article
- L'utilisateur qui a effectué l'opération
- La date et l'heure de l'opération

### Suppression
Lorsqu'une entrée, sortie ou retour est supprimé(e), les mouvements correspondants sont automatiquement supprimés de l'historique.

## Avantages

1. **Traçabilité complète** : Tous les mouvements sont enregistrés automatiquement
2. **Analyse des stocks** : Possibilité d'analyser l'évolution des stocks dans le temps
3. **Audit** : Piste d'audit complète pour chaque article
4. **Alertes** : Identification rapide des stocks en dessous du minimum ou au-dessus du maximum
5. **Statistiques** : Vue d'ensemble des mouvements par type et par période

## Notes importantes

- La table utilise des clés étrangères avec `ON DELETE SET NULL` pour conserver l'historique même si l'entité liée est supprimée
- Les index sont optimisés pour les recherches fréquentes
- La pagination est implémentée pour gérer de grandes quantités de données
- Le système est conçu pour être performant même avec des milliers de mouvements

## Prochaines améliorations possibles

1. Export Excel/PDF de l'historique
2. Graphiques d'évolution des stocks
3. Notifications automatiques en cas de stock faible
4. Prévisions de réapprovisionnement basées sur l'historique
5. Comparaison des mouvements entre périodes
