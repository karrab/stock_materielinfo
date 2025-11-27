<?php
$page_title = 'Paramètres';
require_once __DIR__ . '/../../includes/header.php';

$auth->requireAdmin();
$db = Database::getInstance();

// Récupérer les paramètres actuels
$db->prepare("SELECT * FROM parametres WHERE id = 1");
$parametres = $db->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_entreprise = trim($_POST['nom_entreprise'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $tel = trim($_POST['tel'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $site_web = trim($_POST['site_web'] ?? '');
    $logo = $parametres['logo'] ?? '';

    $errors = [];
    if (empty($nom_entreprise)) $errors[] = 'Le nom de l\'entreprise est obligatoire.';

    // Gestion upload logo
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['logo']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed) && $_FILES['logo']['size'] <= 2000000) {
            $newname = 'logo_' . time() . '.' . $ext;
            $upload_path = __DIR__ . '/../../uploads/logo/';

            if (!is_dir($upload_path)) {
                mkdir($upload_path, 0755, true);
            }

            if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path . $newname)) {
                // Supprimer ancien logo
                if (!empty($logo) && file_exists($upload_path . $logo)) {
                    unlink($upload_path . $logo);
                }
                $logo = $newname;
            } else {
                $errors[] = 'Erreur lors de l\'upload du logo.';
            }
        } else {
            $errors[] = 'Logo non autorisé ou trop volumineux (max 2MB, formats: JPG, PNG, GIF).';
        }
    }

    if (empty($errors)) {
        try {
            if ($parametres) {
                // Update
                $sql = "UPDATE parametres SET
                            nom_entreprise = :nom_entreprise,
                            adresse = :adresse,
                            tel = :tel,
                            email = :email,
                            site_web = :site_web,
                            logo = :logo,
                            updated_at = NOW()
                        WHERE id = 1";
            } else {
                // Insert
                $sql = "INSERT INTO parametres (id, nom_entreprise, adresse, tel, email, site_web, logo)
                        VALUES (1, :nom_entreprise, :adresse, :tel, :email, :site_web, :logo)";
            }

            $db->prepare($sql);
            $db->bind(':nom_entreprise', $nom_entreprise);
            $db->bind(':adresse', $adresse);
            $db->bind(':tel', $tel);
            $db->bind(':email', $email);
            $db->bind(':site_web', $site_web);
            $db->bind(':logo', $logo);

            if ($db->execute()) {
                $auth->logTrace($auth->getUserId(), 'parametres', 'update', 'parametres', 1, "Modification paramètres");
                $_SESSION['success'] = 'Paramètres mis à jour avec succès.';
                header('Location: ' . BASE_URL . '/pages/parametres/edit.php');
                exit;
            }
        } catch (Exception $e) {
            $errors[] = 'Erreur: ' . $e->getMessage();
        }
    }
} else {
    $nom_entreprise = $parametres['nom_entreprise'] ?? 'Gestion Stock Matériel Informatique';
    $adresse = $parametres['adresse'] ?? '';
    $tel = $parametres['tel'] ?? '';
    $email = $parametres['email'] ?? '';
    $site_web = $parametres['site_web'] ?? '';
    $logo = $parametres['logo'] ?? '';
}
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-gear"></i> Paramètres de l'application</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                    <li class="breadcrumb-item active">Paramètres</li>
                </ol>
            </nav>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo $error; ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-header"><i class="bi bi-building"></i> Informations de l'entreprise</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="nom_entreprise" class="form-label required">Nom de l'entreprise</label>
                            <input type="text" class="form-control" id="nom_entreprise" name="nom_entreprise" required value="<?php echo htmlspecialchars($nom_entreprise); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="adresse" class="form-label">Adresse</label>
                            <textarea class="form-control" id="adresse" name="adresse" rows="3"><?php echo htmlspecialchars($adresse); ?></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="tel" class="form-label">Téléphone</label>
                                <input type="tel" class="form-control" id="tel" name="tel" value="<?php echo htmlspecialchars($tel); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="site_web" class="form-label">Site web</label>
                            <input type="url" class="form-control" id="site_web" name="site_web" value="<?php echo htmlspecialchars($site_web); ?>" placeholder="https://...">
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><i class="bi bi-image"></i> Logo de l'entreprise</div>
                    <div class="card-body">
                        <?php if (!empty($logo)): ?>
                            <div class="mb-3">
                                <label class="form-label">Logo actuel :</label>
                                <div>
                                    <img src="<?php echo BASE_URL; ?>/uploads/logo/<?php echo htmlspecialchars($logo); ?>" alt="Logo" style="max-height: 150px;">
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="mb-3">
                            <label for="logo" class="form-label">Nouveau logo</label>
                            <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                            <small class="text-muted">Formats acceptés: JPG, PNG, GIF - Taille max: 2MB</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header"><i class="bi bi-gear"></i> Actions</div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-save"></i> Enregistrer
                            </button>
                            <a href="<?php echo BASE_URL; ?>/index.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Retour
                            </a>
                        </div>
                        <hr>
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-info-circle"></i>
                            <strong>Information</strong>
                            <p class="mb-0 mt-2 small">
                                Ces paramètres seront utilisés sur tous les documents générés (PDF, etc.).
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
