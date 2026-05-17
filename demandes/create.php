<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if (isAdmin()) { flash('Les administrateurs ne peuvent pas soumettre de demandes d\'aide.', 'error'); header('Location: ' . BASE_URL . 'admin/index.php'); exit; }
$db  = getDB();
$uid = $_SESSION['user_id'];

// Charger toutes les associations actives ayant au moins 1 siège actif
$associations = $db->query("SELECT DISTINCT a.id, a.nom FROM association a INNER JOIN siege s ON s.association_id=a.id AND s.statut='actif' WHERE a.statut='active' ORDER BY a.nom")->fetchAll();

// Sièges de l'association sélectionnée
$selectedAssoc = $_GET['assoc'] ?? ($_POST['association_id'] ?? '');
$sieges = [];
if ($selectedAssoc) {
    $stmtS = $db->prepare("SELECT id, nom, wilaya FROM siege WHERE association_id=? AND statut='actif' ORDER BY wilaya, nom");
    $stmtS->execute([$selectedAssoc]);
    $sieges = $stmtS->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sujet        = trim($_POST['sujet'] ?? '');
    $description  = trim($_POST['description'] ?? '');
    $type_aide    = $_POST['type_aide'] ?? 'autre';
    $association_id = $_POST['association_id'] ?? '';
    $siege_id     = $_POST['siege_id'] ?? '';

    $errors = [];
    if (empty($sujet))         $errors[] = 'Le sujet est obligatoire.';
    if (empty($association_id)) $errors[] = 'Veuillez choisir une association.';
    if (empty($siege_id))       $errors[] = 'Veuillez choisir un siège.';

    // Gestion pièce jointe (optionnelle)
    $documentPath = null;
    if (!empty($_FILES['document']['name']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['application/pdf','image/jpeg','image/png','image/jpg'];
        $fileType = mime_content_type($_FILES['document']['tmp_name']);
        if (!in_array($fileType, $allowedTypes)) {
            $errors[] = 'Format de pièce jointe non accepté (PDF, JPG, PNG uniquement).';
        } elseif ($_FILES['document']['size'] > 5 * 1024 * 1024) {
            $errors[] = 'La pièce jointe ne doit pas dépasser 5 Mo.';
        } else {
            $uploadDir = __DIR__ . '/../uploads/demandes/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $ext      = pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION);
            $fileName = uuid() . '.' . strtolower($ext);
            move_uploaded_file($_FILES['document']['tmp_name'], $uploadDir . $fileName);
            $documentPath = 'uploads/demandes/' . $fileName;
        }
    }

    if ($errors) {
        foreach ($errors as $e) flash($e, 'error');
        header('Location: ' . BASE_URL . 'demandes/create.php?assoc=' . urlencode($association_id)); exit;
    }

    $id = uuid();
    $db->prepare("INSERT INTO demande_aide (id,demandeur_id,siege_id,sujet,description,type_aide,document_path,statut) VALUES (?,?,?,?,?,?,?,'soumise')")
       ->execute([$id, $uid, $siege_id, $sujet, $description, $type_aide, $documentPath]);
    flash('Votre demande d\'aide a été soumise avec succès. Nous vous contacterons bientôt.', 'success');
    header('Location: ' . BASE_URL . 'demandes/track.php?id=' . $id); exit;
}

$pageTitle = 'Soumettre une demande d\'aide';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div class="breadcrumb"><a href="<?= BASE_URL ?>index.php">Accueil</a> / <a href="<?= BASE_URL ?>demandes/index.php">Demandes</a> / Nouvelle</div>
    <h1><i class="fas fa-hand-holding-heart"></i> Soumettre une Demande d'Aide</h1>
    <p>Décrivez votre situation, nous sommes là pour vous aider</p>
</div>
<div class="container" style="padding:40px 20px;">
    <div class="form-card" style="max-width:600px;">
        <div class="form-title">Formulaire de demande d'aide</div>
        <div style="padding:14px;background:#fff3cd;border-radius:8px;margin-bottom:20px;font-size:0.88rem;color:#856404;">
            <i class="fas fa-shield-alt"></i> Vos informations restent confidentielles et ne seront partagées qu'avec les bénévoles assignés à votre demande.
        </div>
        <form method="POST" enctype="multipart/form-data">

            <!-- Type d'aide -->
            <div class="form-group">
                <label>Type d'aide nécessaire <span>*</span></label>
                <select name="type_aide" class="form-control" required>
                    <option value="financiere" <?= ($_POST['type_aide'] ?? '') === 'financiere' ? 'selected' : '' ?>>💰 Aide financière</option>
                    <option value="medicale"   <?= ($_POST['type_aide'] ?? '') === 'medicale'   ? 'selected' : '' ?>>🏥 Aide médicale</option>
                    <option value="alimentaire"<?= ($_POST['type_aide'] ?? '') === 'alimentaire'? 'selected' : '' ?>>🍞 Aide alimentaire</option>
                    <option value="autre"      <?= ($_POST['type_aide'] ?? 'autre') === 'autre' ? 'selected' : '' ?>>📋 Autre</option>
                </select>
            </div>

            <!-- Sujet -->
            <div class="form-group">
                <label>Sujet de la demande <span>*</span></label>
                <input type="text" name="sujet" class="form-control" required placeholder="Résumez brièvement votre besoin..." maxlength="255" value="<?= htmlspecialchars($_POST['sujet'] ?? '') ?>">
            </div>

            <!-- Description -->
            <div class="form-group">
                <label>Description détaillée</label>
                <textarea name="description" class="form-control" rows="5" placeholder="Expliquez votre situation, ce dont vous avez besoin et toute information utile..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>

            <!-- Association (obligatoire) -->
            <div class="form-group">
                <label>Association <span>*</span></label>
                <select name="association_id" id="selAssoc" class="form-control" required onchange="loadSiegesDA(this.value)">
                    <option value="">-- Choisir une association --</option>
                    <?php foreach ($associations as $a): ?>
                    <option value="<?= $a['id'] ?>" <?= $selectedAssoc === $a['id'] ? 'selected' : '' ?>><?= htmlspecialchars($a['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Siège (obligatoire, chargé dynamiquement) -->
            <div class="form-group">
                <label>Siège le plus proche <span>*</span></label>
                <select name="siege_id" id="selSiege" class="form-control" required>
                    <option value="">-- Choisir d'abord une association --</option>
                    <?php foreach ($sieges as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= ($_POST['siege_id'] ?? '') === $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['nom']) ?> — <?= htmlspecialchars($s['wilaya']) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="form-hint">Choisir un siège proche de chez vous accélérera le traitement de votre demande.</p>
            </div>

            <!-- Pièce jointe (optionnelle) -->
            <div class="form-group">
                <label>Pièce jointe (optionnelle)</label>
                <div style="border:2px dashed var(--border);border-radius:var(--radius-sm);padding:18px;text-align:center;background:var(--bg);cursor:pointer;" onclick="document.getElementById('pjFile').click()">
                    <i class="fas fa-paperclip" style="font-size:1.5rem;color:var(--primary);margin-bottom:6px;display:block;"></i>
                    <p style="color:var(--text-light);font-size:0.88rem;margin:0;" id="pjLabel">Cliquez pour joindre un document (PDF, JPG, PNG — max 5 Mo)</p>
                </div>
                <input type="file" id="pjFile" name="document" accept=".pdf,.jpg,.jpeg,.png" style="display:none;" onchange="document.getElementById('pjLabel').textContent = this.files[0]?.name || 'Aucun fichier'">
                <p class="form-hint">Ordonnance, facture, justificatif... Ce document aide à traiter votre demande plus rapidement.</p>
            </div>

            <div class="form-actions">
                <a href="<?= BASE_URL ?>demandes/index.php" class="btn btn-outline">Annuler</a>
                <button type="submit" class="btn btn-secondary"><i class="fas fa-paper-plane"></i> Soumettre la demande</button>
            </div>
        </form>
    </div>
</div>
<script>
const BASE_URL_DA = '<?= BASE_URL ?>';

async function loadSiegesDA(assocId) {
    const sel = document.getElementById('selSiege');
    sel.innerHTML = '<option value="">Chargement...</option>';
    if (!assocId) { sel.innerHTML = '<option value="">-- Choisir d\'abord une association --</option>'; return; }
    try {
        const r    = await fetch(BASE_URL_DA + 'api/get_sieges.php?association_id=' + encodeURIComponent(assocId));
        const data = await r.json();
        sel.innerHTML = '<option value="">-- Choisir un siège --</option>';
        data.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = s.nom + ' — ' + s.wilaya;
            sel.appendChild(opt);
        });
    } catch(e) {
        sel.innerHTML = '<option value="">Erreur de chargement</option>';
    }
}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
