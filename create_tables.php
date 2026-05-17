<?php
require_once __DIR__ . '/config/db.php';
$db = getDB();

$queries = [
    "CREATE TABLE IF NOT EXISTS mission (
        id VARCHAR(36) PRIMARY KEY,
        siege_id VARCHAR(36) NOT NULL,
        titre VARCHAR(255) NOT NULL,
        description TEXT,
        type ENUM('distribution','transport','sensibilisation','autre') DEFAULT 'autre',
        date_mission DATETIME,
        statut ENUM('planifiee','en_cours','terminee','annulee') DEFAULT 'planifiee',
        date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (siege_id) REFERENCES siege(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS assignation (
        id VARCHAR(36) PRIMARY KEY,
        membre_association_id VARCHAR(36),
        mission_id VARCHAR(36),
        demande_id VARCHAR(36),
        don_id VARCHAR(36),
        date_assignation DATE DEFAULT (CURRENT_DATE),
        statut ENUM('assignee','en_cours','terminee','annulee') DEFAULT 'assignee',
        note TEXT,
        date_rendezvous DATETIME NULL,
        note_rendezvous TEXT NULL,
        message_non_resolu TEXT NULL,
        FOREIGN KEY (membre_association_id) REFERENCES membre_association(id) ON DELETE SET NULL,
        FOREIGN KEY (mission_id) REFERENCES mission(id) ON DELETE SET NULL,
        FOREIGN KEY (demande_id) REFERENCES demande_aide(id) ON DELETE SET NULL,
        FOREIGN KEY (don_id) REFERENCES don(id) ON DELETE SET NULL
    )"
];

foreach ($queries as $q) {
    try {
        $db->exec($q);
        echo "OK<br>";
    } catch(Exception $e) {
        echo "ERREUR: " . $e->getMessage() . "<br>";
    }
}
echo "Tables créées.";
