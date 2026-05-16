<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$pageTitle = 'Administration';
$db = getDB();

$nbMembres = $db->query("SELECT COUNT(*) FROM membre")->fetchColumn();
$nbAssociations = $db->query("SELECT COUNT(*) FROM association")->fetchColumn();
$nbAssocEnAttente = $db->query("SELECT COUNT(*) FROM association WHERE statut='en_attente'")->fetchColumn();
$nbSieges = $db->query("SELECT COUNT(*) FROM siege")->fetchColumn();
$nbDons = $db->query("SELECT COUNT(*) FROM don")->fetchColumn();
$nbDemandes = $db->query("SELECT COUNT(*) FROM demande_aide")->fetchColumn();
$nbAnnonces = $db->query("SELECT COUNT(*) FROM annonce WHERE (date_fin IS NULL OR date_fin >= NOW())")->fetchColumn();

$recentMembres = $db->query("SELECT * FROM membre ORDER BY date_inscription DESC LIMIT 5")->fetchAll();
$recentAssocs = $db->query("SELECT a.*, m.nom AS pnom, m.prenom AS ppren FROM association a LEFT JOIN membre m ON a.president_id=m.id ORDER BY a.date_creation DESC LIMIT 5")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1><i class="fas fa-cog"></i> Tableau de bord — Administration</h1>
    <p>Gérez la plateforme Takaful</p>
</div>
<div class="container py-20">

    <?php if ($nbAssocEnAttente > 0): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        <strong><?= $nbAssocEnAttente ?> association(s)</strong> en attente de validation.
        <a href="<?= BASE_URL ?>admin/associations.php?statut=en_attente" style="font-weight:700;">Valider maintenant</a>
    </div>
    <?php endif; ?>

    <div class="stats-grid mb-20">
        <div class="stat-card">
            <div class="stat-card-icon icon-primary-text"><i class="fas fa-users"></i></div>
            <div class="stat-card-info"><div class="num"><?= $nbMembres ?></div><div class="label">Membres inscrits</div></div>
        </div>
        <div class="stat-card orange">
            <div class="stat-card-icon icon-secondary-text"><i class="fas fa-building"></i></div>
            <div class="stat-card-info"><div class="num"><?= $nbAssociations ?></div><div class="label">Associations <small style="font-size:0.7rem;"><?= $nbAssocEnAttente ?> en attente</small></div></div>
        </div>
        <div class="stat-card blue">
            <div class="stat-card-icon icon-info-text"><i class="fas fa-map-marker-alt"></i></div>
            <div class="stat-card-info"><div class="num"><?= $nbSieges ?></div><div class="label">Sièges</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-card-icon icon-primary-text"><i class="fas fa-donate"></i></div>
            <div class="stat-card-info"><div class="num"><?= $nbDons ?></div><div class="label">Dons</div></div>
        </div>
        <div class="stat-card red">
            <div class="stat-card-icon icon-danger-text"><i class="fas fa-hand-holding-heart"></i></div>
            <div class="stat-card-info"><div class="num"><?= $nbDemandes ?></div><div class="label">Demandes d'aide</div></div>
        </div>
        <div class="stat-card orange">
            <div class="stat-card-icon icon-secondary-text"><i class="fas fa-bullhorn"></i></div>
            <div class="stat-card-info"><div class="num"><?= $nbAnnonces ?></div><div class="label">Annonces actives</div></div>
        </div>
    </div>

    <div class="flex gap-10 mb-20" style="flex-wrap:wrap;">
        <a href="<?= BASE_URL ?>admin/users.php" class="btn btn-primary"><i class="fas fa-users"></i> Gérer les utilisateurs</a>
        <a href="<?= BASE_URL ?>admin/associations.php" class="btn btn-secondary"><i class="fas fa-building"></i> Gérer les associations</a>
        <a href="<?= BASE_URL ?>admin/annonces.php" class="btn btn-outline"><i class="fas fa-bullhorn"></i> Annonces</a>
    </div>

    <div class="two-col-grid">
        <div class="table-wrapper">
            <div class="py-20 px-20 border-bottom flex justify-between items-center" style="padding-left:20px;padding-right:20px;">
                <h3 class="font-bold" style="font-size:1rem;"><i class="fas fa-users icon-primary-text"></i> Derniers inscrits</h3>
                <a href="<?= BASE_URL ?>admin/users.php" class="btn btn-outline btn-sm">Voir tout</a>
            </div>
            <table><thead><tr><th>Nom</th><th>Email</th><th>Rôle</th><th>Inscription</th></tr></thead><tbody>
            <?php foreach ($recentMembres as $m): ?>
            <tr><td><?= htmlspecialchars($m['nom'].' '.$m['prenom']) ?></td><td><?= htmlspecialchars($m['email']) ?></td><td><?= ucfirst(str_replace('_',' ',$m['role'])) ?></td><td><?= date('d/m/Y', strtotime($m['date_inscription'])) ?></td></tr>
            <?php endforeach; ?>
            </tbody></table>
        </div>
        <div class="table-wrapper">
            <div class="py-20 px-20 border-bottom flex justify-between items-center" style="padding-left:20px;padding-right:20px;">
                <h3 class="font-bold" style="font-size:1rem;"><i class="fas fa-building icon-secondary-text"></i> Dernières associations</h3>
                <a href="<?= BASE_URL ?>admin/associations.php" class="btn btn-outline btn-sm">Voir tout</a>
            </div>
            <table><thead><tr><th>Nom</th><th>Président</th><th>Statut</th></tr></thead><tbody>
            <?php foreach ($recentAssocs as $a): ?>
            <tr>
                <td><?= htmlspecialchars($a['nom']) ?></td>
                <td><?= $a['pnom'] ? htmlspecialchars($a['pnom'].' '.$a['ppren']) : '—' ?></td>
                <td><span class="badge badge-<?= ['en_attente'=>'warning','active'=>'success','suspendue'=>'secondary','rejetee'=>'danger'][$a['statut']]??'secondary' ?>"><?= ucfirst($a['statut']) ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody></table>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
