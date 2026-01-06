# Migration 009 - Module Retour Fournisseur

## Description
Ce module gère les retours d'articles vers les fournisseurs (articles défectueux, non conformes, etc.).

**IMPORTANT** : Un retour fournisseur **DIMINUE** le stock disponible (contrairement au retour employé qui l'augmente).

## Tables créées

### 1. `retour_fournisseur`
- **id** : Identifiant unique
- **fournisseur_id** : ID du fournisseur (FK)
- **date** : Date du retour
- **fichier** : Fichier joint (bon de retour, etc.)
- **notes** : Notes/commentaires
- **user_id** : Utilisateur ayant créé le retour (FK)
- **created_at** / **updated_at** : Timestamps

### 2. `ligne_retour_fournisseur`
- **id** : Identifiant unique
- **retour_fournisseur_id** : ID du retour (FK, CASCADE)
- **article_id** : ID de l'article (FK)
- **code_article** : Code de l'article
- **designation** : Désignation de l'article
- **qte** : Quantité retournée
- **created_at** : Timestamp

### 3. Modification `historique_article`
- Ajout de `'retour_fournisseur'` dans l'enum operation
- Ajout de la colonne `retour_fournisseur_id` (FK)

## Installation

### Méthode 1 : Via script PHP
```bash
php migrations/run_migration_009.php
```

### Méthode 2 : Via phpMyAdmin
1. Ouvrir phpMyAdmin
2. Sélectionner la base `stock_materiel`
3. Onglet "SQL"
4. Copier/coller le contenu de `009_create_retour_fournisseur.sql`
5. Exécuter

### Méthode 3 : Via ligne de commande
```bash
mysql -u root -p stock_materiel < migrations/009_create_retour_fournisseur.sql
```

## Classe PHP : RetourFournisseur

### Méthodes principales
- `getAll($page, $perPage, $filters)` : Liste avec pagination et filtres
- `count($filters)` : Compte total avec filtres
- `getById($id)` : Récupère un retour avec ses lignes
- `getLignesByRetourId($id)` : Récupère les lignes d'un retour
- `create($data)` : Crée un nouveau retour
- `update($id, $data)` : Met à jour un retour
- `delete($id)` : Supprime un retour

### Logique métier
1. **Création** :
   - Insère le retour fournisseur
   - Insère les lignes
   - **DIMINUE** le stock disponible de chaque article
   - Enregistre dans `historique_article`

2. **Modification** :
   - Restaure les anciens stocks
   - Supprime les anciennes lignes
   - Insère les nouvelles lignes
   - Applique les nouveaux stocks
   - Met à jour `historique_article`

3. **Suppression** :
   - Restaure les stocks
   - Supprime de `historique_article`
   - Supprime les lignes (CASCADE)
   - Supprime le retour

## Fichiers CRUD

### Structure complète
```
pages/retour_fournisseur/
├── index.php    ✅ Liste avec filtres et pagination
├── create.php   📝 À créer - Formulaire de création
├── edit.php     📝 À créer - Formulaire de modification
├── view.php     📝 À créer - Affichage détaillé
├── delete.php   📝 À créer - Suppression
└── pdf.php      📝 À créer - Génération PDF
```

### index.php ✅ (Créé)
**Fonctionnalités** :
- Liste paginée des retours fournisseurs
- Filtres par :
  - Fournisseur
  - Date début
  - Date fin
- Affichage :
  - Numéro retour
  - Date
  - Fournisseur + contact
  - Nombre d'articles
  - Quantité totale
- Actions : Voir, PDF, Modifier, Supprimer

### create.php 📝 (À créer)
**Fonctionnalités requises** :
- Sélection fournisseur (Select2)
- Date du retour
- Upload fichier joint (optionnel)
- Notes (textarea)
- Tableau dynamique articles :
  - Sélection article (Select2 AJAX)
  - Quantité
  - Bouton ajouter/supprimer ligne
- Validation :
  - Fournisseur obligatoire
  - Date obligatoire
  - Au moins un article
  - Quantités positives
  - Vérifier stock suffisant

### edit.php 📝 (À créer)
**Fonctionnalités requises** :
- Même structure que create.php
- Pré-remplir les données existantes
- Pré-charger les lignes d'articles
- Permissions : vérifier droits modification

### view.php 📝 (À créer)
**Fonctionnalités requises** :
- Affichage détaillé :
  - En-tête : Numéro, Date, Fournisseur
  - Informations fournisseur complètes
  - Tableau des articles retournés
  - Total quantités
  - Notes
  - Fichier joint (lien téléchargement)
  - Utilisateur créateur
  - Dates création/modification
- Boutons d'action :
  - Retour liste
  - Générer PDF
  - Modifier
  - Supprimer

### delete.php 📝 (À créer)
**Code minimal** :
```php
<?php
require_once __DIR__ . '/../../config/config.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$retourFournisseur = new RetourFournisseur();
$id = $_GET['id'] ?? 0;

// Vérifier existence
$retour = $retourFournisseur->getById($id);

if (!$retour) {
    $_SESSION['error'] = 'Retour fournisseur introuvable.';
    header('Location: ' . BASE_URL . '/pages/retour_fournisseur/index.php');
    exit;
}

try {
    $retourFournisseur->delete($id);

    $auth->logTrace($auth->getUserId(), 'retour_fournisseur', 'delete', 'retour_fournisseur', $id, "Suppression retour fournisseur #" . $id);

    $_SESSION['success'] = 'Retour fournisseur supprimé avec succès.';
} catch (Exception $e) {
    $_SESSION['error'] = 'Erreur lors de la suppression: ' . $e->getMessage();
}

header('Location: ' . BASE_URL . '/pages/retour_fournisseur/index.php');
exit;
```

### pdf.php 📝 (À créer)
**Fonctionnalités requises** :
- Utiliser Dompdf
- Design Bootstrap 5 (comme bons entrée/sortie)
- En-tête avec logo
- Informations retour fournisseur
- Informations fournisseur
- Tableau articles avec :
  - Code
  - Désignation
  - Quantité retournée
  - Total
- Section notes
- Section signatures
- Footer avec numérotation

**Template** : S'inspirer de `classes/PDF.php` méthode `generateBonEntree()`

## Intégration menu

### À ajouter dans `includes/navbar.php`

Trouver la section "Mouvements" et ajouter :

```php
<li><hr class="dropdown-divider"></li>
<li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/pages/retour_fournisseur/index.php">
    <i class="bi bi-box-arrow-left"></i> Retours Fournisseur
</a></li>
```

Position suggérée : Après "Retours" (employés) et avant le séparateur "Historique des mouvements"

## Tests à effectuer

### Test création
1. Créer un retour fournisseur
2. Vérifier que les stocks diminuent correctement
3. Vérifier enregistrement dans `historique_article`
4. Vérifier génération PDF

### Test modification
1. Modifier un retour existant
2. Changer articles/quantités
3. Vérifier restauration anciens stocks
4. Vérifier application nouveaux stocks
5. Vérifier mise à jour `historique_article`

### Test suppression
1. Supprimer un retour
2. Vérifier restauration des stocks
3. Vérifier suppression `historique_article`
4. Vérifier suppression fichier joint

### Test filtres
1. Filtrer par fournisseur
2. Filtrer par période
3. Combiner filtres
4. Vérifier pagination

## Permissions

À ajouter dans la gestion des permissions (si applicable) :
- `retour_fournisseur.view` : Consulter les retours
- `retour_fournisseur.create` : Créer des retours
- `retour_fournisseur.update` : Modifier des retours
- `retour_fournisseur.delete` : Supprimer des retours

## Différences avec Retours (employés)

| Aspect | Retour Employé | Retour Fournisseur |
|--------|----------------|---------------------|
| **Action stock** | Augmente (+) | Diminue (-) |
| **Origine** | Employé/Service | Vers fournisseur |
| **Motif** | Article emprunté | Article défectueux |
| **Table** | `retours` | `retour_fournisseur` |
| **Historique** | `operation='retour'` | `operation='retour_fournisseur'` |

## Prochaines améliorations

1. Export Excel des retours fournisseurs
2. Statistiques par fournisseur
3. Suivi des motifs de retour
4. Notification automatique au fournisseur
5. Intégration avec système de garantie
6. Suivi du remboursement/remplacement

## Support

Pour toute question :
- Documentation complète : `migrations/README_009_retour_fournisseur.md`
- Classe PHP : `classes/RetourFournisseur.php`
- Migration SQL : `migrations/009_create_retour_fournisseur.sql`
