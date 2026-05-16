<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$db = getDB();
$uid = $_SESSION['user_id'];
$id = $_GET['id'] ?? '';
if (isAdmin()) {
    $siege = $db->prepare("SELECT * FROM siege WHERE id=?"); $siege->execute([$id]); $siege = $siege->fetch();
} else {
    requireRole(['president_association']);
    $siege = $db->prepare("SELECT s.* FROM siege s JOIN association a ON s.association_id=a.id WHERE s.id=? AND a.president_id=?"); $siege->execute([$id,$uid]); $siege = $siege->fetch();
}
if (!$siege) { flash('Siège introuvable.', 'error'); header('Location: ' . BASE_URL . 'dashboard.php'); exit; }
$db->prepare("DELETE FROM siege WHERE id=?")->execute([$id]);
flash('Siège supprimé.', 'success');
header('Location: ' . (isAdmin() ? BASE_URL.'admin/associations.php' : BASE_URL.'sieges/index.php')); exit;
