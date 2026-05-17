<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$db = getDB();
$uid = $_SESSION['user_id'];
$id = $_GET['id'] ?? '';
$stmt = $db->prepare("SELECT da.*, s.nom as siege_nom, s.wilaya as siege_wilaya FROM demande_aide da LEFT JOIN siege s ON da.siege_id=s.id WHERE da.id=? AND da.demandeur_id=?");
$stmt->execute([$id, $uid]); $demande = $stmt->fetch();
if (!$demande) { flash('Demande introuvable.', 'error'); header('Location: ' . BASE_URL . 'demandes/index.php'); exit; }

$statusLabels = ['soumise'=>'Soumise','en_cours'=>'En cours de traitement','acceptee'=>'Acceptée','refusee'=>'Refusée','resolue'=>'Résolue'];
$statusBadge = ['soumise'=>'info','en_cours'=>'warning','acceptee'=>'success','refusee'=>'danger','resolue'=>'success'];
$typeLabels = ['financiere'=>'Aide financière','medicale'=>'Aide médicale','alimentaire'=>'Aide alimentaire','autre'=>'Autre'];

$assign = $db->prepare("SELECT * FROM assignation WHERE demande_id=? ORDER BY date_assignation DESC LIMIT 1");
$assign->execute([$id]);
$assignData = $assign->fetch();

$steps = [
    ['key'=>'soumise','label'=>'Demande soumise','icon'=>'paper-plane'],
    ['key'=>'en_cours','label'=>'En traitement','icon'=>'spinner'],
    ['key'=>'acceptee','label'=>'Acceptée','icon'=>'check-circle'],
    ['key'=>'resolue','label'=>'Résolue','icon'=>'heart'],
];
$stepOrder = ['soumise'=>0,'en_cours'=>1,'acceptee'=>2,'resolue'=>3,'refusee'=>-1];
$currentStep = $stepOrder[$demande['statut']] ?? 0;

$pageTitle = 'Suivi de la demande';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div class="breadcrumb"><a href="<?= BASE_URL ?>index.php">Accueil</a> / <a href="<?= BASE_URL ?>demandes/index.php">Demandes</a> / Suivi</div>
    <h1><i class="fas fa-search"></i> Suivi de la Demande</h1>
</div>
<div class="container" style="padding:40px 20px;max-width:700px;">
    <div class="card">
        <div class="card-header">
            <div>
                <h2 style="font-size:1.1rem;font-weight:800;"><?= htmlspecialchars($demande['sujet']) ?></h2>
                <small style="color:var(--text-light);">Réf: <?= substr($demande['id'],0,8) ?>... — <?= date('d/m/Y H:i', strtotime($demande['date_demande'])) ?></small>
            </div>
            <span class="badge badge-<?= $statusBadge[$demande['statut']] ?? 'secondary' ?>"><?= $statusLabels[$demande['statut']] ?? $demande['statut'] ?></span>
        </div>
        <div class="card-body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px;">
                <div><strong>Type d'aide :</strong> <?= $typeLabels[$demande['type_aide']] ?? $demande['type_aide'] ?></div>
                <div><strong>Siège :</strong> <?= $demande['siege_nom'] ? htmlspecialchars($demande['siege_nom']) : '—' ?></div>
            </div>
            <?php if ($demande['description']): ?>
            <div style="padding:14px;background:var(--bg);border-radius:8px;margin-bottom:16px;">
                <strong>Description :</strong><br><?= nl2br(htmlspecialchars($demande['description'])) ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($demande['document_path'])): ?>
            <div style="display:flex;align-items:center;gap:14px;padding:14px;background:var(--primary-light);border-radius:8px;margin-bottom:24px;">
                <i class="fas fa-paperclip" style="font-size:1.5rem;color:var(--primary);"></i>
                <div>
                    <p style="font-weight:700;margin-bottom:4px;font-size:0.9rem;">Pièce jointe</p>
                    <a href="<?= BASE_URL . htmlspecialchars($demande['document_path']) ?>" target="_blank" class="btn btn-outline btn-sm">
                        <i class="fas fa-eye"></i> Voir le document
                    </a>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($demande['statut'] === 'refusee'): ?>
                <div class="alert alert-danger" style="margin-bottom:15px;"><i class="fas fa-times-circle"></i> Votre demande a été refusée.</div>
                <?php if ($demande['message_decision']): ?>
                    <div style="padding:15px;background:#ffebee;border-left:4px solid #f44336;border-radius:4px;">
                        <strong>Motif du refus :</strong><br><?= nl2br(htmlspecialchars($demande['message_decision'])) ?>
                    </div>
                <?php endif; ?>
            <?php elseif ($demande['statut'] !== 'resolue'): ?>
            <div style="display:flex;gap:0;margin:30px 0;position:relative;">
                <div style="position:absolute;top:20px;left:0;right:0;height:3px;background:var(--border);z-index:0;"></div>
                <div style="position:absolute;top:20px;left:0;width:<?= min(100,($currentStep/max(count($steps)-1,1))*100) ?>%;height:3px;background:var(--primary);z-index:1;transition:width 0.5s;"></div>
                <?php foreach ($steps as $i => $step): ?>
                <div style="flex:1;text-align:center;position:relative;z-index:2;">
                    <div style="width:42px;height:42px;border-radius:50%;margin:0 auto 10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;background:<?= $i<=$currentStep?'var(--primary)':'var(--border)' ?>;color:<?= $i<=$currentStep?'white':'var(--text-light)' ?>;">
                        <i class="fas fa-<?= $step['icon'] ?>"></i>
                    </div>
                    <div style="font-weight:<?= $i===$currentStep?'800':'600' ?>;font-size:0.82rem;color:<?= $i<=$currentStep?'var(--primary)':'var(--text-light)' ?>;"><?= $step['label'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
                <div class="alert alert-success" style="margin-bottom:15px;"><i class="fas fa-check-circle"></i> Votre demande a été résolue avec succès !</div>
            <?php endif; ?>

            <?php if (in_array($demande['statut'], ['acceptee', 'en_cours', 'resolue']) && $demande['message_decision']): ?>
                <div style="padding:15px;background:#e8f5e9;border-left:4px solid #4caf50;border-radius:4px;margin-top:20px;">
                    <strong>Message du responsable :</strong><br><?= nl2br(htmlspecialchars($demande['message_decision'])) ?>
                </div>
            <?php endif; ?>

            <?php if ($assignData && $assignData['statut'] === 'terminee' && $assignData['date_rendezvous']): ?>
                <div style="padding:15px;background:var(--bg);border:1px solid var(--border);border-radius:4px;margin-top:20px;">
                    <h4 style="color:var(--primary);margin-bottom:10px;"><i class="fas fa-calendar-check"></i> Rendez-vous planifié</h4>
                    <p><strong>Date et Heure :</strong> <?= date('d/m/Y à H:i', strtotime($assignData['date_rendezvous'])) ?></p>
                    <?php if ($assignData['note_rendezvous']): ?>
                        <p style="margin-top:8px;"><strong>Note (Lieu / Instructions) :</strong><br><?= nl2br(htmlspecialchars($assignData['note_rendezvous'])) ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($assignData && $assignData['statut'] === 'annulee' && $assignData['message_non_resolu']): ?>
                <div class="alert alert-warning" style="margin-top:20px;">
                    <i class="fas fa-exclamation-triangle"></i> <strong>Problème lors du traitement :</strong><br>
                    <?= nl2br(htmlspecialchars($assignData['message_non_resolu'])) ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-footer">
            <a href="<?= BASE_URL ?>demandes/index.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Retour</a>
            <a href="<?= BASE_URL ?>demandes/create.php" class="btn btn-secondary"><i class="fas fa-plus"></i> Nouvelle demande</a>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
