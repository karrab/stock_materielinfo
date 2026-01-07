<?php
require_once __DIR__ . '/../../config/config.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requirePermission('retour_fournisseur', 'pdf');

$id = $_GET['id'] ?? 0;

if (empty($id)) {
    die('ID retour fournisseur manquant');
}

try {
    $pdf = new PDF();
    $pdf->generateBonRetourFournisseur($id);
} catch (Exception $e) {
    die('Erreur lors de la génération du PDF: ' . $e->getMessage());
}
