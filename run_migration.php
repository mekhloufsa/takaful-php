<?php
require_once __DIR__ . '/config/db.php';
$db = getDB();

try {
    $db->exec("ALTER TABLE membre_association ADD COLUMN siege_id VARCHAR(36) NULL AFTER association_id");
} catch(Exception $e) {}

try {
    $db->exec("ALTER TABLE membre_association MODIFY COLUMN statut ENUM('en_attente','actif','inactif') DEFAULT 'en_attente'");
} catch(Exception $e) {}

echo "Migration done.";
