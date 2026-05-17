<?php
require_once __DIR__ . '/config/db.php';
$db = getDB();

try {
    $db->exec("ALTER TABLE membre_association ADD COLUMN IF NOT EXISTS message TEXT NULL AFTER document_path");
    echo "Added message column to membre_association successfully.<br>";
} catch(Exception $e) {
    echo "Message column error: " . $e->getMessage() . "<br>";
}

try {
    $db->exec("ALTER TABLE membre_association ADD COLUMN IF NOT EXISTS message_decision TEXT NULL AFTER message");
    echo "Added message_decision column to membre_association successfully.<br>";
} catch(Exception $e) {
    echo "Message_decision column error: " . $e->getMessage() . "<br>";
}

echo "Migration completed.";
