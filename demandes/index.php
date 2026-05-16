<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if (isAdmin()) { header('Location: ' . BASE_URL . 'admin/index.php'); exit; }
$pageTitle = 'Mes Demandes d\'aide';
$db = getDB();
$uid = $_SESSION['user_id'];
$filtre_statut = $_GET['statut'] ?? '';
$sql = "SELECT da.*, s.nom as siege_nom FROM demande_aide da LEFT JOIN siege s ON da.siege_id=s.id WHERE da.demandeur_id=?";
$params = [$uid];
if ($filtre_statut) { $sql .= " AND da.statut=?"; $params[] = $filtre_statut; }
$sql .= " ORDER BY da.date_demande DESC";
$stmt = $db->prepare($sql); $stmt->execute($params); $demandes = $stmt->fetchAll();
$statusLabels = ['soumise'=>'Soumise','en_cours'=>'En cours','acceptee'=>'Acceptée','refusee'=>'Refusée','resolue'=>'Résolue'];
$statusBadge = ['soumise'=>'info','en_cours'=>'warning','acceptee'=>'success','refusee'=>'danger','resolue'=>'success'];
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div class="breadcrumb"><a href="<?= BASE_URL ?>index.php">Accueil</a> / Demandes d'aide</div>
    <h1><i class="fas fa-hand-holding-heart"></i> Mes Demandes d'aide</h1>
    <p>Suivez vos demandes d'assistance</p>
</div>
<div class="container py-20">
    <div class="filters-bar">
        <form method="GET" class="flex gap-10 items-center" style="flex-wrap:wrap;width:100%;">
            <div class="form-group">
                <label>Statut</label>
                <select name="statut" class="form-control" onchange="this.form.submit()">
                    <option value="">Tous</option>
                    <?php foreach ($statusLabels as $k=>$v): ?><option value="<?= $k ?>" <?= $filtre_statut===$k?'selected':'' ?>><?= $v ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group flex items-center" style="margin-left:auto;">
                <a href="<?= BASE_URL ?>demandes/create.php" class="btn btn-secondary"><i class="fas fa-plus"></i> Nouvelle demande</a>
            </div>
        </form>
    </div>
    <?php if ($demandes): ?>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Date</th><th>Sujet</th><th>Type d'aide</th><th>Siège</th><th>Statut</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($demandes as $d): ?>
            <tr>
                <td><?= date('d/m/Y', strtotime($d['date_demande'])) ?></td>
                <td><strong><?= htmlspecialchars(substr($d['sujet'],0,50)) ?></strong></td>
                <td><?= ucfirst(str_replace('_',' ',$d['type_aide'])) ?></td>
                <td><?= $d['siege_nom'] ? htmlspecialchars($d['siege_nom']) : '—' ?></td>
                <td><span class="badge badge-<?= $statusBadge[$d['statut']] ?? 'secondary' ?>"><?= $statusLabels[$d['statut']] ?? $d['statut'] ?></span></td>
                <td><a href="<?= BASE_URL ?>demandes/track.php?id=<?= $d['id'] ?>" class="btn btn-outline btn-sm"><i class="fas fa-eye"></i> Détail</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state card py-50">
        <i class="fas fa-hand-holding-heart icon-secondary-text" style="opacity:0.4;"></i>
        <h3>Aucune demande trouvée</h3>
        <p>Vous n'avez pas encore soumis de demande d'aide.</p>
        <a href="<?= BASE_URL ?>demandes/create.php" class="btn btn-secondary mt-20"><i class="fas fa-plus"></i> Soumettre une demande</a>
    </div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
