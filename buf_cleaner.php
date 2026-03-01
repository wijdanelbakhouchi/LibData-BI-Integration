<?php

function cleanSpecialChars($string) {
    // Convertir en UTF-8 si ce n’est pas le cas
    $string = mb_convert_encoding($string, 'UTF-8', 'ISO-8859-1');

    // Remplacer les caractères accentués par leur équivalent non-accentué
    $string = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $string);

    // Supprimer les caractères invisibles et espaces multiples
    $string = preg_replace('/[\x00-\x1F\x7F]/u', '', $string);
    $string = preg_replace('/\s+/', ' ', $string);

    return trim($string);
}

$inputFile = 'buf.csv';
$outputFile = 'cleaned_buf.csv';

// Ouvrir les fichiers
$input = fopen($inputFile, 'r');
if (!$input) {
    die("Impossible d'ouvrir le fichier source.\n");
}
$output = fopen($outputFile, 'w');
if (!$output) {
    die("Impossible de créer le fichier de sortie.\n");
}

// Nouvelle entête
$newHeader = ['Cote', 'Titre', 'Auteur', 'Lieu', 'Éditeur', 'Annee', 'Nb pages', 'Matiere', 'Inventaire'];
fputcsv($output, $newHeader);

// Sauter l'entête originale
fgetcsv($input);

$seenRows = [];
$inventaireCounter = 1;

while (($row = fgetcsv($input)) !== false) {
    // Nettoyage : trim + remplacement des valeurs manquantes
    $row = array_map('trim', $row);
    $row = array_map(function ($value, $index) {
        return ($value === '' || $value === null) ? ($index === 3 ? 'Unknown' : '') : $value;
    }, $row, array_keys($row));

    // Nettoyer chaque champ de caractères spéciaux
    $row = array_map('cleanSpecialChars', $row);

    // Supprimer les lignes incomplètes
    if (count(array_filter($row)) < 4) {
        continue;
    }

    // Identifier doublons
    $rowKey = md5(implode('|', $row));
    if (!isset($seenRows[$rowKey])) {
        $seenRows[$rowKey] = true;

        // Numéro d’inventaire unique
        $row[8] = $inventaireCounter++;
        fputcsv($output, $row);
    }
}

fclose($input);
fclose($output);

echo "Fichier nettoyé, doublons supprimés, caractères spéciaux normalisés. Résultat : cleaned_buf1.csv\n";
?>
