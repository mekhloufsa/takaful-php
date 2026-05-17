<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$db = getDB();
$uid = $_SESSION['user_id'];

// Récupérer les demandes "Membre"
$stmtM = $db->prepare("SELECT ma.*, a.nom as assoc_nom, s.nom as siege_nom, 'Membre' as type_demande 
                       FROM membre_association ma 
                       JOIN association a ON ma.association_id=a.id 
                       JOIN siege s ON ma.siege_id=s.id 
                       WHERE ma.membre_id=? ORDER BY ma.date_adhesion DESC");
$stmtM->execute([$uid]);
$demandesM = $stmtM->fetchAll();

// Récupérer les demandes "Responsable"
$stmtC = $db->prepare("SELECT c.*, a.nom as assoc_nom, s.nom as siege_nom, 'Responsable' as type_demande 
                       FROM candidature_siege c 
                       JOIN siege s ON c.siege_id=s.id 
                       JOIN association a ON s.association_id=a.id 
                       WHERE c.membre_id=? ORDER BY c.date_candidature DESC");
$stmtC->execute([$uid]);
$demandesC = $stmtC->fetchAll();

$toutesDemandes = array_merge($demandesM, $demandesC);

// Trier par date (approximation car date_adhesion vs date_candidature)
usort($toutesDemandes, function($a, $b) {
    $dateA = $a['date_candidature'] ?? $a['date_adhesion'];
    $dateB = $b['date_candidature'] ?? $b['date_adhesion'];
    return strtotime($dateB) - strtotime($dateA);
});

$pageTitle = 'Suivi de mes candidatures';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div class="breadcrumb"><a href="<?= BASE_URL ?>index.php">Accueil</a> / Mes Candidatures</div>
    <h1><i class="fas fa-handshake"></i> Suivi de mes adhésions</h1>
    <p>Consultez l'état de vos demandes pour rejoindre des associations</p>
</div>
<div class="container py-50">
    <?php if ($toutesDemandes): ?>
    <div class="cards-grid">
        <?php foreach ($toutesDemandes as $d): ?>
        <div class="card">
            <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
                <div>
                    <h3 style="margin-bottom:5px;"><i class="fas fa-building icon-primary-text"></i> <?= htmlspecialchars($d['assoc_nom']) ?></h3>
                    <div style="font-size:0.85rem;color:var(--text-light);"><i class="fas fa-map-marker-alt"></i> Siège : <?= htmlspecialchars($d['siege_nom']) ?></div>
                </div>
                <span class="badge badge-<?= ['en_attente'=>'warning','actif'=>'success','inactif'=>'secondary','acceptee'=>'success','refusee'=>'danger'][$d['statut']] ?? 'secondary' ?>">
                    <?= ucfirst(str_replace('_',' ',$d['statut'])) ?>
                </span>
            </div>
            <div class="card-body" style="font-size:0.9rem;">
                <p><strong>Rôle demandé :</strong> <?= $d['type_demande'] ?></p>
                <p><strong>Date :</strong> <?= date('d/m/Y', strtotime($d['date_candidature'] ?? $d['date_adhesion'])) ?></p>
                
                <?php if (!empty($d['message'])): ?>
                <div style="margin-top:10px;padding:10px;background:var(--bg);border-radius:4px;font-style:italic;">
                    "<?= nl2br(htmlspecialchars($d['message'])) ?>"
                </div>
                <?php endif; ?>

                <?php if (!empty($d['document_path'])): ?>
                <div style="margin-top:15px;">
                    <a href="<?= BASE_URL . $d['document_path'] ?>" target="_blank" class="btn btn-outline btn-sm"><i class="fas fa-paperclip"></i> Voir la pièce jointe</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state card py-50">
        <i class="fas fa-handshake icon-primary-text" style="opacity:0.3;"></i>
        <h3>Aucune candidature</h3>
        <p>Vous n'avez soumis aucune demande pour rejoindre une association.</p>
        <a href="<?= BASE_URL ?>associations/index.php" class="btn btn-primary mt-20">Explorer les associations</a>
    </div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
