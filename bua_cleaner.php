<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

$inputFile = 'bua.xls';
$spreadsheet = IOFactory::load($inputFile);
$sheet = $spreadsheet->getActiveSheet();

$highestRow = $sheet->getHighestDataRow();
$highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString(
    $sheet->getHighestDataColumn()
);

// Nettoyer les en-têtes
$headers = [];
for ($col = 1; $col <= $highestColumnIndex; $col++) {
    $header = trim($sheet->getCellByColumnAndRow($col, 1)->getValue());
    $headers[$col] = $header;
    $sheet->setCellValueByColumnAndRow($col, 1, $header);
}

// Créer un nouveau classeur pour le fichier nettoyé
$cleanedSheet = new Spreadsheet();
$newSheet = $cleanedSheet->getActiveSheet();
$newSheet->setTitle('Nettoyé');

// Écrire les entêtes
foreach ($headers as $col => $header) {
    $newSheet->setCellValueByColumnAndRow($col, 1, $header);
}

// Pour éviter les doublons
$seenRows = [];
$newRow = 2;
$inventaireCounter = 14098; // Départ pour les numéros d'inventaire uniques

// Champs utilisés pour identifier les doublons
$dedupFields = ['Titre', 'Auteur', 'Editeur'];

for ($row = 2; $row <= $highestRow; $row++) {
    $skipRow = false;
    $rowData = [];
    $rowKey = '';

    for ($col = 1; $col <= $highestColumnIndex; $col++) {
        $value = $sheet->getCellByColumnAndRow($col, $row)->getValue();
        $header = $headers[$col];

        // Nettoyage de base
        $cleaned = preg_replace('/^[\"\']+|[\"\']+$/u', '', trim((string)$value));
        $cleaned = preg_replace('/\s+/u', ' ', $cleaned);

        // Supprimer les lignes contenant "مكرر"
        if (mb_strpos($cleaned, 'مكرر') !== false) {
            $skipRow = true;
            break;
        }

        // Nettoyer 'Cote' des guillemets
        if ($header === 'Cote') {
            $cleaned = str_replace('"', '', $cleaned);
        }

        // Nettoyage spécifique d'auteur
        if ($header === 'Auteur') {
            $cleaned = preg_replace('/[.,]+/u', ',', $cleaned);
            $cleaned = preg_replace('/\s+,/', ',', $cleaned);
        }

        // Valeurs numériques
        if (in_array($header, ['Annee', 'Nb pages'])) {
            $cleaned = is_numeric($cleaned) ? (int)$cleaned : "N/A";
        }

        // Gestion des champs vides
        if ($cleaned === '' || $cleaned === null) {
            $cleaned = "N/A";
        }

        // Générer une valeur unique pour l'inventaire
        if ($header === 'Inventaire') {
            $cleaned = $inventaireCounter++;
        }

        $rowData[$col] = $cleaned;

        // Générer la clé de détection des doublons
        if (in_array($header, $dedupFields)) {
            $rowKey .= strtolower($cleaned) . '|';
        }
    }

    if ($skipRow) continue;

    // Éviter les doublons exacts
    if (isset($seenRows[$rowKey])) continue;
    $seenRows[$rowKey] = true;

    // Écriture dans la nouvelle feuille
    foreach ($rowData as $col => $value) {
        $newSheet->setCellValueByColumnAndRow($col, $newRow, $value);
    }
    $newRow++;
}

// Sauvegarde du fichier nettoyé
$outputFile = 'bua_cleaned.csv';
$writer = IOFactory::createWriter($cleanedSheet, 'Xlsx');
$writer->save($outputFile);

echo "Fichier nettoyé avec succès : $outputFile\n";
