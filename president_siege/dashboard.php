<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole(['president_siege']);
$db = getDB();
$uid = $_SESSION['user_id'];
$siege = $db->prepare("SELECT s.*, a.nom as assoc_nom FROM siege s JOIN association a ON s.association_id=a.id WHERE s.president_siege_id=?");
$siege->execute([$uid]); $siege = $siege->fetch();
if (!$siege) { flash('Aucun siège assigné à votre compte.', 'error'); header('Location: ' . BASE_URL . 'dashboard.php'); exit; }
$sid = $siege['id'];

$nbDons = $db->prepare("SELECT COUNT(*) FROM don WHERE siege_id=?"); $nbDons->execute([$sid]); $nbDons = $nbDons->fetchColumn();
$nbDonsAttente = $db->prepare("SELECT COUNT(*) FROM don WHERE siege_id=? AND statut='en_attente'"); $nbDonsAttente->execute([$sid]); $nbDonsAttente = $nbDonsAttente->fetchColumn();
$nbDemandes = $db->prepare("SELECT COUNT(*) FROM demande_aide WHERE siege_id=?"); $nbDemandes->execute([$sid]); $nbDemandes = $nbDemandes->fetchColumn();
$nbDemandesAttente = $db->prepare("SELECT COUNT(*) FROM demande_aide WHERE siege_id=? AND statut='soumise'"); $nbDemandesAttente->execute([$sid]); $nbDemandesAttente = $nbDemandesAttente->fetchColumn();
$nbMissions = $db->prepare("SELECT COUNT(*) FROM mission WHERE siege_id=?"); $nbMissions->execute([$sid]); $nbMissions = $nbMissions->fetchColumn();

$recentDons = $db->prepare("SELECT d.*, m.nom, m.prenom FROM don d JOIN membre m ON d.donateur_id=m.id WHERE d.siege_id=? ORDER BY d.date_don DESC LIMIT 5"); $recentDons->execute([$sid]); $recentDons = $recentDons->fetchAll();
$recentDemandes = $db->prepare("SELECT da.*, m.nom, m.prenom FROM demande_aide da JOIN membre m ON da.demandeur_id=m.id WHERE da.siege_id=? ORDER BY da.date_demande DESC LIMIT 5"); $recentDemandes->execute([$sid]); $recentDemandes = $recentDemandes->fetchAll();

$pageTitle = 'Espace Président de Siège';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div class="breadcrumb"><a href="<?= BASE_URL ?>index.php">Accueil</a> / Espace Président de Siège</div>
    <h1><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($siege['nom']) ?></h1>
    <p><?= htmlspecialchars($siege['assoc_nom']) ?> — <?= htmlspecialchars($siege['wilaya']) ?></p>
</div>
<div class="container py-20">
    <div class="stats-grid mb-20">
        <div class="stat-card">
            <div class="stat-card-icon icon-primary-text"><i class="fas fa-donate"></i></div>
            <div class="stat-card-info">
                <div class="num"><?= $nbDons ?></div>
                <div class="label">Total dons <?php if($nbDonsAttente): ?><span class="badge badge-warning"><?= $nbDonsAttente ?> en attente</span><?php endif; ?></div>
            </div>
        </div>
        <div class="stat-card orange">
            <div class="stat-card-icon icon-secondary-text"><i class="fas fa-hand-holding-heart"></i></div>
            <div class="stat-card-info">
                <div class="num"><?= $nbDemandes ?></div>
                <div class="label">Demandes d'aide <?php if($nbDemandesAttente): ?><span class="badge badge-warning"><?= $nbDemandesAttente ?> nouvelles</span><?php endif; ?></div>
            </div>
        </div>
        <div class="stat-card blue">
            <div class="stat-card-icon icon-info-text"><i class="fas fa-tasks"></i></div>
            <div class="stat-card-info"><div class="num"><?= $nbMissions ?></div><div class="label">Missions</div></div>
        </div>
    </div>

    <div class="flex gap-10 mb-20" style="flex-wrap:wrap;">
        <a href="<?= BASE_URL ?>president_siege/dons.php" class="btn btn-primary"><i class="fas fa-donate"></i> Gérer les dons</a>
        <a href="<?= BASE_URL ?>president_siege/demandes.php" class="btn btn-secondary"><i class="fas fa-hand-holding-heart"></i> Gérer les demandes</a>
        <a href="<?= BASE_URL ?>president_siege/missions.php" class="btn btn-outline"><i class="fas fa-tasks"></i> Missions</a>
    </div>

    <div class="two-col-grid">
        <div class="table-wrapper">
            <div class="py-20 px-20 border-bottom flex justify-between items-center" style="padding-left:20px;padding-right:20px;">
                <h3 class="font-bold" style="font-size:1rem;"><i class="fas fa-donate icon-primary-text"></i> Derniers dons</h3>
                <a href="<?= BASE_URL ?>president_siege/dons.php" class="btn btn-outline btn-sm">Voir tout</a>
            </div>
            <?php if ($recentDons): ?>
            <table><thead><tr><th>Donateur</th><th>Type</th><th>Montant</th><th>Statut</th></tr></thead><tbody>
            <?php foreach ($recentDons as $d): ?><tr><td><?= htmlspecialchars($d['nom'].' '.$d['prenom']) ?></td><td><?= ucfirst($d['type']) ?></td><td><?= $d['montant']>0?number_format($d['montant'],2).' DA':'—' ?></td><td><span class="badge badge-<?= ['en_attente'=>'warning','confirme'=>'success','collecte'=>'success','annule'=>'danger'][$d['statut']]??'secondary' ?>"><?= $d['statut'] ?></span></td></tr><?php endforeach; ?>
            </tbody></table>
            <?php else: ?><div class="empty-state"><i class="fas fa-donate"></i><h3>Aucun don</h3></div><?php endif; ?>
        </div>
        <div class="table-wrapper">
            <div class="py-20 px-20 border-bottom flex justify-between items-center" style="padding-left:20px;padding-right:20px;">
                <h3 class="font-bold" style="font-size:1rem;"><i class="fas fa-hand-holding-heart icon-secondary-text"></i> Dernières demandes</h3>
                <a href="<?= BASE_URL ?>president_siege/demandes.php" class="btn btn-outline btn-sm">Voir tout</a>
            </div>
            <?php if ($recentDemandes): ?>
            <table><thead><tr><th>Demandeur</th><th>Sujet</th><th>Statut</th></tr></thead><tbody>
            <?php foreach ($recentDemandes as $d): ?><tr><td><?= htmlspecialchars($d['nom'].' '.$d['prenom']) ?></td><td><?= htmlspecialchars(substr($d['sujet'],0,30)) ?></td><td><span class="badge badge-<?= ['soumise'=>'info','en_cours'=>'warning','acceptee'=>'success','refusee'=>'danger','resolue'=>'success'][$d['statut']]??'secondary' ?>"><?= $d['statut'] ?></span></td></tr><?php endforeach; ?>
            </tbody></table>
            <?php else: ?><div class="empty-state"><i class="fas fa-hand-holding-heart"></i><h3>Aucune demande</h3></div><?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
