<?php
require_once __DIR__ . '/../../config/config.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requirePermission('retours', 'view');

$id = $_GET['id'] ?? 0;

if (empty($id)) {
    $_SESSION['error'] = 'Retour introuvable.';
    header('Location: ' . BASE_URL . '/pages/retours/index.php');
    exit;
}

try {
    $pdf = new PDF();
    $pdf->generateBonEntree($id);
} catch (Exception $e) {
    $_SESSION['error'] = 'Erreur lors de la génération du PDF: ' . $e->getMessage();
    header('Location: ' . BASE_URL . '/pages/retours/view.php?id=' . $id);
    exit;
}
