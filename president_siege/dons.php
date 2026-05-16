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
    $don_id = $_POST['don_id'] ?? '';
    $action = $_POST['action'] ?? '';
    $validActions = ['confirme','annule','collecte'];
    if ($don_id && in_array($action, $validActions)) {
        $don = $db->prepare("SELECT * FROM don WHERE id=? AND siege_id=?"); $don->execute([$don_id,$sid]); $don = $don->fetch();
        if ($don) {
            $db->prepare("UPDATE don SET statut=? WHERE id=?")->execute([$action, $don_id]);
            flash('Statut du don mis à jour : ' . $action, 'success');
        }
    }
    header('Location: ' . BASE_URL . 'president_siege/dons.php'); exit;
}

$filtre = $_GET['statut'] ?? '';
$sql = "SELECT d.*, m.nom, m.prenom, m.telephone FROM don d JOIN membre m ON d.donateur_id=m.id WHERE d.siege_id=?";
$params = [$sid];
if ($filtre) { $sql .= " AND d.statut=?"; $params[] = $filtre; }
$sql .= " ORDER BY d.date_don DESC";
$stmt = $db->prepare($sql); $stmt->execute($params); $dons = $stmt->fetchAll();
$statusLabels = ['en_attente'=>'En attente','confirme'=>'Confirmé','collecte'=>'Collecté','annule'=>'Annulé'];
$statusBadge = ['en_attente'=>'warning','confirme'=>'success','collecte'=>'success','annule'=>'danger'];
$pageTitle = 'Gérer les dons';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div class="breadcrumb"><a href="<?= BASE_URL ?>president_siege/dashboard.php">Mon Siège</a> / Gestion des dons</div>
    <h1><i class="fas fa-donate"></i> Gestion des Dons</h1>
    <p><?= htmlspecialchars($siege['nom']) ?></p>
</div>
<div class="container" style="padding:30px 20px;">
    <div class="filters-bar">
        <form method="GET" style="display:flex;gap:16px;flex-wrap:wrap;width:100%;">
            <div class="form-group"><label>Statut</label>
                <select name="statut" class="form-control" onchange="this.form.submit()">
                    <option value="">Tous</option>
                    <?php foreach ($statusLabels as $k=>$v): ?><option value="<?= $k ?>" <?= $filtre===$k?'selected':'' ?>><?= $v ?></option><?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
    <?php if ($dons): ?>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Date</th><th>Donateur</th><th>Type</th><th>Catégorie</th><th>Montant</th><th>Description</th><th>Statut</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($dons as $d): ?>
            <tr>
                <td><?= date('d/m/Y', strtotime($d['date_don'])) ?></td>
                <td><strong><?= htmlspecialchars($d['nom'].' '.$d['prenom']) ?></strong><?php if($d['telephone']): ?><br><small style="color:var(--text-light);"><?= htmlspecialchars($d['telephone']) ?></small><?php endif; ?></td>
                <td><?= ucfirst($d['type']) ?></td>
                <td><?= htmlspecialchars($d['categorie']??'—') ?></td>
                <td><?= $d['montant']>0?'<strong>'.number_format($d['montant'],2).' DA</strong>':'—' ?></td>
                <td><?= htmlspecialchars(substr($d['description']??'',0,40)) ?></td>
                <td><span class="badge badge-<?= $statusBadge[$d['statut']]??'secondary' ?>"><?= $statusLabels[$d['statut']]??$d['statut'] ?></span></td>
                <td>
                    <?php if ($d['statut'] === 'en_attente'): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="don_id" value="<?= $d['id'] ?>">
                        <input type="hidden" name="action" value="confirme">
                        <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Confirmer ce don ?')"><i class="fas fa-check"></i> Confirmer</button>
                    </form>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="don_id" value="<?= $d['id'] ?>">
                        <input type="hidden" name="action" value="annule">
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Annuler ce don ?')"><i class="fas fa-times"></i> Annuler</button>
                    </form>
                    <?php elseif ($d['statut'] === 'confirme'): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="don_id" value="<?= $d['id'] ?>">
                        <input type="hidden" name="action" value="collecte">
                        <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Marquer comme collecté ?')"><i class="fas fa-hands-helping"></i> Collecté</button>
                    </form>
                    <?php else: ?>
                    <span style="color:var(--text-light);font-size:0.85rem;">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state card" style="padding:60px;"><i class="fas fa-donate" style="opacity:0.3;color:var(--primary);"></i><h3>Aucun don reçu</h3></div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
