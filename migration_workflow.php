<?php
require_once __DIR__ . '/config/db.php';
$db = getDB();

$queries = [
    "ALTER TABLE candidature_siege ADD COLUMN document_path VARCHAR(500) NULL AFTER message",
    "ALTER TABLE membre_association ADD COLUMN document_path VARCHAR(500) NULL AFTER statut",
    "ALTER TABLE don ADD COLUMN message_decision TEXT NULL AFTER statut",
    "ALTER TABLE demande_aide ADD COLUMN message_decision TEXT NULL AFTER statut",
    "ALTER TABLE assignation ADD COLUMN date_rendezvous DATETIME NULL AFTER note",
    "ALTER TABLE assignation ADD COLUMN note_rendezvous TEXT NULL AFTER date_rendezvous",
    "ALTER TABLE assignation ADD COLUMN message_non_resolu TEXT NULL AFTER note_rendezvous"
];

foreach ($queries as $q) {
    try {
        $db->exec($q);
        echo "OK: $q<br>";
    } catch(Exception $e) {
        echo "ERREUR ou déjà existant: " . $e->getMessage() . "<br>";
    }
}
echo "Migration terminée.";
