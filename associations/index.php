<?php
require_once __DIR__ . '/../includes/auth.php';
$pageTitle = 'Associations';
$db     = getDB();
$search = trim($_GET['q'] ?? '');

$wilaya_filter = $_GET['wilaya'] ?? '';
$etat_filter = $_GET['etat'] ?? '';

// On affiche uniquement les associations avec au moins 1 siège actif
$sql = "SELECT a.*, m.nom AS pnom, m.prenom AS ppren,
               COUNT(DISTINCT s.id) as nb_sieges,
               SUM(CASE WHEN s.president_siege_id IS NULL THEN 1 ELSE 0 END) as sieges_sans_resp
        FROM association a
        LEFT JOIN membre m ON a.president_id = m.id
        INNER JOIN siege s ON s.association_id = a.id AND s.statut = 'actif'
        WHERE a.statut = 'active'";
$params = [];
if ($search) {
    $sql .= " AND a.nom LIKE ?";
    $params[] = "%$search%";
}
if ($wilaya_filter) {
    $sql .= " AND s.wilaya = ?";
    $params[] = $wilaya_filter;
}
$sql .= " GROUP BY a.id";

$having = [];
if ($etat_filter === 'cherche_resp') {
    $having[] = "sieges_sans_resp > 0";
} elseif ($etat_filter === 'avec_resp') {
    $having[] = "nb_sieges > sieges_sans_resp";
}
if ($having) {
    $sql .= " HAVING " . implode(" AND ", $having);
}

$sql .= " ORDER BY a.date_creation DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$associations = $stmt->fetchAll();

$wilayas = ['Adrar','Chlef','Laghouat','Oum El Bouaghi','Batna','Béjaïa','Biskra','Béchar','Blida','Bouira','Tamanrasset','Tébessa','Tlemcen','Tiaret','Tizi Ouzou','Alger','Djelfa','Jijel','Sétif','Saïda','Skikda','Sidi Bel Abbès','Annaba','Guelma','Constantine','Médéa','Mostaganem','M\'Sila','Mascara','Ouargla','Oran','El Bayadh','Illizi','Bordj Bou Arréridj','Boumerdès','El Tarf','Tindouf','Tissemsilt','El Oued','Khenchela','Souk Ahras','Tipaza','Mila','Aïn Defla','Naâma','Aïn Témouchent','Ghardaïa','Relizane'];

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
            <div class="form-group" style="flex:1;min-width:200px;">
                <label>Nom de l'association</label>
                <input type="text" name="q" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="Rechercher...">
            </div>
            <div class="form-group">
                <label>Wilaya</label>
                <select name="wilaya" class="form-control" onchange="this.form.submit()">
                    <option value="">Toutes</option>
                    <?php foreach ($wilayas as $w): ?>
                    <option value="<?= $w ?>" <?= $wilaya_filter===$w?'selected':'' ?>><?= $w ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>État / Statut</label>
                <select name="etat" class="form-control" onchange="this.form.submit()">
                    <option value="">Tous les états</option>
                    <option value="avec_resp" <?= $etat_filter==='avec_resp'?'selected':'' ?>>Avec responsable</option>
                    <option value="cherche_resp" <?= $etat_filter==='cherche_resp'?'selected':'' ?>>Cherche responsable</option>
                </select>
            </div>
            <div style="display:flex;gap:10px;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Chercher</button>
                <?php if ($search || $wilaya_filter || $etat_filter): ?><a href="?" class="btn btn-outline">Effacer</a><?php endif; ?>
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
                    <div>
                        <h3 style="font-size:1rem;font-weight:800;"><?= htmlspecialchars($a['nom']) ?></h3>
                        <span class="badge badge-success">Active</span>
                        <?php if ($a['sieges_sans_resp'] > 0): ?>
                        <span class="badge badge-warning" style="margin-left:6px;"><i class="fas fa-exclamation-circle"></i> Cherche responsable</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <p style="color:var(--text-light);font-size:0.9rem;margin-bottom:14px;">
                    <?= htmlspecialchars(substr($a['description'] ?? 'Association humanitaire au service des personnes dans le besoin.', 0, 120)) ?>...
                </p>
                <div style="display:flex;gap:16px;font-size:0.85rem;color:var(--text-light);">
                    <span><i class="fas fa-map-marker-alt" style="color:var(--primary);"></i> <?= $a['nb_sieges'] ?> siège(s)</span>
                    <?php if ($a['pnom']): ?>
                    <span><i class="fas fa-user" style="color:var(--primary);"></i> <?= htmlspecialchars($a['pnom'].' '.$a['ppren']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-footer">
                <a href="<?= BASE_URL ?>associations/detail.php?id=<?= $a['id'] ?>" class="btn btn-outline btn-sm"><i class="fas fa-eye"></i> Voir les détails</a>
                <?php if (isLoggedIn() && !isAdmin()): ?>
                <a href="<?= BASE_URL ?>dons/create.php?assoc=<?= $a['id'] ?>" class="btn btn-primary btn-sm"><i class="fas fa-donate"></i> Donner</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state card" style="padding:60px;">
        <i class="fas fa-building" style="color:var(--primary);opacity:0.3;"></i>
        <h3><?= $search || $wilaya_filter || $etat_filter ? 'Aucune association trouvée avec ces critères' : 'Aucune association disponible pour le moment' ?></h3>
        <p>Les associations apparaissent ici une fois validées par l'administration.</p>
        <?php if (isLoggedIn() && !isAdmin()): ?>
        <a href="<?= BASE_URL ?>associations/create.php" class="btn btn-primary" style="margin-top:16px;">Créer une association</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
