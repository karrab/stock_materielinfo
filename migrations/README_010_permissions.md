# Migration 010: Permissions pour Retour Fournisseur

## Description
Cette migration ajoute les permissions pour le module **Retour Fournisseur** permettant de gérer les droits d'accès pour chaque utilisateur selon son rôle.

## Permissions ajoutées
- **retour_fournisseur/view** : Voir les retours fournisseur
- **retour_fournisseur/create** : Créer des retours fournisseur
- **retour_fournisseur/update** : Modifier des retours fournisseur
- **retour_fournisseur/delete** : Supprimer des retours fournisseur
- **retour_fournisseur/pdf** : Générer des PDF de retours fournisseur

## Installation

### Méthode 1: Via phpMyAdmin
1. Ouvrez phpMyAdmin
2. Sélectionnez la base de données `stock_materiel`
3. Cliquez sur l'onglet "SQL"
4. Copiez le contenu du fichier `010_add_retour_fournisseur_permissions.sql`
5. Collez-le dans la zone de texte et cliquez sur "Exécuter"

### Méthode 2: Via ligne de commande
```bash
mysql -u root -p stock_materiel < migrations/010_add_retour_fournisseur_permissions.sql
```

### Méthode 3: Via script PHP (recommandé)
1. Naviguez vers: `http://votre-site/migrations/install_retour_fournisseur_permissions.php`
2. Le script exécutera automatiquement la migration

## Attribution automatique
Les permissions sont automatiquement attribuées aux rôles suivants:
- **Admin** (role_id=1): Toutes les permissions
- **Gestionnaire** (role_id=2): Toutes sauf delete

## Fichiers modifiés
- `pages/retour_fournisseur/index.php` : Ajout vérification permission 'view'
- `pages/retour_fournisseur/create.php` : Ajout vérification permission 'create'
- `pages/retour_fournisseur/edit.php` : Ajout vérification permission 'update'
- `pages/retour_fournisseur/view.php` : Ajout vérification permission 'view' + boutons conditionnels
- `pages/retour_fournisseur/delete.php` : Ajout vérification permission 'delete'
- `pages/retour_fournisseur/pdf.php` : Ajout vérification permission 'pdf'
- `includes/navbar.php` : Menu conditionnel basé sur permission 'view'
- `pages/roles/permissions.php` : Ajout icône et configuration pour retour_fournisseur
- `api/articles.php` : Ajout filtre `stock_only` pour retour_fournisseur

## Fonctionnalité supplémentaire: Filtrage des articles
### Articles avec stock > 0 uniquement
Dans la page `pages/retour_fournisseur/create.php`, la liste déroulante des articles affiche uniquement les articles ayant un **stock disponible supérieur à 0**.

Cette fonctionnalité évite de sélectionner des articles en rupture de stock lors de la création d'un retour fournisseur.

**Implémentation technique:**
- Paramètre `stock_only=1` dans l'API `api/articles.php`
- Fonction JavaScript personnalisée `initArticleSelectWithStock()` dans create.php
- Filtre SQL: `WHERE qte_disponible > 0`

## Vérification
Après installation, vérifiez que:
1. Les permissions apparaissent dans `pages/roles/permissions.php`
2. Le module "Retour Fournisseur" est visible/caché selon les droits de l'utilisateur
3. Les boutons d'actions (Modifier, Supprimer, PDF) sont visibles/cachés selon les droits
4. Dans create.php, seuls les articles avec stock > 0 sont affichés

## Support
En cas de problème, vérifiez:
- Que la base de données `stock_materiel` existe
- Que les tables `permissions` et `role_permissions` existent
- Que les rôles Admin (id=1) et Gestionnaire (id=2) existent
- Les logs d'erreur PHP/MySQL pour plus de détails
