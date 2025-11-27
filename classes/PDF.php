<?php
/**
 * Classe PDF - Génération de documents PDF avec Dompdf
 */

// Charger Dompdf si installé via Composer
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

use Dompdf\Dompdf;
use Dompdf\Options;

class PDF {
    private $dompdf;
    private $options;
    private $parametres;
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();

        // Récupérer les paramètres
        $this->db->prepare("SELECT * FROM parametres WHERE id = 1");
        $this->parametres = $this->db->fetch();

        // Configuration Dompdf
        $this->options = new Options();
        $this->options->set('isHtml5ParserEnabled', true);
        $this->options->set('isRemoteEnabled', true);
        $this->options->set('defaultFont', 'DejaVu Sans');
        $this->options->set('chroot', ROOT_PATH);

        $this->dompdf = new Dompdf($this->options);
    }

    /**
     * Générer un PDF
     */
    public function generate($html, $filename = 'document.pdf', $orientation = 'portrait', $paperSize = 'A4') {
        $fullHtml = $this->getTemplate($html);

        $this->dompdf->loadHtml($fullHtml);
        $this->dompdf->setPaper($paperSize, $orientation);
        $this->dompdf->render();

        // Stream le PDF (afficher dans le navigateur)
        $this->dompdf->stream($filename, ['Attachment' => false]);
    }

    /**
     * Sauvegarder un PDF dans un fichier
     */
    public function save($html, $filepath, $orientation = 'portrait', $paperSize = 'A4') {
        $fullHtml = $this->getTemplate($html);

        $this->dompdf->loadHtml($fullHtml);
        $this->dompdf->setPaper($paperSize, $orientation);
        $this->dompdf->render();

        file_put_contents($filepath, $this->dompdf->output());
    }

    /**
     * Template HTML complet avec styles
     */
    private function getTemplate($content) {
        $logoPath = IMAGES_PATH . '/' . ($this->parametres['logo'] ?? 'logo.png');
        $logoData = '';

        if (file_exists($logoPath)) {
            $logoData = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }

        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }

                body {
                    font-family: "DejaVu Sans", sans-serif;
                    font-size: 11pt;
                    color: #333;
                    line-height: 1.4;
                }

                .header {
                    text-align: center;
                    margin-bottom: 30px;
                    padding-bottom: 20px;
                    border-bottom: 3px solid #0d6efd;
                }

                .header img {
                    max-height: 60px;
                    margin-bottom: 10px;
                }

                .header h1 {
                    font-size: 18pt;
                    color: #0d6efd;
                    margin: 10px 0;
                }

                .header .info {
                    font-size: 9pt;
                    color: #666;
                    margin-top: 5px;
                }

                .footer {
                    position: fixed;
                    bottom: 0;
                    left: 0;
                    right: 0;
                    height: 50px;
                    text-align: center;
                    font-size: 8pt;
                    color: #666;
                    border-top: 1px solid #ddd;
                    padding-top: 10px;
                }

                .page-number:before {
                    content: "Page " counter(page);
                }

                h2 {
                    color: #0d6efd;
                    font-size: 16pt;
                    margin: 20px 0 15px 0;
                    padding-bottom: 5px;
                    border-bottom: 2px solid #0d6efd;
                }

                h3 {
                    color: #333;
                    font-size: 13pt;
                    margin: 15px 0 10px 0;
                }

                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 15px 0;
                }

                table thead th {
                    background-color: #0d6efd;
                    color: white;
                    padding: 10px;
                    text-align: left;
                    font-weight: bold;
                    font-size: 10pt;
                }

                table tbody td {
                    padding: 8px;
                    border: 1px solid #ddd;
                    font-size: 10pt;
                }

                table tbody tr:nth-child(even) {
                    background-color: #f8f9fa;
                }

                .info-box {
                    background-color: #f8f9fa;
                    padding: 15px;
                    margin: 15px 0;
                    border-left: 4px solid #0d6efd;
                }

                .info-box .label {
                    font-weight: bold;
                    color: #0d6efd;
                    display: inline-block;
                    min-width: 150px;
                }

                .info-box .value {
                    color: #333;
                }

                .text-right {
                    text-align: right;
                }

                .text-center {
                    text-align: center;
                }

                .badge {
                    display: inline-block;
                    padding: 3px 8px;
                    font-size: 9pt;
                    border-radius: 3px;
                    font-weight: bold;
                }

                .badge-success {
                    background-color: #198754;
                    color: white;
                }

                .badge-warning {
                    background-color: #ffc107;
                    color: #000;
                }

                .badge-danger {
                    background-color: #dc3545;
                    color: white;
                }

                .badge-info {
                    background-color: #0dcaf0;
                    color: #000;
                }

                .total-row {
                    background-color: #e7f1ff !important;
                    font-weight: bold;
                    font-size: 11pt;
                }
            </style>
        </head>
        <body>
            <div class="header">
                ' . (!empty($logoData) ? '<img src="' . $logoData . '" alt="Logo">' : '') . '
                <h1>' . htmlspecialchars($this->parametres['nom_etablissement'] ?? APP_NAME) . '</h1>
                <div class="info">
                    ' . htmlspecialchars($this->parametres['adresse'] ?? '') . '<br>
                    Tél: ' . htmlspecialchars($this->parametres['tel_fixe'] ?? '') . ' |
                    Email: ' . htmlspecialchars($this->parametres['email'] ?? '') . '
                </div>
            </div>

            <div class="content">
                ' . $content . '
            </div>

            <div class="footer">
                <div class="page-number"></div>
                <div>Généré le ' . date('d/m/Y à H:i') . ' - © ' . date('Y') . ' ' . htmlspecialchars($this->parametres['nom_etablissement'] ?? APP_NAME) . '</div>
            </div>
        </body>
        </html>
        ';
    }

    /**
     * Générer un bon d'entrée
     */
    public function generateBonEntree($entree_id) {
        // Récupérer les données
        $sql = "SELECT e.*, f.nom_complet as fournisseur, f.adresse as fournisseur_adresse,
                       f.ville, f.tel1, u.nom as user_nom, u.prenom as user_prenom
                FROM entrees e
                INNER JOIN fournisseurs f ON e.fournisseur_id = f.id
                INNER JOIN users u ON e.user_id = u.id
                WHERE e.id = :id";

        $this->db->prepare($sql);
        $this->db->bind(':id', $entree_id);
        $entree = $this->db->fetch();

        // Récupérer les lignes
        $this->db->prepare("SELECT * FROM ligne_entrees WHERE entree_id = :id ORDER BY id");
        $this->db->bind(':id', $entree_id);
        $lignes = $this->db->fetchAll();

        // Construire le HTML
        $html = '
            <h2>BON D\'ENTRÉE N° ' . $entree['id'] . '</h2>

            <div class="info-box">
                <div><span class="label">Date:</span> <span class="value">' . date('d/m/Y', strtotime($entree['date'])) . '</span></div>
                <div><span class="label">Fournisseur:</span> <span class="value">' . htmlspecialchars($entree['fournisseur']) . '</span></div>
                <div><span class="label">Adresse:</span> <span class="value">' . htmlspecialchars($entree['fournisseur_adresse'] . ', ' . $entree['ville']) . '</span></div>
                <div><span class="label">Téléphone:</span> <span class="value">' . htmlspecialchars($entree['tel1']) . '</span></div>
                <div><span class="label">Créé par:</span> <span class="value">' . htmlspecialchars($entree['user_prenom'] . ' ' . $entree['user_nom']) . '</span></div>
            </div>

            <h3>Articles</h3>
            <table>
                <thead>
                    <tr>
                        <th width="15%">Code article</th>
                        <th width="55%">Désignation</th>
                        <th width="15%" class="text-right">Quantité</th>
                        <th width="15%" class="text-right">Unité</th>
                    </tr>
                </thead>
                <tbody>';

        $total_qte = 0;
        foreach ($lignes as $ligne) {
            $total_qte += $ligne['qte_entree'];

            $html .= '
                    <tr>
                        <td>' . htmlspecialchars($ligne['code_article']) . '</td>
                        <td>' . htmlspecialchars($ligne['designation']) . '</td>
                        <td class="text-right">' . number_format($ligne['qte_entree'], 2, ',', ' ') . '</td>
                        <td class="text-right">Unité(s)</td>
                    </tr>';
        }

        $html .= '
                    <tr class="total-row">
                        <td colspan="2" class="text-right">TOTAL</td>
                        <td class="text-right">' . number_format($total_qte, 2, ',', ' ') . '</td>
                        <td class="text-right">Unité(s)</td>
                    </tr>
                </tbody>
            </table>';

        if (!empty($entree['notes'])) {
            $html .= '
                <h3>Notes</h3>
                <div class="info-box">
                    ' . nl2br(htmlspecialchars($entree['notes'])) . '
                </div>';
        }

        $html .= '
            <div style="margin-top: 50px;">
                <table style="border: none;">
                    <tr>
                        <td width="50%" style="border: none; text-align: center;">
                            <div style="border-top: 1px solid #000; display: inline-block; padding-top: 5px; min-width: 200px;">
                                Signature Fournisseur
                            </div>
                        </td>
                        <td width="50%" style="border: none; text-align: center;">
                            <div style="border-top: 1px solid #000; display: inline-block; padding-top: 5px; min-width: 200px;">
                                Signature Réceptionnaire
                            </div>
                        </td>
                    </tr>
                </table>
            </div>';

        $this->generate($html, 'bon_entree_' . $entree_id . '.pdf');
    }

    /**
     * Générer un bon de sortie
     */
    public function generateBonSortie($sortie_id) {
        // Récupérer les données
        $sql = "SELECT s.*, ser.nom as service, CONCAT(emp.nom, ' ', emp.prenom) as employe,
                       ser2.nom as service_affectation, CONCAT(emp2.nom, ' ', emp2.prenom) as employe_affectation,
                       b.code_local as bureau, a.numero as armoire,
                       u.nom as user_nom, u.prenom as user_prenom
                FROM sorties s
                INNER JOIN services ser ON s.service_id = ser.id
                INNER JOIN employes emp ON s.employe_id = emp.id
                LEFT JOIN services ser2 ON s.service_affectation_id = ser2.id
                LEFT JOIN employes emp2 ON s.employe_affectation_id = emp2.id
                LEFT JOIN bureaux b ON s.bureau_id = b.id
                LEFT JOIN armoires a ON s.armoire_id = a.id
                INNER JOIN users u ON s.user_id = u.id
                WHERE s.id = :id";

        $this->db->prepare($sql);
        $this->db->bind(':id', $sortie_id);
        $sortie = $this->db->fetch();

        // Récupérer les lignes
        $this->db->prepare("SELECT * FROM ligne_sorties WHERE sortie_id = :id ORDER BY id");
        $this->db->bind(':id', $sortie_id);
        $lignes = $this->db->fetchAll();

        // Construire le HTML
        $html = '
            <h2>BON DE SORTIE N° ' . $sortie['id'] . '</h2>

            <div class="info-box">
                <div><span class="label">Date:</span> <span class="value">' . date('d/m/Y', strtotime($sortie['date'])) . '</span></div>
                <div><span class="label">Service demandeur:</span> <span class="value">' . htmlspecialchars($sortie['service']) . '</span></div>
                <div><span class="label">Employé demandeur:</span> <span class="value">' . htmlspecialchars($sortie['employe']) . '</span></div>';

        if (!empty($sortie['service_affectation'])) {
            $html .= '<div><span class="label">Service affectation:</span> <span class="value">' . htmlspecialchars($sortie['service_affectation']) . '</span></div>';
        }
        if (!empty($sortie['employe_affectation'])) {
            $html .= '<div><span class="label">Employé affectation:</span> <span class="value">' . htmlspecialchars($sortie['employe_affectation']) . '</span></div>';
        }
        if (!empty($sortie['bureau'])) {
            $html .= '<div><span class="label">Bureau:</span> <span class="value">' . htmlspecialchars($sortie['bureau']) . '</span></div>';
        }
        if (!empty($sortie['armoire'])) {
            $html .= '<div><span class="label">Armoire:</span> <span class="value">' . htmlspecialchars($sortie['armoire']) . '</span></div>';
        }

        $html .= '
                <div><span class="label">Créé par:</span> <span class="value">' . htmlspecialchars($sortie['user_prenom'] . ' ' . $sortie['user_nom']) . '</span></div>
            </div>

            <h3>Articles</h3>
            <table>
                <thead>
                    <tr>
                        <th width="15%">Code article</th>
                        <th width="55%">Désignation</th>
                        <th width="15%" class="text-right">Quantité</th>
                        <th width="15%" class="text-right">Unité</th>
                    </tr>
                </thead>
                <tbody>';

        $total_qte = 0;
        foreach ($lignes as $ligne) {
            $total_qte += $ligne['qte_sortie'];

            $html .= '
                    <tr>
                        <td>' . htmlspecialchars($ligne['code_article']) . '</td>
                        <td>' . htmlspecialchars($ligne['designation']) . '</td>
                        <td class="text-right">' . number_format($ligne['qte_sortie'], 2, ',', ' ') . '</td>
                        <td class="text-right">Unité(s)</td>
                    </tr>';
        }

        $html .= '
                    <tr class="total-row">
                        <td colspan="2" class="text-right">TOTAL</td>
                        <td class="text-right">' . number_format($total_qte, 2, ',', ' ') . '</td>
                        <td class="text-right">Unité(s)</td>
                    </tr>
                </tbody>
            </table>';

        if (!empty($sortie['notes'])) {
            $html .= '
                <h3>Notes</h3>
                <div class="info-box">
                    ' . nl2br(htmlspecialchars($sortie['notes'])) . '
                </div>';
        }

        $html .= '
            <div style="margin-top: 50px;">
                <table style="border: none;">
                    <tr>
                        <td width="50%" style="border: none; text-align: center;">
                            <div style="border-top: 1px solid #000; display: inline-block; padding-top: 5px; min-width: 200px;">
                                Signature Magasinier
                            </div>
                        </td>
                        <td width="50%" style="border: none; text-align: center;">
                            <div style="border-top: 1px solid #000; display: inline-block; padding-top: 5px; min-width: 200px;">
                                Signature Bénéficiaire
                            </div>
                        </td>
                    </tr>
                </table>
            </div>';

        $this->generate($html, 'bon_sortie_' . $sortie_id . '.pdf');
    }
}
