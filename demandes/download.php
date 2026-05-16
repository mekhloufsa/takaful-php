<?php
/**
 * Téléchargement sécurisé des pièces jointes des demandes d'aide.
 * Accessible uniquement :
 *  - Au demandeur lui-même
 *  - Au président du siège concerné
 *  - Aux membres assignés à cette demande
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$db  = getDB();
$uid = $_SESSION['user_id'];
$did = $_GET['id'] ?? '';

if (empty($did)) { http_response_code(400); exit('Paramètre manquant.'); }

// Récupérer la demande
$stmt = $db->prepare("SELECT da.*, s.president_siege_id FROM demande_aide da LEFT JOIN siege s ON da.siege_id=s.id WHERE da.id=?");
$stmt->execute([$did]);
$demande = $stmt->fetch();

if (!$demande || empty($demande['piece_jointe'])) {
    http_response_code(404); exit('Fichier introuvable.');
}

// Vérifier les droits d'accès
$autorise = false;

// 1. Le demandeur
if ($demande['demandeur_id'] === $uid) $autorise = true;

// 2. Le président du siège
if ($demande['president_siege_id'] === $uid) $autorise = true;

// 3. Admin
if (isAdmin()) $autorise = true;

// 4. Membre assigné à cette demande
if (!$autorise) {
    $stmtA = $db->prepare("
        SELECT a.id FROM assignation a
        JOIN membre_association ma ON a.membre_association_id = ma.id
        WHERE a.demande_id=? AND ma.membre_id=? AND a.statut IN('assignee','en_cours')
    ");
    $stmtA->execute([$did, $uid]);
    if ($stmtA->fetch()) $autorise = true;
}

if (!$autorise) { http_response_code(403); exit('Accès refusé.'); }

$filePath = __DIR__ . '/../uploads/demandes/' . basename($demande['piece_jointe']);

if (!file_exists($filePath)) { http_response_code(404); exit('Fichier non trouvé sur le serveur.'); }

// Déterminer le type MIME
$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
$mimes = [
    'pdf'  => 'application/pdf',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
];
$mime = $mimes[$ext] ?? 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="piece_jointe_' . substr($did, 0, 8) . '.' . $ext . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private');
readfile($filePath);
exit;
