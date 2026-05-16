<?php
require_once __DIR__ . '/../includes/auth.php';
$db = getDB();
$id = $_GET['id'] ?? '';
$stmt = $db->prepare("SELECT a.*, m.nom AS pnom, m.prenom AS ppren, m.telephone AS ptel FROM association a LEFT JOIN membre m ON a.president_id=m.id WHERE a.id=?");
$stmt->execute([$id]); $assoc = $stmt->fetch();
if (!$assoc) { flash('Association introuvable.', 'error'); header('Location: ' . BASE_URL . 'associations/index.php'); exit; }
$sieges = $db->prepare("SELECT * FROM siege WHERE association_id=? AND statut='actif'"); $sieges->execute([$id]); $sieges = $sieges->fetchAll();
$nbMembres = $db->prepare("SELECT COUNT(*) FROM membre_association WHERE association_id=? AND statut='actif'"); $nbMembres->execute([$id]); $nbMembres = $nbMembres->fetchColumn();
$pageTitle = htmlspecialchars($assoc['nom']);
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div class="breadcrumb"><a href="<?= BASE_URL ?>index.php">Accueil</a> / <a href="<?= BASE_URL ?>associations/index.php">Associations</a> / <?= htmlspecialchars($assoc['nom']) ?></div>
    <h1><i class="fas fa-building"></i> <?= htmlspecialchars($assoc['nom']) ?></h1>
    <p><span class="badge badge-success">Association active</span></p>
</div>
<div class="container" style="padding:30px 20px;">
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;" class="detail-grid">
        <div>
            <div class="card" style="margin-bottom:24px;">
                <div class="card-header"><h3><i class="fas fa-info-circle" style="color:var(--primary);"></i> À propos</h3></div>
                <div class="card-body">
                    <p><?= nl2br(htmlspecialchars($assoc['description'] ?? 'Association humanitaire au service de la communauté.')) ?></p>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:20px;padding-top:20px;border-top:1px solid var(--border);">
                        <div><i class="fas fa-user" style="color:var(--primary);"></i> <strong>Président :</strong><br><?= htmlspecialchars($assoc['pnom'].' '.$assoc['ppren']) ?></div>
                        <div><i class="fas fa-users" style="color:var(--primary);"></i> <strong>Membres :</strong><br><?= $nbMembres ?> membre(s) actif(s)</div>
                        <div><i class="fas fa-map-marker-alt" style="color:var(--primary);"></i> <strong>Sièges :</strong><br><?= count($sieges) ?> siège(s)</div>
                        <div><i class="fas fa-calendar" style="color:var(--primary);"></i> <strong>Créée le :</strong><br><?= date('d/m/Y', strtotime($assoc['date_creation'])) ?></div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-map-marker-alt" style="color:var(--primary);"></i> Nos Sièges</h3></div>
                <div class="card-body">
                    <?php if ($sieges): ?>
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px;">
                        <?php foreach ($sieges as $s): ?>
                        <div style="padding:14px;border:1px solid var(--border);border-radius:8px;">
                            <h4 style="font-weight:700;margin-bottom:6px;"><?= htmlspecialchars($s['nom']) ?></h4>
                            <p style="color:var(--text-light);font-size:0.85rem;"><i class="fas fa-map-pin"></i> <?= htmlspecialchars($s['wilaya']) ?></p>
                            <?php if ($s['adresse']): ?><p style="color:var(--text-light);font-size:0.82rem;"><?= htmlspecialchars($s['adresse']) ?></p><?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?><div class="empty-state"><i class="fas fa-map-marker-alt"></i><h3>Aucun siège pour l'instant</h3></div><?php endif; ?>
                </div>
            </div>
        </div>
        <div>
            <div class="card" style="margin-bottom:24px;">
                <div class="card-body">
                    <?php if (isLoggedIn()): ?>
                    <a href="<?= BASE_URL ?>dons/create.php" class="btn btn-primary" style="width:100%;justify-content:center;margin-bottom:12px;"><i class="fas fa-donate"></i> Faire un don</a>
                    <a href="<?= BASE_URL ?>demandes/create.php" class="btn btn-outline" style="width:100%;justify-content:center;"><i class="fas fa-hand-holding-heart"></i> Demander de l'aide</a>
                    <?php else: ?>
                    <a href="<?= BASE_URL ?>register.php" class="btn btn-primary" style="width:100%;justify-content:center;"><i class="fas fa-user-plus"></i> Rejoindre Takaful</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<style>@media(max-width:700px){.detail-grid{grid-template-columns:1fr!important;}}</style>
<?php include __DIR__ . '/../includes/footer.php'; ?>
