<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole(['president_association']);
$db = getDB();
$uid = $_SESSION['user_id'];
$assoc = $db->prepare("SELECT * FROM association WHERE president_id=?"); $assoc->execute([$uid]); $assoc = $assoc->fetch();
if (!$assoc) { flash('Association introuvable.', 'error'); header('Location: ' . BASE_URL . 'dashboard.php'); exit; }
$sieges = $db->prepare("SELECT s.*, m.nom as ps_nom, m.prenom as ps_prenom FROM siege s LEFT JOIN membre m ON s.president_siege_id=m.id WHERE s.association_id=? ORDER BY s.wilaya, s.nom"); $sieges->execute([$assoc['id']]); $sieges = $sieges->fetchAll();
$pageTitle = 'Gestion des Sièges';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div class="breadcrumb"><a href="<?= BASE_URL ?>index.php">Accueil</a> / <a href="<?= BASE_URL ?>president_association/dashboard.php">Mon Association</a> / Sièges</div>
    <h1><i class="fas fa-map-marker-alt"></i> Gestion des Sièges</h1>
    <p><?= htmlspecialchars($assoc['nom']) ?></p>
</div>
<div class="container py-20">
    <div class="flex justify-between items-center mb-20">
        <h2 style="font-size:1.2rem;font-weight:700;">Liste des sièges</h2>
        <a href="<?= BASE_URL ?>sieges/create.php" class="btn btn-primary"><i class="fas fa-plus"></i> Créer un siège</a>
    </div>
    <?php if ($sieges): ?>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Nom du siège</th><th>Wilaya</th><th>Adresse</th><th>Président de siège</th><th>Statut</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($sieges as $s): ?>
            <tr>
                <td><strong><?= htmlspecialchars($s['nom']) ?></strong></td>
                <td><?= htmlspecialchars($s['wilaya']) ?></td>
                <td><?= htmlspecialchars($s['adresse'] ?? '—') ?></td>
                <td><?= $s['ps_nom'] ? htmlspecialchars($s['ps_nom'].' '.$s['ps_prenom']) : '<span class="text-light">Non assigné</span>' ?></td>
                <td><span class="badge badge-<?= $s['statut']==='actif'?'success':'secondary' ?>"><?= ucfirst($s['statut']) ?></span></td>
                <td>
                    <div class="table-actions">
                        <a href="<?= BASE_URL ?>sieges/detail.php?id=<?= $s['id'] ?>" class="btn btn-outline btn-sm"><i class="fas fa-eye"></i></a>
                        <a href="<?= BASE_URL ?>sieges/edit.php?id=<?= $s['id'] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                        <a href="<?= BASE_URL ?>sieges/delete.php?id=<?= $s['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirmDelete('Supprimer ce siège ?')"><i class="fas fa-trash"></i></a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state card py-50">
        <i class="fas fa-map-marker-alt icon-primary-text" style="opacity:0.3;"></i>
        <h3>Aucun siège créé</h3>
        <p>Créez votre premier siège pour commencer à organiser vos activités.</p>
        <a href="<?= BASE_URL ?>sieges/create.php" class="btn btn-primary mt-20"><i class="fas fa-plus"></i> Créer un siège</a>
    </div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
