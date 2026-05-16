<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$db = getDB();
$id = $_GET['id'] ?? '';
$siege = $db->prepare("SELECT s.*, a.nom as assoc_nom, a.president_id as assoc_president, m.nom as ps_nom, m.prenom as ps_prenom, m.telephone as ps_tel FROM siege s JOIN association a ON s.association_id=a.id LEFT JOIN membre m ON s.president_siege_id=m.id WHERE s.id=?");
$siege->execute([$id]); $siege = $siege->fetch();
if (!$siege) { flash('Siège introuvable.', 'error'); header('Location: ' . BASE_URL . 'dashboard.php'); exit; }
$uid = $_SESSION['user_id'];
$canEdit = isAdmin() || $siege['assoc_president'] === $uid;

$nbDons = $db->prepare("SELECT COUNT(*) FROM don WHERE siege_id=?"); $nbDons->execute([$id]); $nbDons = $nbDons->fetchColumn();
$nbDemandes = $db->prepare("SELECT COUNT(*) FROM demande_aide WHERE siege_id=?"); $nbDemandes->execute([$id]); $nbDemandes = $nbDemandes->fetchColumn();
$nbMissions = $db->prepare("SELECT COUNT(*) FROM mission WHERE siege_id=?"); $nbMissions->execute([$id]); $nbMissions = $nbMissions->fetchColumn();
$recentDons = $db->prepare("SELECT d.*, m.nom, m.prenom FROM don d JOIN membre m ON d.donateur_id=m.id WHERE d.siege_id=? ORDER BY d.date_don DESC LIMIT 5"); $recentDons->execute([$id]); $recentDons = $recentDons->fetchAll();

$pageTitle = htmlspecialchars($siege['nom']);
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div class="breadcrumb"><a href="<?= BASE_URL ?>index.php">Accueil</a> / <a href="<?= BASE_URL ?>associations/index.php">Associations</a> / <?= htmlspecialchars($siege['assoc_nom']) ?> / <?= htmlspecialchars($siege['nom']) ?></div>
    <h1><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($siege['nom']) ?></h1>
    <p><?= htmlspecialchars($siege['assoc_nom']) ?> — <?= htmlspecialchars($siege['wilaya']) ?></p>
</div>
<div class="container py-20">
    <div class="stats-grid mb-20">
        <div class="stat-card">
            <div class="stat-card-icon icon-primary-text"><i class="fas fa-donate"></i></div>
            <div class="stat-card-info"><div class="num"><?= $nbDons ?></div><div class="label">Dons reçus</div></div>
        </div>
        <div class="stat-card orange">
            <div class="stat-card-icon icon-secondary-text"><i class="fas fa-hand-holding-heart"></i></div>
            <div class="stat-card-info"><div class="num"><?= $nbDemandes ?></div><div class="label">Demandes d'aide</div></div>
        </div>
        <div class="stat-card blue">
            <div class="stat-card-icon icon-info-text"><i class="fas fa-tasks"></i></div>
            <div class="stat-card-info"><div class="num"><?= $nbMissions ?></div><div class="label">Missions</div></div>
        </div>
    </div>
    <div class="two-col-grid" style="grid-template-columns: 2fr 1fr;">
        <div>
            <div class="table-wrapper">
                <div class="py-20 px-20 border-bottom" style="padding-left:20px;padding-right:20px;">
                    <h3 class="font-bold"><i class="fas fa-donate icon-primary-text"></i> Derniers dons reçus</h3>
                </div>
                <?php if ($recentDons): ?>
                <table>
                    <thead><tr><th>Donateur</th><th>Type</th><th>Montant</th><th>Statut</th></tr></thead>
                    <tbody><?php foreach ($recentDons as $d): ?><tr><td><?= htmlspecialchars($d['nom'].' '.$d['prenom']) ?></td><td><?= ucfirst($d['type']) ?></td><td><?= $d['montant']>0?number_format($d['montant'],2).' DA':'—' ?></td><td><span class="badge badge-<?= ['en_attente'=>'warning','confirme'=>'success','collecte'=>'success','annule'=>'danger'][$d['statut']]??'secondary' ?>"><?= $d['statut'] ?></span></td></tr><?php endforeach; ?></tbody>
                </table>
                <?php else: ?><div class="empty-state"><i class="fas fa-donate"></i><h3>Aucun don pour ce siège</h3></div><?php endif; ?>
            </div>
        </div>
        <div>
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-info-circle icon-primary-text"></i> Informations</h3></div>
                <div class="card-body">
                    <p><strong>Wilaya :</strong> <?= htmlspecialchars($siege['wilaya']) ?></p>
                    <?php if ($siege['adresse']): ?><p><strong>Adresse :</strong> <?= htmlspecialchars($siege['adresse']) ?></p><?php endif; ?>
                    <p><strong>Statut :</strong> <span class="badge badge-<?= $siege['statut']==='actif'?'success':'secondary' ?>"><?= ucfirst($siege['statut']) ?></span></p>
                    <?php if ($siege['ps_nom']): ?>
                    <div class="mt-20 pt-20 border-top">
                        <p><strong>Président de siège :</strong></p>
                        <p><?= htmlspecialchars($siege['ps_nom'].' '.$siege['ps_prenom']) ?></p>
                        <?php if ($siege['ps_tel']): ?><p class="text-light" style="font-size:0.85rem;"><?= htmlspecialchars($siege['ps_tel']) ?></p><?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php if ($canEdit): ?>
                <div class="card-footer">
                    <a href="<?= BASE_URL ?>sieges/edit.php?id=<?= $siege['id'] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i> Modifier</a>
                    <a href="<?= BASE_URL ?>sieges/delete.php?id=<?= $siege['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirmDelete()"><i class="fas fa-trash"></i> Supprimer</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
