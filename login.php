<?php
require_once __DIR__ . '/config/config.php';

$auth = new Auth();

// Si déjà connecté, rediriger vers le dashboard
if ($auth->isLoggedIn()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Traitement du formulaire de connexion
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($login) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        if ($auth->login($login, $password)) {
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        } else {
            $error = 'Identifiants incorrects.';
        }
    }
}

// Récupérer les paramètres
$db = Database::getInstance();
$db->prepare("SELECT * FROM parametres WHERE id = 1");
$parametres = $db->fetch();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - <?php echo APP_NAME; ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <!-- Custom CSS -->
    <link href="<?php echo BASE_URL; ?>/assets/css/style.css" rel="stylesheet">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo IMAGES_URL; ?>/<?php echo $parametres['logo'] ?? 'logo.png'; ?>">
</head>
<body>
    <div class="login-container">
        <div class="login-card card">
            <div class="card-header">
                <?php if (!empty($parametres['logo'])): ?>
                    <img src="<?php echo IMAGES_URL; ?>/<?php echo $parametres['logo']; ?>" alt="Logo" class="img-fluid">
                <?php endif; ?>
                <h4 class="mb-0"><?php echo $parametres['nom_etablissement'] ?? APP_NAME; ?></h4>
                <p class="mb-0 mt-2"><small>Système de Gestion de Stock</small></p>
            </div>
            <div class="card-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['info'])): ?>
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <i class="bi bi-info-circle"></i> <?php echo $_SESSION['info']; unset($_SESSION['info']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="login" class="form-label"><i class="bi bi-person"></i> Identifiant</label>
                        <input type="text" class="form-control form-control-lg" id="login" name="login"
                               placeholder="Saisissez votre identifiant" required autofocus
                               value="<?php echo htmlspecialchars($login ?? ''); ?>">
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label"><i class="bi bi-lock"></i> Mot de passe</label>
                        <input type="password" class="form-control form-control-lg" id="password" name="password"
                               placeholder="Saisissez votre mot de passe" required>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-box-arrow-in-right"></i> Se connecter
                        </button>
                    </div>
                </form>

                <hr class="my-4">

                <div class="text-center text-muted small">
                    <p class="mb-0">Comptes de démonstration :</p>
                    <p class="mb-0"><strong>Admin:</strong> admin / admin123</p>
                    <p class="mb-0"><strong>Gestionnaire:</strong> gestionnaire / admin123</p>
                </div>
            </div>
            <div class="card-footer text-center text-muted">
                <small>&copy; <?php echo date('Y'); ?> - v<?php echo APP_VERSION; ?></small>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
