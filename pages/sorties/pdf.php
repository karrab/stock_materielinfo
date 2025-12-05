<?php
require_once __DIR__ . '/../../config/config.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requirePermission('sorties', 'view');

$id = $_GET['id'] ?? 0;

if (empty($id)) {
    $_SESSION['error'] = 'Sortie introuvable.';
    header('Location: ' . BASE_URL . '/pages/sorties/index.php');
    exit;
}

try {
    $pdf = new PDF();
    $pdf->generateBonSortie($id);
} catch (Exception $e) {
    $_SESSION['error'] = 'Erreur lors de la génération du PDF: ' . $e->getMessage();
    header('Location: ' . BASE_URL . '/pages/sorties/view.php?id=' . $id);
    exit;
}
