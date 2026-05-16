<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole(['president_association']);
$db = getDB();
$uid = $_SESSION['user_id'];
$assoc = $db->prepare("SELECT * FROM association WHERE president_id=?"); $assoc->execute([$uid]); $assoc = $assoc->fetch();

$pageTitle = 'Espace Président d\'Association';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div class="breadcrumb"><a href="<?= BASE_URL ?>index.php">Accueil</a> / Mon Association</div>
    <h1><i class="fas fa-building"></i> <?= $assoc ? htmlspecialchars($assoc['nom']) : 'Mon Association' ?></h1>
    <?php if ($assoc): ?><p>Statut : <span class="badge badge-<?= $assoc['statut']==='active'?'success':'warning' ?>"><?= ucfirst($assoc['statut']) ?></span></p><?php endif; ?>
</div>
<div class="container py-20">
    <?php if (!$assoc): ?>
    <div class="empty-state card py-50">
        <i class="fas fa-building icon-primary-text" style="opacity:0.3;"></i>
        <h3>Vous n'avez pas encore d'association</h3>
        <p>Créez votre association pour commencer à organiser des activités humanitaires.</p>
        <a href="<?= BASE_URL ?>associations/create.php" class="btn btn-primary mt-20"><i class="fas fa-plus"></i> Créer une association</a>
    </div>
    <?php elseif ($assoc['statut'] === 'en_attente'): ?>
    <div class="alert alert-warning"><i class="fas fa-clock"></i> Votre association est en attente de validation par un administrateur. Vous serez notifié dès qu'elle sera approuvée.</div>
    <?php elseif ($assoc['statut'] === 'rejetee'): ?>
    <div class="alert alert-danger"><i class="fas fa-times-circle"></i> Votre association a été rejetée. Veuillez contacter l'administrateur pour plus d'informations.</div>
    <?php else: ?>
    <?php
    $sid = $assoc['id'];
    $nbSieges = $db->prepare("SELECT COUNT(*) FROM siege WHERE association_id=?"); $nbSieges->execute([$sid]); $nbSieges = $nbSieges->fetchColumn();
    $nbMembres = $db->prepare("SELECT COUNT(*) FROM membre_association WHERE association_id=? AND statut='actif'"); $nbMembres->execute([$sid]); $nbMembres = $nbMembres->fetchColumn();
    $nbDons = $db->prepare("SELECT COUNT(*) FROM don d JOIN siege s ON d.siege_id=s.id WHERE s.association_id=?"); $nbDons->execute([$sid]); $nbDons = $nbDons->fetchColumn();
    $nbDemandes = $db->prepare("SELECT COUNT(*) FROM demande_aide da JOIN siege s ON da.siege_id=s.id WHERE s.association_id=?"); $nbDemandes->execute([$sid]); $nbDemandes = $nbDemandes->fetchColumn();
    ?>
    <div class="stats-grid mb-20">
        <div class="stat-card">
            <div class="stat-card-icon icon-primary-text"><i class="fas fa-map-marker-alt"></i></div>
            <div class="stat-card-info"><div class="num"><?= $nbSieges ?></div><div class="label">Sièges</div></div>
        </div>
        <div class="stat-card orange">
            <div class="stat-card-icon icon-secondary-text"><i class="fas fa-users"></i></div>
            <div class="stat-card-info"><div class="num"><?= $nbMembres ?></div><div class="label">Membres</div></div>
        </div>
        <div class="stat-card blue">
            <div class="stat-card-icon icon-info-text"><i class="fas fa-donate"></i></div>
            <div class="stat-card-info"><div class="num"><?= $nbDons ?></div><div class="label">Dons</div></div>
        </div>
        <div class="stat-card red">
            <div class="stat-card-icon icon-danger-text"><i class="fas fa-hand-holding-heart"></i></div>
            <div class="stat-card-info"><div class="num"><?= $nbDemandes ?></div><div class="label">Demandes d'aide</div></div>
        </div>
    </div>
    <div class="flex gap-10 mb-20" style="flex-wrap:wrap;">
        <a href="<?= BASE_URL ?>sieges/index.php" class="btn btn-primary"><i class="fas fa-map-marker-alt"></i> Gérer les sièges</a>
        <a href="<?= BASE_URL ?>president_association/membres.php" class="btn btn-outline"><i class="fas fa-users"></i> Voir les membres</a>
        <a href="<?= BASE_URL ?>sieges/create.php" class="btn btn-secondary"><i class="fas fa-plus"></i> Nouveau siège</a>
    </div>
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-info-circle icon-primary-text"></i> Informations de l'association</h3></div>
        <div class="card-body">
            <div class="two-col-grid">
                <div><strong>Nom :</strong><br><?= htmlspecialchars($assoc['nom']) ?></div>
                <div><strong>Créée le :</strong><br><?= date('d/m/Y', strtotime($assoc['date_creation'])) ?></div>
                <div style="grid-column:span 2;"><strong>Description :</strong><br><?= nl2br(htmlspecialchars($assoc['description'] ?? 'Aucune description.')) ?></div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
