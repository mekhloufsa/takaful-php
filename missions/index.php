<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole(['membre_association','president_siege']);
$db = getDB();
$uid = $_SESSION['user_id'];

if (getRole() === 'president_siege') {
    $siege = $db->prepare("SELECT * FROM siege WHERE president_siege_id=?"); $siege->execute([$uid]); $siege = $siege->fetch();
    if (!$siege) { flash('Siège introuvable.', 'error'); header('Location: ' . BASE_URL . 'dashboard.php'); exit; }
    $missions = $db->prepare("SELECT * FROM mission WHERE siege_id=? ORDER BY date_mission DESC, date_creation DESC"); $missions->execute([$siege['id']]); $missions = $missions->fetchAll();
} else {
    $ma = $db->prepare("SELECT ma.* FROM membre_association ma WHERE ma.membre_id=? AND ma.statut='actif'"); $ma->execute([$uid]); $ma = $ma->fetch();
    if (!$ma) { flash('Vous n\'êtes pas membre d\'une association.', 'error'); header('Location: ' . BASE_URL . 'dashboard.php'); exit; }
    $missions = $db->prepare("SELECT m.* FROM mission m JOIN assignation a ON a.mission_id=m.id WHERE a.membre_association_id=? ORDER BY m.date_mission DESC"); $missions->execute([$ma['id']]); $missions = $missions->fetchAll();
}

$statusLabels = ['planifiee'=>'Planifiée','en_cours'=>'En cours','terminee'=>'Terminée','annulee'=>'Annulée'];
$statusBadge = ['planifiee'=>'info','en_cours'=>'warning','terminee'=>'success','annulee'=>'danger'];
$pageTitle = 'Mes Missions';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div class="breadcrumb"><a href="<?= BASE_URL ?>dashboard.php">Tableau de bord</a> / Missions</div>
    <h1><i class="fas fa-tasks"></i> <?= getRole()==='president_siege'?'Gestion des Missions':'Mes Missions' ?></h1>
</div>
<div class="container" style="padding:30px 20px;">
    <?php if (getRole() === 'president_siege'): ?>
    <div style="display:flex;justify-content:flex-end;margin-bottom:20px;">
        <a href="<?= BASE_URL ?>president_siege/missions.php" class="btn btn-primary"><i class="fas fa-cog"></i> Gérer les missions</a>
    </div>
    <?php endif; ?>
    <?php if ($missions): ?>
    <div class="cards-grid">
        <?php foreach ($missions as $m): ?>
        <div class="card">
            <div class="card-header">
                <div><h3 style="font-size:1rem;font-weight:800;"><?= htmlspecialchars($m['titre']) ?></h3><span class="badge badge-<?= $statusBadge[$m['statut']]??'secondary' ?>"><?= $statusLabels[$m['statut']]??$m['statut'] ?></span></div>
            </div>
            <div class="card-body">
                <p style="color:var(--text-light);font-size:0.88rem;margin-bottom:12px;"><?= htmlspecialchars(substr($m['description']??'',0,100)) ?></p>
                <p><i class="fas fa-calendar" style="color:var(--primary);"></i> <?= $m['date_mission']?date('d/m/Y H:i',strtotime($m['date_mission'])):'Date à définir' ?></p>
                <p style="margin-top:6px;"><i class="fas fa-tag" style="color:var(--primary);"></i> <?= ucfirst($m['type']) ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state card" style="padding:60px;"><i class="fas fa-tasks" style="opacity:0.3;color:var(--primary);"></i><h3>Aucune mission</h3><p>Vous n'avez aucune mission assignée pour le moment.</p></div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
