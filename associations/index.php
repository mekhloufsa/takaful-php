<?php
require_once __DIR__ . '/../includes/auth.php';
$pageTitle = 'Associations';
$db = getDB();
$search = trim($_GET['q'] ?? '');
$sql = "SELECT a.*, m.nom AS pnom, m.prenom AS ppren, COUNT(DISTINCT s.id) as nb_sieges FROM association a LEFT JOIN membre m ON a.president_id=m.id LEFT JOIN siege s ON s.association_id=a.id WHERE a.statut='active'";
$params = [];
if ($search) { $sql .= " AND a.nom LIKE ?"; $params[] = "%$search%"; }
$sql .= " GROUP BY a.id ORDER BY a.date_creation DESC";
$stmt = $db->prepare($sql); $stmt->execute($params); $associations = $stmt->fetchAll();
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div class="breadcrumb"><a href="<?= BASE_URL ?>index.php">Accueil</a> / Associations</div>
    <h1><i class="fas fa-building"></i> Associations Humanitaires</h1>
    <p>Découvrez les associations actives sur la plateforme Takaful</p>
</div>
<div class="container" style="padding:30px 20px;">
    <div class="filters-bar">
        <form method="GET" style="display:flex;gap:16px;flex-wrap:wrap;width:100%;align-items:flex-end;">
            <div class="form-group" style="flex:1;">
                <label>Rechercher une association</label>
                <input type="text" name="q" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="Nom de l'association...">
            </div>
            <div style="display:flex;gap:10px;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Chercher</button>
                <?php if ($search): ?><a href="?" class="btn btn-outline">Effacer</a><?php endif; ?>
            </div>
            <?php if (isLoggedIn() && !isAdmin()): ?>
            <a href="<?= BASE_URL ?>associations/create.php" class="btn btn-secondary"><i class="fas fa-plus"></i> Créer une association</a>
            <?php endif; ?>
        </form>
    </div>
    <?php if ($associations): ?>
    <div class="cards-grid">
        <?php foreach ($associations as $a): ?>
        <div class="card">
            <div class="card-header">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div class="card-icon icon-green"><i class="fas fa-building"></i></div>
                    <div><h3 style="font-size:1rem;font-weight:800;"><?= htmlspecialchars($a['nom']) ?></h3><span class="badge badge-success">Active</span></div>
                </div>
            </div>
            <div class="card-body">
                <p style="color:var(--text-light);font-size:0.9rem;margin-bottom:14px;"><?= htmlspecialchars(substr($a['description'] ?? 'Association humanitaire au service des personnes dans le besoin.', 0, 120)) ?>...</p>
                <div style="display:flex;gap:16px;font-size:0.85rem;color:var(--text-light);">
                    <span><i class="fas fa-map-marker-alt" style="color:var(--primary);"></i> <?= $a['nb_sieges'] ?> siège(s)</span>
                    <?php if ($a['pnom']): ?><span><i class="fas fa-user" style="color:var(--primary);"></i> <?= htmlspecialchars($a['pnom'].' '.$a['ppren']) ?></span><?php endif; ?>
                </div>
            </div>
            <div class="card-footer">
                <a href="<?= BASE_URL ?>associations/detail.php?id=<?= $a['id'] ?>" class="btn btn-outline btn-sm"><i class="fas fa-eye"></i> Voir les détails</a>
                <?php if (isLoggedIn()): ?>
                <a href="<?= BASE_URL ?>dons/create.php?assoc=<?= $a['id'] ?>" class="btn btn-primary btn-sm"><i class="fas fa-donate"></i> Donner</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state card" style="padding:60px;"><i class="fas fa-building" style="color:var(--primary);opacity:0.3;"></i><h3>Aucune association trouvée</h3><p>Soyez le premier à créer une association !</p><?php if (isLoggedIn() && !isAdmin()): ?><a href="<?= BASE_URL ?>associations/create.php" class="btn btn-primary" style="margin-top:16px;">Créer une association</a><?php endif; ?></div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
