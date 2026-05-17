<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole(['president_association']);
$db = getDB();
$uid = $_SESSION['user_id'];
$assoc = $db->prepare("SELECT * FROM association WHERE president_id=? AND statut='active'");
$assoc->execute([$uid]);
$assoc = $assoc->fetch();

if (!$assoc) { 
    flash('Association non trouvée ou inactive.', 'error'); 
    header('Location: ' . BASE_URL . 'president_association/dashboard.php'); 
    exit; 
}

// Récupérer la liste des sièges pour le filtre
$sieges = $db->prepare("SELECT id, nom FROM siege WHERE association_id=? AND statut='actif' ORDER BY nom");
$sieges->execute([$assoc['id']]);
$sieges = $sieges->fetchAll();

// Gérer le filtre par siège
$filter_siege = $_GET['siege_id'] ?? '';
$queryStr = "SELECT ma.*, m.nom, m.prenom, m.email, m.telephone, m.wilaya, m.role, s.nom as siege_nom, s.wilaya as siege_wilaya 
             FROM membre_association ma 
             JOIN membre m ON ma.membre_id=m.id 
             LEFT JOIN siege s ON ma.siege_id=s.id 
             WHERE ma.association_id=? ";
$params = [$assoc['id']];

if (!empty($filter_siege)) {
    $queryStr .= " AND ma.siege_id=? ";
    $params[] = $filter_siege;
}

$queryStr .= " ORDER BY FIELD(ma.statut, 'en_attente', 'actif', 'inactif'), ma.date_adhesion DESC";

$membresQuery = $db->prepare($queryStr);
$membresQuery->execute($params);
$membres = $membresQuery->fetchAll();

$pageTitle = 'Membres des Sièges - Consultation';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div class="breadcrumb"><a href="<?= BASE_URL ?>president_association/dashboard.php">Mon Association</a> / Membres</div>
    <h1><i class="fas fa-users"></i> Membres & Bénévoles des Sièges</h1>
    <p>Consultez la liste des membres actifs et des candidatures en attente de traitement par vos responsables locaux.</p>
</div>

<div class="container py-30">
    <div class="card mb-20" style="padding:15px; background:var(--bg); border:1px solid var(--border);">
        <form method="GET" style="display:flex; gap:15px; align-items:center; flex-wrap:wrap;">
            <div class="form-group" style="margin:0; flex:1; min-width:250px;">
                <select name="siege_id" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Filtrer par Siège (Tous les sièges) --</option>
                    <?php foreach ($sieges as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $filter_siege === $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Filtrer</button>
            <?php if (!empty($filter_siege)): ?>
                <a href="membres.php" class="btn btn-outline btn-sm"><i class="fas fa-times"></i> Réinitialiser</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($membres): ?>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Nom Complet</th>
                    <th>Email</th>
                    <th>Téléphone</th>
                    <th>Siège affecté</th>
                    <th>Statut d'Adhésion</th>
                    <th>Date d'Adhésion</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($membres as $m): ?>
            <tr style="<?= $m['statut']==='en_attente' ? 'background:rgba(241, 196, 15, 0.05);' : '' ?>">
                <td>
                    <strong><?= htmlspecialchars($m['nom'].' '.$m['prenom']) ?></strong>
                    <?php if ($m['statut'] === 'en_attente'): ?>
                        <br><span class="badge badge-warning" style="font-size:0.75rem; margin-top:4px;"><i class="fas fa-clock"></i> Candidat</span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($m['email']) ?></td>
                <td><?= htmlspecialchars($m['telephone'] ?? '—') ?></td>
                <td>
                    <?php if($m['siege_nom']): ?>
                        <span class="badge badge-primary"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($m['siege_nom']) ?></span>
                        <br><small class="text-light"><?= htmlspecialchars($m['siege_wilaya']) ?></small>
                    <?php else: ?>
                        <span class="text-light">— Non assigné —</span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge badge-<?= ['en_attente'=>'warning','actif'=>'success','inactif'=>'secondary'][$m['statut']] ?? 'secondary' ?>">
                        <?= $m['statut'] === 'en_attente' ? 'En attente du Siège' : ucfirst($m['statut']) ?>
                    </span>
                </td>
                <td><?= $m['date_adhesion'] ? date('d/m/Y', strtotime($m['date_adhesion'])) : '—' ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state card" style="padding:60px;">
        <i class="fas fa-users" style="opacity:0.3; color:var(--primary); font-size: 3rem;"></i>
        <h3>Aucun bénévole trouvé</h3>
        <p>Il n'y a aucun membre enregistré pour ce siège ou pour cette association.</p>
    </div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
