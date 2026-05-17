<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole(['president_association']);
$db = getDB();
$uid = $_SESSION['user_id'];

// Récupérer l'association du président
$assoc = $db->prepare("SELECT * FROM association WHERE president_id=? AND statut='active'");
$assoc->execute([$uid]); $assoc = $assoc->fetch();
if (!$assoc) { flash('Association non trouvée ou inactive.', 'error'); header('Location: ' . BASE_URL . 'president_association/dashboard.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $cid = $_POST['candidature_id'] ?? '';
    if ($cid) {
        $cand = $db->prepare("SELECT c.*, s.president_siege_id FROM candidature_siege c JOIN siege s ON c.siege_id=s.id WHERE c.id=? AND s.association_id=?");
        $cand->execute([$cid, $assoc['id']]); $cand = $cand->fetch();

        if ($cand && $cand['statut'] === 'en_attente') {
            if ($action === 'accepter') {
                if ($cand['president_siege_id']) {
                    flash('Ce siège a déjà un responsable. Rejetez la candidature.', 'error');
                } else {
                    // 1. Mettre à jour la candidature
                    $db->prepare("UPDATE candidature_siege SET statut='acceptee' WHERE id=?")->execute([$cid]);
                    
                    // 2. Mettre à jour le siège
                    $db->prepare("UPDATE siege SET president_siege_id=? WHERE id=?")->execute([$cand['membre_id'], $cand['siege_id']]);
                    
                    // 3. Mettre à jour le rôle du membre
                    $db->prepare("UPDATE membre SET role='president_siege' WHERE id=?")->execute([$cand['membre_id']]);
                    
                    // 4. Refuser automatiquement les autres candidatures pour ce même siège
                    $db->prepare("UPDATE candidature_siege SET statut='refusee' WHERE siege_id=? AND statut='en_attente'")->execute([$cand['siege_id']]);

                    flash('Candidature acceptée ! Le siège a maintenant un responsable.', 'success');
                }
            } elseif ($action === 'rejeter') {
                $db->prepare("UPDATE candidature_siege SET statut='refusee' WHERE id=?")->execute([$cid]);
                flash('Candidature refusée.', 'success');
            }
        }
    }
    header('Location: ' . BASE_URL . 'president_association/candidatures.php'); exit;
}

$sql = "SELECT c.*, m.nom, m.prenom, m.email, m.telephone, m.wilaya, s.nom as siege_nom, s.president_siege_id
        FROM candidature_siege c 
        JOIN membre m ON c.membre_id=m.id 
        JOIN siege s ON c.siege_id=s.id 
        WHERE s.association_id=? 
        ORDER BY FIELD(c.statut, 'en_attente', 'acceptee', 'refusee'), c.date_candidature DESC";
$candidatures = $db->prepare($sql);
$candidatures->execute([$assoc['id']]); $candidatures = $candidatures->fetchAll();

$pageTitle = 'Candidatures de Responsables de Siège';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div class="breadcrumb"><a href="<?= BASE_URL ?>president_association/dashboard.php">Mon Association</a> / Candidatures Sièges</div>
    <h1><i class="fas fa-briefcase"></i> Candidatures "Responsable de Siège"</h1>
</div>
<div class="container" style="padding:30px 20px;">
    <?php if ($candidatures): ?>
    <div class="cards-grid">
        <?php foreach ($candidatures as $c): ?>
        <div class="card" style="<?= $c['statut']==='en_attente' ? 'border:2px solid var(--warning);' : '' ?>">
            <div class="card-header" style="display:flex;justify-content:space-between;align-items:flex-start;">
                <div>
                    <h3 style="margin-bottom:4px;"><i class="fas fa-user icon-primary-text"></i> <?= htmlspecialchars($c['nom'].' '.$c['prenom']) ?></h3>
                    <small class="text-light"><?= date('d/m/Y H:i', strtotime($c['date_candidature'])) ?></small>
                </div>
                <span class="badge badge-<?= ['en_attente'=>'warning','acceptee'=>'success','refusee'=>'danger'][$c['statut']] ?? 'secondary' ?>"><?= ucfirst(str_replace('_',' ',$c['statut'])) ?></span>
            </div>
            <div class="card-body">
                <div style="margin-bottom:12px;font-size:0.9rem;">
                    <div><strong><i class="fas fa-map-marker-alt" style="color:var(--primary);"></i> Siège :</strong> <?= htmlspecialchars($c['siege_nom']) ?></div>
                    <div><strong><i class="fas fa-envelope"></i> Email :</strong> <?= htmlspecialchars($c['email']) ?></div>
                    <div><strong><i class="fas fa-phone"></i> Tél :</strong> <?= htmlspecialchars($c['telephone'] ?? '—') ?></div>
                </div>
                
                <?php if ($c['message']): ?>
                <div style="padding:10px;background:var(--bg);border-radius:4px;font-size:0.85rem;margin-bottom:14px;font-style:italic;">
                    "<?= nl2br(htmlspecialchars($c['message'])) ?>"
                </div>
                <?php endif; ?>

                <?php if ($c['statut'] === 'en_attente'): ?>
                    <?php if ($c['president_siege_id']): ?>
                        <div class="alert alert-warning" style="padding:8px;font-size:0.8rem;margin-bottom:10px;">
                            <i class="fas fa-exclamation-triangle"></i> Ce siège a déjà un responsable assigné.
                        </div>
                        <form method="POST">
                            <input type="hidden" name="candidature_id" value="<?= $c['id'] ?>">
                            <button name="action" value="rejeter" class="btn btn-danger w-100" style="justify-content:center;"><i class="fas fa-times"></i> Rejeter</button>
                        </form>
                    <?php else: ?>
                        <form method="POST" style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                            <input type="hidden" name="candidature_id" value="<?= $c['id'] ?>">
                            <button name="action" value="accepter" class="btn btn-success" style="justify-content:center;" onclick="return confirm('Nommer ce membre responsable du siège ?')"><i class="fas fa-check"></i> Accepter</button>
                            <button name="action" value="rejeter" class="btn btn-danger" style="justify-content:center;" onclick="return confirm('Rejeter cette candidature ?')"><i class="fas fa-times"></i> Rejeter</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state card" style="padding:60px;"><i class="fas fa-briefcase" style="opacity:0.3;color:var(--primary);"></i><h3>Aucune candidature</h3><p>Aucun membre n'a postulé pour devenir responsable d'un siège.</p></div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
