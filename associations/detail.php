<?php
require_once __DIR__ . '/../includes/auth.php';
$db = getDB();
$id = $_GET['id'] ?? ($_POST['association_id'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
    $siege_id = $_POST['siege_id'] ?? '';
    $role_demande = $_POST['role_demande'] ?? 'membre';
    $message = trim($_POST['message'] ?? '');
    $uid = $_SESSION['user_id'];
    $document_path = null;

    if ($siege_id) {
        $existsM = $db->prepare("SELECT id FROM membre_association WHERE membre_id=? AND association_id=?");
        $existsM->execute([$uid, $id]);
        $existsC = $db->prepare("SELECT c.id FROM candidature_siege c JOIN siege s ON c.siege_id=s.id WHERE c.membre_id=? AND s.association_id=?");
        $existsC->execute([$uid, $id]);

        if ($existsM->fetch() || $existsC->fetch()) {
            flash('Vous avez déjà une demande ou vous êtes déjà membre de cette association.', 'error');
        } else {
            // Traitement de l'upload du document
            if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'])) {
                    $filename = uniqid() . '_' . time() . '.' . $ext;
                    $dest = __DIR__ . '/../uploads/candidatures/' . $filename;
                    if (!is_dir(__DIR__ . '/../uploads/candidatures/')) {
                        mkdir(__DIR__ . '/../uploads/candidatures/', 0777, true);
                    }
                    if (move_uploaded_file($_FILES['document']['tmp_name'], $dest)) {
                        $document_path = 'uploads/candidatures/' . $filename;
                    }
                }
            }

            if (!$document_path) {
                flash('Une pièce jointe valide (PDF, JPG, PNG) est obligatoire.', 'error');
            } else {
                if ($role_demande === 'responsable') {
                    $cid = uuid();
                    $db->prepare("INSERT INTO candidature_siege (id, membre_id, siege_id, message, document_path) VALUES (?, ?, ?, ?, ?)")->execute([$cid, $uid, $siege_id, $message, $document_path]);
                    flash('Votre candidature pour devenir responsable du siège a été soumise.', 'success');
                } else {
                    $mid = uuid();
                    $db->prepare("INSERT INTO membre_association (id, membre_id, association_id, siege_id, statut, document_path, message) VALUES (?, ?, ?, ?, 'en_attente', ?, ?)")->execute([$mid, $uid, $id, $siege_id, $document_path, $message]);
                    flash('Votre demande d\'adhésion au siège a été soumise. Elle est en attente de validation.', 'success');
                }
            }
        }
    }
    header('Location: ' . BASE_URL . 'associations/detail.php?id=' . $id);
    exit;
}

// Vérifier pour l'affichage du formulaire si le membre a déjà postulé
$dejaPostule = false;
if (isLoggedIn() && hasRole(['membre'])) {
    $uid = $_SESSION['user_id'];
    $existsM = $db->prepare("SELECT id FROM membre_association WHERE membre_id=? AND association_id=?");
    $existsM->execute([$uid, $id]);
    $existsC = $db->prepare("SELECT c.id FROM candidature_siege c JOIN siege s ON c.siege_id=s.id WHERE c.membre_id=? AND s.association_id=?");
    $existsC->execute([$uid, $id]);
    if ($existsM->fetch() || $existsC->fetch()) {
        $dejaPostule = true;
    }
}

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
                        <?php if (hasRole(['membre'])): ?>
                            <?php if ($dejaPostule): ?>
                                <div class="alert alert-info mb-20">
                                    <i class="fas fa-info-circle"></i> Vous avez déjà soumis une demande pour cette association. <br>
                                    <a href="<?= BASE_URL ?>candidatures/track.php" style="font-weight:bold;text-decoration:underline;">Suivre ma demande</a>
                                </div>
                                <hr style="border:none;border-top:1px solid var(--border);margin:20px 0;">
                            <?php else: ?>
                                <div style="margin-bottom:20px;">
                                    <h4 style="margin-bottom:10px;font-weight:700;"><i class="fas fa-handshake icon-primary-text"></i> Rejoindre cette association</h4>
                                    <form method="POST" enctype="multipart/form-data">
                                        <input type="hidden" name="association_id" value="<?= $assoc['id'] ?>">
                                        <div class="form-group" style="margin-bottom:10px;">
                                            <select name="siege_id" id="joinSiege" class="form-control" required style="padding:8px;" onchange="updateRoleOptions()">
                                                <option value="">-- Choisir un siège --</option>
                                                <?php foreach ($sieges as $s): ?>
                                                    <option value="<?= $s['id'] ?>" data-has-resp="<?= $s['president_siege_id'] ? '1' : '0' ?>">
                                                        <?= htmlspecialchars($s['nom'] . ' (' . $s['wilaya'] . ')') ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group" style="margin-bottom:10px;">
                                            <select name="role_demande" id="joinRole" class="form-control" required style="padding:8px;">
                                                <option value="membre">Membre bénévole</option>
                                                <option value="responsable" id="optResp" style="display:none;">Devenir Responsable du siège</option>
                                            </select>
                                        </div>
                                         <div class="form-group" id="messageGroup" style="display:block;margin-bottom:10px;">
                                             <textarea name="message" class="form-control" rows="2" placeholder="Message de motivation..."></textarea>
                                         </div>
                                        <div class="form-group" style="margin-bottom:10px;">
                                            <label style="font-size:0.85rem;">Pièce jointe (Obligatoire) <small>PDF, JPG, PNG</small></label>
                                            <input type="file" name="document" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required style="padding:5px;">
                                        </div>
                                        <button type="submit" class="btn btn-secondary w-100" style="justify-content:center;padding:10px;"><i class="fas fa-paper-plane"></i> Envoyer ma candidature</button>
                                    </form>
                                </div>
                                <hr style="border:none;border-top:1px solid var(--border);margin:20px 0;">
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if (count($sieges) > 0): ?>
                            <a href="<?= BASE_URL ?>dons/create.php?assoc=<?= $assoc['id'] ?>" class="btn btn-primary" style="width:100%;justify-content:center;margin-bottom:12px;"><i class="fas fa-donate"></i> Faire un don</a>
                            <a href="<?= BASE_URL ?>demandes/create.php?assoc=<?= $assoc['id'] ?>" class="btn btn-outline" style="width:100%;justify-content:center;"><i class="fas fa-hand-holding-heart"></i> Demander de l'aide</a>
                        <?php else: ?>
                            <div class="alert alert-warning" style="margin-top:14px;font-size:0.85rem;"><i class="fas fa-exclamation-triangle"></i> Cette association ne possède pas encore de siège pour recevoir des dons ou traiter des demandes d'aide.</div>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>register.php" class="btn btn-primary" style="width:100%;justify-content:center;"><i class="fas fa-user-plus"></i> Rejoindre Takaful</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
function updateRoleOptions() {
    const s = document.getElementById('joinSiege');
    const optResp = document.getElementById('optResp');
    const msg = document.getElementById('messageGroup');
    const role = document.getElementById('joinRole');
    
    if(s.selectedIndex > 0) {
        const hasResp = s.options[s.selectedIndex].getAttribute('data-has-resp') === '1';
        if(hasResp) {
            optResp.style.display = 'none';
            role.value = 'membre';
        } else {
            optResp.style.display = 'block';
        }
    } else {
        optResp.style.display = 'none';
    }
}
</script>
<style>@media(max-width:700px){.detail-grid{grid-template-columns:1fr!important;}}</style>
<?php include __DIR__ . '/../includes/footer.php'; ?>
