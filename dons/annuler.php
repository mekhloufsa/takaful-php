<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$db = getDB();
$uid = $_SESSION['user_id'];
$id = $_GET['id'] ?? '';
$stmt = $db->prepare("SELECT * FROM don WHERE id=? AND donateur_id=? AND statut='en_attente'");
$stmt->execute([$id, $uid]); $don = $stmt->fetch();
if (!$don) { flash('Impossible d\'annuler ce don.', 'error'); header('Location: ' . BASE_URL . 'dons/index.php'); exit; }
$db->prepare("UPDATE don SET statut='annule' WHERE id=?")->execute([$id]);
flash('Don annulé.', 'success');
header('Location: ' . BASE_URL . 'dons/index.php'); exit;
