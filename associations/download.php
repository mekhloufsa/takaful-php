<?php
/**
 * Téléchargement sécurisé des documents d'association.
 * Accessible uniquement aux administrateurs.
 */
require_once __DIR__ . '/../includes/auth.php';

if (!isAdmin()) { http_response_code(403); exit('Accès réservé aux administrateurs.'); }

$db  = getDB();
$aid = $_GET['id'] ?? '';

if (empty($aid)) { http_response_code(400); exit('Paramètre manquant.'); }

$stmt = $db->prepare("SELECT piece_jointe FROM association WHERE id=?");
$stmt->execute([$aid]);
$assoc = $stmt->fetch();

if (!$assoc || empty($assoc['piece_jointe'])) {
    http_response_code(404); exit('Document introuvable.');
}

$filePath = __DIR__ . '/../uploads/associations/' . basename($assoc['piece_jointe']);
if (!file_exists($filePath)) { http_response_code(404); exit('Fichier non trouvé sur le serveur.'); }

$ext   = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
$mimes = [
    'pdf'  => 'application/pdf',
    'jpg'  => 'image/jpeg', 'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
];
$mime = $mimes[$ext] ?? 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="document_association_' . substr($aid,0,8) . '.' . $ext . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private');
readfile($filePath);
exit;
