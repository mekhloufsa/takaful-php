<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
if (isAdmin()) { header('Location: ' . BASE_URL . 'admin/index.php'); exit; }
$pageTitle = 'Mon Tableau de bord';
$db = getDB();
$uid = $_SESSION['user_id'];
$role = getRole();

$mesDons = $db->prepare("SELECT COUNT(*) FROM don WHERE donateur_id=?"); $mesDons->execute([$uid]); $nbDons = $mesDons->fetchColumn();
$mesDemandes = $db->prepare("SELECT COUNT(*) FROM demande_aide WHERE demandeur_id=?"); $mesDemandes->execute([$uid]); $nbDemandes = $mesDemandes->fetchColumn();

$dons = $db->prepare("SELECT d.*, s.nom as siege_nom FROM don d LEFT JOIN siege s ON d.siege_id=s.id WHERE d.donateur_id=? ORDER BY d.date_don DESC LIMIT 5"); $dons->execute([$uid]); $dons = $dons->fetchAll();
$demandes = $db->prepare("SELECT da.*, s.nom as siege_nom FROM demande_aide da LEFT JOIN siege s ON da.siege_id=s.id WHERE da.demandeur_id=? ORDER BY da.date_demande DESC LIMIT 5"); $demandes->execute([$uid]); $demandes = $demandes->fetchAll();

$statusLabels = ['en_attente'=>'En attente','confirme'=>'Confirmé','collecte'=>'Collecté','annule'=>'Annulé','soumise'=>'Soumise','en_cours'=>'En cours','acceptee'=>'Acceptée','refusee'=>'Refusée','resolue'=>'Résolue'];
$statusBadge = ['en_attente'=>'warning','confirme'=>'success','collecte'=>'success','annule'=>'danger','soumise'=>'info','en_cours'=>'warning','acceptee'=>'success','refusee'=>'danger','resolue'=>'success'];

include __DIR__ . '/includes/header.php';
?>
<div class="page-header">
    <div class="breadcrumb"><a href="<?= BASE_URL ?>index.php">Accueil</a> / Tableau de bord</div>
    <h1><i class="fas fa-tachometer-alt"></i> Mon Tableau de bord</h1>
    <p>Bonjour <?= htmlspecialchars($_SESSION['user']['prenom'] ?? '') ?> — <?= ucfirst(str_replace('_', ' ', $role)) ?></p>
</div>

<div class="container py-20">

    <?php if ($role === 'president_association'): ?>
    <div class="alert alert-info"><i class="fas fa-info-circle"></i> En tant que Président d'Association, accédez à votre <a href="<?= BASE_URL ?>president_association/dashboard.php" style="font-weight:700;">espace de gestion</a>.</div>
    <?php elseif ($role === 'president_siege'): ?>
    <div class="alert alert-info"><i class="fas fa-info-circle"></i> En tant que Président de Siège, accédez à votre <a href="<?= BASE_URL ?>president_siege/dashboard.php" style="font-weight:700;">espace de gestion du siège</a>.</div>
    <?php elseif ($role === 'membre_association'): ?>
    <div class="alert alert-info"><i class="fas fa-info-circle"></i> En tant que Membre d'Association, consultez vos <a href="<?= BASE_URL ?>missions/index.php" style="font-weight:700;">missions assignées</a>.</div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-card-icon icon-primary-text"><i class="fas fa-donate"></i></div>
            <div class="stat-card-info"><div class="num"><?= $nbDons ?></div><div class="label">Mes dons</div></div>
        </div>
        <div class="stat-card orange">
            <div class="stat-card-icon icon-secondary-text"><i class="fas fa-hand-holding-heart"></i></div>
            <div class="stat-card-info"><div class="num"><?= $nbDemandes ?></div><div class="label">Mes demandes d'aide</div></div>
        </div>
    </div>

    <div class="two-col-grid mt-10">

        <div class="table-wrapper">
            <div class="py-20 px-20 border-bottom flex justify-between items-center" style="padding-left:20px;padding-right:20px;">
                <h3 class="font-bold" style="font-size:1rem;"><i class="fas fa-donate icon-primary-text"></i> Mes derniers dons</h3>
                <a href="<?= BASE_URL ?>dons/index.php" class="btn btn-outline btn-sm">Voir tout</a>
            </div>
            <?php if ($dons): ?>
            <table>
                <thead><tr><th>Type</th><th>Catégorie</th><th>Montant</th><th>Statut</th></tr></thead>
                <tbody>
                <?php foreach ($dons as $d): ?>
                <tr>
                    <td><i class="fas fa-<?= $d['type']==='financier' ? 'money-bill' : 'box' ?>"></i> <?= ucfirst($d['type']) ?></td>
                    <td><?= htmlspecialchars($d['categorie'] ?? '—') ?></td>
                    <td><?= $d['montant'] > 0 ? number_format($d['montant'],2).' DA' : '—' ?></td>
                    <td><span class="badge badge-<?= $statusBadge[$d['statut']] ?? 'secondary' ?>"><?= $statusLabels[$d['statut']] ?? $d['statut'] ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state"><i class="fas fa-donate"></i><h3>Aucun don</h3><p><a href="<?= BASE_URL ?>dons/create.php">Faire un don maintenant</a></p></div>
            <?php endif; ?>
        </div>

        <div class="table-wrapper">
            <div class="py-20 px-20 border-bottom flex justify-between items-center" style="padding-left:20px;padding-right:20px;">
                <h3 class="font-bold" style="font-size:1rem;"><i class="fas fa-hand-holding-heart icon-secondary-text"></i> Mes demandes d'aide</h3>
                <a href="<?= BASE_URL ?>demandes/index.php" class="btn btn-outline btn-sm">Voir tout</a>
            </div>
            <?php if ($demandes): ?>
            <table>
                <thead><tr><th>Sujet</th><th>Type</th><th>Statut</th></tr></thead>
                <tbody>
                <?php foreach ($demandes as $d): ?>
                <tr>
                    <td><?= htmlspecialchars(substr($d['sujet'],0,30)) ?><?= strlen($d['sujet'])>30?'...':'' ?></td>
                    <td><?= ucfirst($d['type_aide']) ?></td>
                    <td><span class="badge badge-<?= $statusBadge[$d['statut']] ?? 'secondary' ?>"><?= $statusLabels[$d['statut']] ?? $d['statut'] ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state"><i class="fas fa-hand-holding-heart"></i><h3>Aucune demande</h3><p><a href="<?= BASE_URL ?>demandes/create.php">Soumettre une demande</a></p></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="flex gap-10 mt-20" style="flex-wrap:wrap;">
        <a href="<?= BASE_URL ?>dons/create.php" class="btn btn-primary"><i class="fas fa-donate"></i> Faire un don</a>
        <a href="<?= BASE_URL ?>demandes/create.php" class="btn btn-secondary"><i class="fas fa-hand-holding-heart"></i> Demande d'aide</a>
        <a href="<?= BASE_URL ?>associations/index.php" class="btn btn-outline"><i class="fas fa-building"></i> Associations</a>
        <a href="<?= BASE_URL ?>profil.php" class="btn btn-outline"><i class="fas fa-user"></i> Mon profil</a>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
