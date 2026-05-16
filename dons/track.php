<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$db = getDB();
$uid = $_SESSION['user_id'];
$id = $_GET['id'] ?? '';
$stmt = $db->prepare("SELECT d.*, s.nom as siege_nom, s.wilaya as siege_wilaya, m.nom as donateur_nom, m.prenom as donateur_prenom FROM don d LEFT JOIN siege s ON d.siege_id=s.id LEFT JOIN membre m ON d.donateur_id=m.id WHERE d.id=? AND d.donateur_id=?");
$stmt->execute([$id, $uid]); $don = $stmt->fetch();
if (!$don) { flash('Don introuvable.', 'error'); header('Location: ' . BASE_URL . 'dons/index.php'); exit; }

$steps = [
    ['key'=>'en_attente','label'=>'Don soumis','icon'=>'paper-plane','desc'=>'Votre don a été reçu et est en cours d\'examen.'],
    ['key'=>'confirme','label'=>'Don confirmé','icon'=>'check-circle','desc'=>'Le responsable du siège a confirmé votre don.'],
    ['key'=>'collecte','label'=>'Don collecté','icon'=>'hands-helping','desc'=>'Votre don a été collecté et remis aux bénéficiaires.'],
];
$statusOrder = ['en_attente'=>0,'confirme'=>1,'collecte'=>2,'annule'=>-1];
$currentStep = $statusOrder[$don['statut']] ?? 0;

$pageTitle = 'Suivi du Don';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div class="breadcrumb"><a href="<?= BASE_URL ?>index.php">Accueil</a> / <a href="<?= BASE_URL ?>dons/index.php">Mes dons</a> / Suivi</div>
    <h1><i class="fas fa-search"></i> Suivi du Don</h1>
</div>
<div class="container" style="padding:40px 20px;max-width:700px;">
    <div class="card">
        <div class="card-header">
            <div>
                <h2 style="font-size:1.1rem;font-weight:800;">Don <?= ucfirst($don['type']) ?></h2>
                <small style="color:var(--text-light);">Réf: <?= substr($don['id'],0,8) ?>... — <?= date('d/m/Y H:i', strtotime($don['date_don'])) ?></small>
            </div>
            <?php if ($don['statut'] === 'annule'): ?>
            <span class="badge badge-danger">ANNULÉ</span>
            <?php else: ?>
            <span class="badge badge-<?= $don['statut']==='collecte'?'success':'warning' ?>"><?= strtoupper(str_replace('_',' ',$don['statut'])) ?></span>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px;">
                <div><strong>Type :</strong> <?= ucfirst($don['type']) ?></div>
                <div><strong>Catégorie :</strong> <?= htmlspecialchars($don['categorie'] ?? '—') ?></div>
                <?php if ($don['montant'] > 0): ?><div><strong>Montant :</strong> <?= number_format($don['montant'],2) ?> DA</div><?php endif; ?>
                <div><strong>Siège :</strong> <?= $don['siege_nom'] ? htmlspecialchars($don['siege_nom']) . ' (' . htmlspecialchars($don['siege_wilaya']) . ')' : '—' ?></div>
            </div>
            <?php if ($don['description']): ?>
            <div style="padding:12px;background:var(--bg);border-radius:8px;margin-bottom:24px;">
                <strong>Description :</strong> <?= htmlspecialchars($don['description']) ?>
            </div>
            <?php endif; ?>

            <?php if ($don['statut'] !== 'annule'): ?>
            <div style="display:flex;gap:0;margin:30px 0;position:relative;">
                <div style="position:absolute;top:20px;left:0;right:0;height:3px;background:var(--border);z-index:0;"></div>
                <div style="position:absolute;top:20px;left:0;width:<?= min(100, ($currentStep / max(count($steps)-1,1)) * 100) ?>%;height:3px;background:var(--primary);z-index:1;transition:width 0.5s;"></div>
                <?php foreach ($steps as $i => $step): ?>
                <div style="flex:1;text-align:center;position:relative;z-index:2;">
                    <div style="width:42px;height:42px;border-radius:50%;margin:0 auto 10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;background:<?= $i<=$currentStep?'var(--primary)':'var(--border)' ?>;color:<?= $i<=$currentStep?'white':'var(--text-light)' ?>;">
                        <i class="fas fa-<?= $step['icon'] ?>"></i>
                    </div>
                    <div style="font-weight:<?= $i===$currentStep?'800':'600' ?>;font-size:0.82rem;color:<?= $i<=$currentStep?'var(--primary)':'var(--text-light)' ?>;"><?= $step['label'] ?></div>
                    <?php if ($i===$currentStep): ?><div style="font-size:0.76rem;color:var(--text-light);margin-top:4px;"><?= $step['desc'] ?></div><?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <div class="card-footer">
            <a href="<?= BASE_URL ?>dons/index.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Retour</a>
            <?php if ($don['statut'] === 'en_attente'): ?>
            <a href="<?= BASE_URL ?>dons/annuler.php?id=<?= $don['id'] ?>" class="btn btn-danger" onclick="return confirmDelete('Annuler ce don ?')"><i class="fas fa-times"></i> Annuler le don</a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
