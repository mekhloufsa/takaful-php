<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole(['president_association']);
$db = getDB();
$uid = $_SESSION['user_id'];
$assoc = $db->prepare("SELECT * FROM association WHERE president_id=? AND statut='active'"); $assoc->execute([$uid]); $assoc = $assoc->fetch();
if (!$assoc) { flash('Association non trouvée ou inactive.', 'error'); header('Location: ' . BASE_URL . 'president_association/dashboard.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $ma_id = $_POST['ma_id'] ?? '';
    if ($action === 'retirer' && $ma_id) {
        $ma = $db->prepare("SELECT ma.*, m.id as mid FROM membre_association ma JOIN membre m ON ma.membre_id=m.id WHERE ma.id=? AND ma.association_id=?");
        $ma->execute([$ma_id, $assoc['id']]); $ma = $ma->fetch();
        if ($ma) {
            $db->prepare("UPDATE membre_association SET statut='inactif' WHERE id=?")->execute([$ma_id]);
            $db->prepare("UPDATE membre SET role='membre' WHERE id=?")->execute([$ma['mid']]);
            flash('Membre retiré de l\'association.', 'success');
        }
    }
    header('Location: ' . BASE_URL . 'president_association/membres.php'); exit;
}

$membres = $db->prepare("SELECT ma.*, m.nom, m.prenom, m.email, m.telephone, m.wilaya, m.role FROM membre_association ma JOIN membre m ON ma.membre_id=m.id WHERE ma.association_id=? ORDER BY ma.date_adhesion DESC");
$membres->execute([$assoc['id']]); $membres = $membres->fetchAll();

$pageTitle = 'Membres de l\'association';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div class="breadcrumb"><a href="<?= BASE_URL ?>president_association/dashboard.php">Mon Association</a> / Membres</div>
    <h1><i class="fas fa-users"></i> Membres de l'Association</h1>
    <p><?= htmlspecialchars($assoc['nom']) ?> — <?= count($membres) ?> membre(s)</p>
</div>
<div class="container" style="padding:30px 20px;">
    <?php if ($membres): ?>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Nom</th><th>Email</th><th>Téléphone</th><th>Wilaya</th><th>Rôle</th><th>Date d'adhésion</th><th>Statut</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($membres as $m): ?>
            <tr>
                <td><strong><?= htmlspecialchars($m['nom'].' '.$m['prenom']) ?></strong></td>
                <td><?= htmlspecialchars($m['email']) ?></td>
                <td><?= htmlspecialchars($m['telephone'] ?? '—') ?></td>
                <td><?= htmlspecialchars($m['wilaya'] ?? '—') ?></td>
                <td><?= ucfirst(str_replace('_',' ',$m['role'])) ?></td>
                <td><?= date('d/m/Y', strtotime($m['date_adhesion'])) ?></td>
                <td><span class="badge badge-<?= $m['statut']==='actif'?'success':'secondary' ?>"><?= ucfirst($m['statut']) ?></span></td>
                <td>
                    <?php if ($m['statut'] === 'actif' && $m['role'] !== 'president_association'): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="retirer">
                        <input type="hidden" name="ma_id" value="<?= $m['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Retirer ce membre ?')"><i class="fas fa-user-minus"></i> Retirer</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state card" style="padding:60px;"><i class="fas fa-users" style="opacity:0.3;color:var(--primary);"></i><h3>Aucun membre</h3><p>Votre association n'a pas encore de membres.</p></div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
