<?php
// API JSON — retourne les sièges actifs d'une association
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');

$association_id = $_GET['association_id'] ?? '';
if (!$association_id) { echo json_encode([]); exit; }

$db   = getDB();
$stmt = $db->prepare("SELECT id, nom, wilaya FROM siege WHERE association_id=? AND statut='actif' ORDER BY wilaya, nom");
$stmt->execute([$association_id]);
echo json_encode($stmt->fetchAll());
