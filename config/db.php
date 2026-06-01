<?php
// ============================================
// CONFIGURATION BASE DE DONNÉES
// ============================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'Root2025!,');
define('DB_NAME', 'takaful');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            die('<div class="alert alert-danger" style="margin:40px auto;max-width:800px;display:block;">
                <h3 class="mb-20"><i class="fas fa-exclamation-triangle"></i> Erreur de connexion</h3>
                <p>Impossible de se connecter à la base de données : <strong>' . htmlspecialchars($e->getMessage()) . '</strong></p>
                <p class="mt-20 text-light">Vérifiez que XAMPP est démarré et que la base de données <strong>' . DB_NAME . '</strong> existe dans phpMyAdmin.</p>
            </div>');
        }
        // Auto migration to ensure message and message_decision exist in membre_association
        try {
            $pdo->exec("ALTER TABLE membre_association ADD COLUMN IF NOT EXISTS message TEXT NULL AFTER document_path");
            $pdo->exec("ALTER TABLE membre_association ADD COLUMN IF NOT EXISTS message_decision TEXT NULL AFTER message");
            $pdo->exec("ALTER TABLE assignation ADD COLUMN IF NOT EXISTS president_assigne_id VARCHAR(36) NULL AFTER membre_association_id");
        } catch (Exception $e) {}
    }
    return $pdo;
}

function uuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}
