<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole(['president_siege']);
$db = getDB();
$uid = $_SESSION['user_id'];
$siege = $db->prepare("SELECT * FROM siege WHERE president_siege_id=?"); $siege->execute([$uid]); $siege = $siege->fetch();
if (!$siege) { flash('Siège introuvable.', 'error'); header('Location: ' . BASE_URL . 'dashboard.php'); exit; }
$sid = $siege['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dem_id = $_POST['dem_id'] ?? '';
    $action = $_POST['action'] ?? '';
    $validActions = ['en_cours','acceptee','refusee','resolue'];
    if ($dem_id && in_array($action, $validActions)) {
        $dem = $db->prepare("SELECT * FROM demande_aide WHERE id=? AND siege_id=?"); $dem->execute([$dem_id,$sid]); $dem = $dem->fetch();
        if ($dem) { $db->prepare("UPDATE demande_aide SET statut=? WHERE id=?")->execute([$action,$dem_id]); flash('Statut mis à jour.', 'success'); }
    }
    header('Location: ' . BASE_URL . 'president_siege/demandes.php'); exit;
}

$filtre = $_GET['statut'] ?? '';
$sql = "SELECT da.*, m.nom, m.prenom, m.telephone, m.wilaya as m_wilaya FROM demande_aide da JOIN membre m ON da.demandeur_id=m.id WHERE da.siege_id=?";
$params = [$sid];
if ($filtre) { $sql .= " AND da.statut=?"; $params[] = $filtre; }
$sql .= " ORDER BY da.date_demande DESC";
$stmt = $db->prepare($sql); $stmt->execute($params); $demandes = $stmt->fetchAll();
$statusLabels = ['soumise'=>'Soumise','en_cours'=>'En cours','acceptee'=>'Acceptée','refusee'=>'Refusée','resolue'=>'Résolue'];
$statusBadge = ['soumise'=>'info','en_cours'=>'warning','acceptee'=>'success','refusee'=>'danger','resolue'=>'success'];
$pageTitle = 'Gérer les demandes d\'aide';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div class="breadcrumb"><a href="<?= BASE_URL ?>president_siege/dashboard.php">Mon Siège</a> / Demandes d'aide</div>
    <h1><i class="fas fa-hand-holding-heart"></i> Demandes d'Aide</h1>
    <p><?= htmlspecialchars($siege['nom']) ?></p>
</div>
<div class="container" style="padding:30px 20px;">
    <div class="filters-bar">
        <form method="GET" style="display:flex;gap:16px;flex-wrap:wrap;width:100%;">
            <div class="form-group"><label>Statut</label>
                <select name="statut" class="form-control" onchange="this.form.submit()">
                    <option value="">Toutes</option>
                    <?php foreach ($statusLabels as $k=>$v): ?><option value="<?= $k ?>" <?= $filtre===$k?'selected':'' ?>><?= $v ?></option><?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
    <?php if ($demandes): ?>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Date</th><th>Demandeur</th><th>Sujet</th><th>Type d'aide</th><th>Description</th><th>Statut</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($demandes as $d): ?>
            <tr>
                <td><?= date('d/m/Y', strtotime($d['date_demande'])) ?></td>
                <td><strong><?= htmlspecialchars($d['nom'].' '.$d['prenom']) ?></strong><?php if($d['telephone']): ?><br><small><?= htmlspecialchars($d['telephone']) ?></small><?php endif; ?></td>
                <td><?= htmlspecialchars(substr($d['sujet'],0,50)) ?></td>
                <td><?= ucfirst(str_replace('_',' ',$d['type_aide'])) ?></td>
                <td style="max-width:150px;"><?= htmlspecialchars(substr($d['description']??'',0,60)) ?></td>
                <td><span class="badge badge-<?= $statusBadge[$d['statut']]??'secondary' ?>"><?= $statusLabels[$d['statut']]??$d['statut'] ?></span></td>
                <td>
                    <form method="POST" style="display:flex;gap:5px;flex-wrap:wrap;">
                        <input type="hidden" name="dem_id" value="<?= $d['id'] ?>">
                        <?php if ($d['statut'] === 'soumise'): ?>
                            <button name="action" value="en_cours" type="submit" class="btn btn-warning btn-sm" onclick="return confirm('Mettre en cours ?')"><i class="fas fa-spinner"></i> En cours</button>
                            <button name="action" value="refusee" type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Refuser ?')"><i class="fas fa-times"></i> Refuser</button>
                        <?php elseif ($d['statut'] === 'en_cours'): ?>
                            <button name="action" value="acceptee" type="submit" class="btn btn-success btn-sm" onclick="return confirm('Accepter ?')"><i class="fas fa-check"></i> Accepter</button>
                            <button name="action" value="refusee" type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Refuser ?')"><i class="fas fa-times"></i> Refuser</button>
                        <?php elseif ($d['statut'] === 'acceptee'): ?>
                            <button name="action" value="resolue" type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Marquer comme résolue ?')"><i class="fas fa-heart"></i> Résolue</button>
                        <?php else: ?>
                            <span style="color:var(--text-light);font-size:0.85rem;">—</span>
                        <?php endif; ?>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state card" style="padding:60px;"><i class="fas fa-hand-holding-heart" style="opacity:0.3;color:var(--secondary);"></i><h3>Aucune demande</h3></div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
