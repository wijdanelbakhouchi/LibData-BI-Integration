<?php
set_time_limit(0);

// Connexion MySQL
$dbHost = 'localhost';
$dbName = 'pmb';
$dbUser = 'root';
$dbPass = '123456789';

// Fichiers CSV
$baseDir = __DIR__ . DIRECTORY_SEPARATOR;
$csvFiles = [
    $baseDir . 'bua_cleaned.csv',
    $baseDir . 'buf_cleaned.csv'
];

// Vérification des fichiers
foreach ($csvFiles as $file) {
    if (!file_exists($file)) {
        die("❌ Fichier introuvable : $file<br>");
    }
    echo "✔ Fichier trouvé : $file<br>";
}

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->beginTransaction();

    // Préparation des requêtes
    $stmtNotice = $pdo->prepare("
        INSERT INTO notices (
            typdoc, tit1, year, npages,
            n_gen, n_contenu, n_resume, lien,
            eformat, index_l, index_matieres,
            statut, create_date, update_date, signature,
            thumbnail_url, date_parution, niveau_biblio,
            niveau_hierar, origine_catalogage, prix,
            commentaire_gestion
        ) VALUES (
            'a', ?, ?, ?,
            '', '', '', '',
            '', '', '',
            1, NOW(), NOW(), '',
            '', CURDATE(), 'm',
            '0', 1, '',
            ''
        )
    ");

    $stmtExpl = $pdo->prepare("
        INSERT INTO exemplaires (
            expl_notice, expl_cb, expl_cote, expl_statut,
            expl_location, expl_note
        ) VALUES (?, ?, ?, 1, 1, '')
    ");
    $stmtCheckExpl = $pdo->prepare("SELECT COUNT(*) FROM exemplaires WHERE expl_cb = ?");

    $stmtAuthor     = $pdo->prepare("INSERT INTO authors (author_name) VALUES (?) ON DUPLICATE KEY UPDATE author_name = VALUES(author_name)");
    $stmtFindAuthor = $pdo->prepare("SELECT author_id FROM authors WHERE author_name = ?");

    $stmtPublisher     = $pdo->prepare("INSERT INTO publishers (ed_name, ed_ville) VALUES (?, ?) ON DUPLICATE KEY UPDATE ed_name = VALUES(ed_name), ed_ville = VALUES(ed_ville)");
    $stmtFindPublisher = $pdo->prepare("SELECT ed_id FROM publishers WHERE ed_name = ?");
    $stmtUpdatePublisherId = $pdo->prepare("UPDATE notices SET ed1_id = ? WHERE notice_id = ?");

    $stmtLinkAuthor = $pdo->prepare("INSERT INTO responsability (responsability_notice, responsability_author, responsability_fonction, responsability_type) VALUES (?, ?, '0010', 0)");

    $stmtFindCategoryByLabel = $pdo->prepare("SELECT num_noeud FROM categories WHERE libelle_categorie = ?");
    $stmtGetMaxNoeud = $pdo->prepare("SELECT COALESCE(MAX(num_noeud), 0) FROM categories WHERE langue = 'fr_FR'");
    $stmtInsertCategory = $pdo->prepare("
        INSERT INTO categories (
            num_noeud, libelle_categorie, langue, num_thesaurus,
            note_application, comment_public, comment_voir,
            index_categorie, path_word_categ, index_path_word_categ
        ) VALUES (?, ?, 'fr_FR', 1, '', '', '', '', '', '')
    ");
    $stmtLinkCategory = $pdo->prepare("
        INSERT INTO notices_categories (
            notcateg_notice, num_noeud, num_vedette, ordre_vedette, ordre_categorie
        ) VALUES (?, ?, 0, 1, 0)
    ");

    // Lecture de chaque fichier CSV
    foreach ($csvFiles as $csvFile) {
        $handle = fopen($csvFile, 'r');
        fgetcsv($handle, 1000, ","); // Ignore l'en-tête

        while (($data = fgetcsv($handle, 1000, ",")) !== false) {
            if (count($data) < 9) continue;
            list($cote, $titre, $auteur, $lieu, $edition, $annee, $nbPages, $matiere, $inventaire) = $data;

            // 1️⃣ Insertion de la notice
            $stmtNotice->execute([$titre, $annee, $nbPages]);
            $noticeId = $pdo->lastInsertId();

            // 2️⃣ Insertion exemplaire (si inexistant)
            $stmtCheckExpl->execute([$inventaire]);
            if ($stmtCheckExpl->fetchColumn() == 0) {
                $stmtExpl->execute([$noticeId, $inventaire, $cote]);
            }

            // 3️⃣ Insertion des auteurs
            foreach (explode(';', $auteur) as $authorName) {
                $authorName = trim($authorName);
                if (!$authorName) continue;
                $stmtAuthor->execute([$authorName]);
                $authorId = $pdo->lastInsertId() ?: (
                    $stmtFindAuthor->execute([$authorName]) && $stmtFindAuthor->fetchColumn()
                );
                if ($authorId) {
                    $stmtLinkAuthor->execute([$noticeId, $authorId]);
                }
            }

            // 4️⃣ Insertion de l'éditeur
            if (trim($edition) !== '') {
                $stmtPublisher->execute([$edition, $lieu]);
                $publisherId = $pdo->lastInsertId() ?: (
                    $stmtFindPublisher->execute([$edition]) && $stmtFindPublisher->fetchColumn()
                );
                if ($publisherId) {
                    $stmtUpdatePublisherId->execute([$publisherId, $noticeId]);
                }
            }

            // 5️⃣ Insertion matière / catégorie
            if (trim($matiere) !== '') {
                $matiere = trim(str_replace("\xc2\xa0", ' ', $matiere));
                $stmtFindCategoryByLabel->execute([$matiere]);
                $categoryId = $stmtFindCategoryByLabel->fetchColumn();

                if (!$categoryId) {
                    $stmtGetMaxNoeud->execute();
                    $newNoeud = $stmtGetMaxNoeud->fetchColumn() + 1;
                    $stmtInsertCategory->execute([$newNoeud, $matiere]);
                    $categoryId = $newNoeud;
                }

                $stmtLinkCategory->execute([$noticeId, $categoryId]);
            }
        }

        fclose($handle);
    }

    $pdo->commit();
    echo "<br>✅ Import terminé avec succès à " . date('Y-m-d H:i:s');

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("❌ Erreur critique lors de l'import : " . $e->getMessage());
}
?>