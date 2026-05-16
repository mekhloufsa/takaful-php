<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if (isAdmin()) { header('Location: ' . BASE_URL . 'admin/index.php'); exit; }
$pageTitle = 'Mes Dons';
$db = getDB();
$uid = $_SESSION['user_id'];

$filtre = $_GET['type'] ?? '';
$filtre_statut = $_GET['statut'] ?? '';
$sql = "SELECT d.*, s.nom as siege_nom, s.wilaya as siege_wilaya FROM don d LEFT JOIN siege s ON d.siege_id=s.id WHERE d.donateur_id=?";
$params = [$uid];
if ($filtre) { $sql .= " AND d.type=?"; $params[] = $filtre; }
if ($filtre_statut) { $sql .= " AND d.statut=?"; $params[] = $filtre_statut; }
$sql .= " ORDER BY d.date_don DESC";
$stmt = $db->prepare($sql); $stmt->execute($params); $dons = $stmt->fetchAll();

$statusLabels = ['en_attente'=>'En attente','confirme'=>'Confirmé','collecte'=>'Collecté','annule'=>'Annulé'];
$statusBadge = ['en_attente'=>'warning','confirme'=>'success','collecte'=>'success','annule'=>'danger'];

include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div class="breadcrumb"><a href="<?= BASE_URL ?>index.php">Accueil</a> / <a href="<?= BASE_URL ?>dashboard.php">Tableau de bord</a> / Mes dons</div>
    <h1><i class="fas fa-donate"></i> Mes Dons</h1>
    <p>Suivez tous vos dons et leur statut</p>
</div>

<div class="container py-20">
    <div class="filters-bar">
        <form method="GET" class="flex gap-10 items-center" style="flex-wrap:wrap;width:100%;">
            <div class="form-group">
                <label>Type de don</label>
                <select name="type" class="form-control" onchange="this.form.submit()">
                    <option value="">Tous</option>
                    <option value="financier" <?= $filtre==='financier'?'selected':'' ?>>Financier</option>
                    <option value="materiel" <?= $filtre==='materiel'?'selected':'' ?>>Matériel</option>
                </select>
            </div>
            <div class="form-group">
                <label>Statut</label>
                <select name="statut" class="form-control" onchange="this.form.submit()">
                    <option value="">Tous</option>
                    <?php foreach ($statusLabels as $k=>$v): ?>
                    <option value="<?= $k ?>" <?= $filtre_statut===$k?'selected':'' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group flex items-center">
                <a href="<?= BASE_URL ?>dons/index.php" class="btn btn-outline">Réinitialiser</a>
            </div>
            <div class="form-group flex items-center" style="margin-left:auto;">
                <a href="<?= BASE_URL ?>dons/create.php" class="btn btn-primary"><i class="fas fa-plus"></i> Nouveau don</a>
            </div>
        </form>
    </div>

    <?php if ($dons): ?>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Date</th><th>Type</th><th>Catégorie</th><th>Montant</th><th>Siège</th><th>Description</th><th>Statut</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($dons as $d): ?>
            <tr>
                <td><?= date('d/m/Y', strtotime($d['date_don'])) ?></td>
                <td><i class="fas fa-<?= $d['type']==='financier'?'money-bill-wave':'box' ?> icon-primary-text"></i> <?= ucfirst($d['type']) ?></td>
                <td><?= htmlspecialchars($d['categorie'] ?? '—') ?></td>
                <td><?= $d['montant'] > 0 ? '<strong>' . number_format($d['montant'],2) . ' DA</strong>' : '—' ?></td>
                <td><?= $d['siege_nom'] ? htmlspecialchars($d['siege_nom']) . '<br><small class="text-light">' . htmlspecialchars($d['siege_wilaya']) . '</small>' : '—' ?></td>
                <td style="max-width:150px;"><?= htmlspecialchars(substr($d['description'] ?? '', 0, 50)) ?></td>
                <td><span class="badge badge-<?= $statusBadge[$d['statut']] ?? 'secondary' ?>"><?= $statusLabels[$d['statut']] ?? $d['statut'] ?></span></td>
                <td>
                    <div class="table-actions">
                        <a href="<?= BASE_URL ?>dons/track.php?id=<?= $d['id'] ?>" class="btn btn-outline btn-sm"><i class="fas fa-eye"></i></a>
                        <?php if ($d['statut'] === 'en_attente'): ?>
                        <a href="<?= BASE_URL ?>dons/annuler.php?id=<?= $d['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirmDelete('Annuler ce don ?')"><i class="fas fa-times"></i></a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state card py-50">
        <i class="fas fa-donate icon-primary-text" style="opacity:0.4;"></i>
        <h3>Aucun don trouvé</h3>
        <p>Vous n'avez pas encore effectué de don.</p>
        <a href="<?= BASE_URL ?>dons/create.php" class="btn btn-primary mt-20"><i class="fas fa-plus"></i> Faire un don</a>
    </div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
