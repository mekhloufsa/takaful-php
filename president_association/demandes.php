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
$queryStr = "SELECT da.*, s.nom as siege_nom, s.wilaya as siege_wilaya, m.nom as demandeur_nom, m.prenom as demandeur_prenom, m.telephone as demandeur_tel, m.email as demandeur_email 
             FROM demande_aide da 
             JOIN siege s ON da.siege_id=s.id 
             JOIN membre m ON da.demandeur_id=m.id 
             WHERE s.association_id=? ";
$params = [$assoc['id']];

if (!empty($filter_siege)) {
    $queryStr .= " AND da.siege_id=? ";
    $params[] = $filter_siege;
}

$queryStr .= " ORDER BY da.date_demande DESC";

$demQuery = $db->prepare($queryStr);
$demQuery->execute($params);
$demandes = $demQuery->fetchAll();

$pageTitle = 'Demandes de l\'Association - Consultation';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div class="breadcrumb"><a href="<?= BASE_URL ?>president_association/dashboard.php">Mon Association</a> / Demandes</div>
    <h1><i class="fas fa-hand-holding-heart"></i> Demandes d'Aide reçues</h1>
    <p>Consultez l'historique complet et les dossiers des personnes demandant assistance auprès de vos sièges locaux.</p>
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
                <a href="demandes.php" class="btn btn-outline btn-sm"><i class="fas fa-times"></i> Réinitialiser</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($demandes): ?>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Demandeur</th>
                    <th>Sujet / Type d'Aide</th>
                    <th>Justificatif</th>
                    <th>Siège Destinataire</th>
                    <th>Date de Demande</th>
                    <th>Statut actuel</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($demandes as $d): ?>
            <tr>
                <td>
                    <strong><?= htmlspecialchars($d['demandeur_nom'].' '.$d['demandeur_prenom']) ?></strong>
                    <br><small class="text-light"><i class="fas fa-phone"></i> <?= htmlspecialchars($d['demandeur_tel']) ?></small>
                </td>
                <td>
                    <strong><?= htmlspecialchars($d['sujet']) ?></strong>
                    <br><span class="badge badge-secondary"><?= ucfirst($d['type_aide']) ?></span>
                </td>
                <td>
                    <?php if (!empty($d['document_path'])): ?>
                        <a href="<?= BASE_URL . htmlspecialchars($d['document_path']) ?>" target="_blank" class="btn btn-xs btn-outline">
                            <i class="fas fa-paperclip"></i> Consulter
                        </a>
                    <?php else: ?>
                        <span class="text-light">— Aucun —</span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge badge-primary"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($d['siege_nom']) ?></span>
                </td>
                <td><?= date('d/m/Y H:i', strtotime($d['date_demande'])) ?></td>
                <td>
                    <span class="badge badge-<?= ['soumise'=>'info','en_cours'=>'warning','acceptee'=>'success','refusee'=>'danger','resolue'=>'success'][$d['statut']] ?? 'secondary' ?>">
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
        <i class="fas fa-hand-holding-heart" style="opacity:0.3; color:var(--primary); font-size: 3rem;"></i>
        <h3>Aucune demande trouvée</h3>
        <p>Aucune demande d'aide n'a été soumise pour ce siège ou pour cette association.</p>
    </div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
