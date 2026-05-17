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

// Récupérer les sièges pour le filtre
$sieges = $db->prepare("SELECT id, nom FROM siege WHERE association_id=? AND statut='actif' ORDER BY nom");
$sieges->execute([$assoc['id']]);
$sieges = $sieges->fetchAll();

// Filtre
$filter_siege = $_GET['siege_id'] ?? '';
$queryStr = "SELECT d.*, s.nom as siege_nom, s.wilaya as siege_wilaya, m.nom as donateur_nom, m.prenom as donateur_prenom, m.telephone as donateur_tel, m.email as donateur_email 
             FROM don d 
             JOIN siege s ON d.siege_id=s.id 
             JOIN membre m ON d.donateur_id=m.id 
             WHERE s.association_id=? ";
$params = [$assoc['id']];

if (!empty($filter_siege)) {
    $queryStr .= " AND d.siege_id=? ";
    $params[] = $filter_siege;
}

$queryStr .= " ORDER BY d.date_don DESC";

$donsQuery = $db->prepare($queryStr);
$donsQuery->execute($params);
$dons = $donsQuery->fetchAll();

$pageTitle = 'Dons de l\'Association - Consultation';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div class="breadcrumb"><a href="<?= BASE_URL ?>president_association/dashboard.php">Mon Association</a> / Dons</div>
    <h1><i class="fas fa-donate"></i> Dons reçus par l'Association</h1>
    <p>Consultez l'historique complet et les détails de tous les dons acheminés vers vos sièges locaux.</p>
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
                <a href="dons.php" class="btn btn-outline btn-sm"><i class="fas fa-times"></i> Réinitialiser</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($dons): ?>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Donateur</th>
                    <th>Type / Catégorie</th>
                    <th>Montant / Description</th>
                    <th>Siège Destinataire</th>
                    <th>Date du Don</th>
                    <th>Statut actuel</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($dons as $d): ?>
            <tr>
                <td>
                    <strong><?= htmlspecialchars($d['donateur_nom'].' '.$d['donateur_prenom']) ?></strong>
                    <br><small class="text-light"><i class="fas fa-phone"></i> <?= htmlspecialchars($d['donateur_tel']) ?></small>
                </td>
                <td>
                    <span class="badge badge-secondary"><?= ucfirst($d['type']) ?></span>
                    <?php if ($d['categorie']): ?>
                        <br><small class="text-light"><?= htmlspecialchars($d['categorie']) ?></small>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($d['montant'] > 0): ?>
                        <span style="font-weight:bold; color:#2ecc71;"><?= number_format($d['montant'], 2, ',', ' ') ?> DZD</span>
                    <?php else: ?>
                        <span class="text-light">— Don Matériel —</span>
                    <?php endif; ?>
                    <?php if ($d['description']): ?>
                        <br><small class="text-light" style="display:block; max-width:250px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?= htmlspecialchars($d['description']) ?>">
                            <?= htmlspecialchars($d['description']) ?>
                        </small>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge badge-primary"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($d['siege_nom']) ?></span>
                </td>
                <td><?= date('d/m/Y H:i', strtotime($d['date_don'])) ?></td>
                <td>
                    <span class="badge badge-<?= ['en_attente'=>'warning','confirme'=>'info','collecte'=>'success','annule'=>'danger'][$d['statut']] ?? 'secondary' ?>">
                        <?= ucfirst(str_replace('_', ' ', $d['statut'])) ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state card" style="padding:60px;">
        <i class="fas fa-donate" style="opacity:0.3; color:var(--primary); font-size: 3rem;"></i>
        <h3>Aucun don trouvé</h3>
        <p>Aucun don n'a été soumis pour ce siège ou pour cette association.</p>
    </div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
