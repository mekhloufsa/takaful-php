<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if (isAdmin()) { flash('Les administrateurs ne peuvent pas effectuer de dons.', 'error'); header('Location: ' . BASE_URL . 'admin/index.php'); exit; }
$db  = getDB();
$uid = $_SESSION['user_id'];

// Charger toutes les associations actives ayant au moins 1 siège actif
$associations = $db->query("SELECT DISTINCT a.id, a.nom FROM association a INNER JOIN siege s ON s.association_id=a.id AND s.statut='actif' WHERE a.statut='active' ORDER BY a.nom")->fetchAll();

// Sièges de l'association sélectionnée (AJAX ou pré-sélection)
$selectedAssoc = $_GET['assoc'] ?? ($_POST['association_id'] ?? '');
$sieges = [];
if ($selectedAssoc) {
    $stmtS = $db->prepare("SELECT id, nom, wilaya FROM siege WHERE association_id=? AND statut='actif' ORDER BY wilaya, nom");
    $stmtS->execute([$selectedAssoc]);
    $sieges = $stmtS->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type          = $_POST['type'] ?? '';
    $montant       = (float)($_POST['montant'] ?? 0);
    $categorie     = trim($_POST['categorie'] ?? '');
    $association_id= $_POST['association_id'] ?? '';
    $siege_id      = $_POST['siege_id'] ?? '';
    $description   = trim($_POST['description'] ?? '');

    $errors = [];
    if (empty($type))          $errors[] = 'Veuillez choisir le type de don.';
    if (empty($association_id)) $errors[] = 'Veuillez choisir une association.';
    if (empty($siege_id))       $errors[] = 'Veuillez choisir un siège bénéficiaire.';
    if ($type === 'financier' && $montant <= 0) $errors[] = 'Veuillez indiquer un montant valide.';

    if ($errors) {
        foreach ($errors as $e) flash($e, 'error');
        header('Location: ' . BASE_URL . 'dons/create.php?assoc=' . urlencode($association_id)); exit;
    }

    // Pour un don financier, la catégorie n'est pas pertinente
    if ($type === 'financier') $categorie = '';

    $id = uuid();
    $db->prepare("INSERT INTO don (id,donateur_id,association_id,siege_id,type,categorie,montant,description,statut) VALUES (?,?,?,?,?,?,?,?,'en_attente')")
       ->execute([$id, $uid, $association_id, $siege_id, $type, $categorie, $montant, $description]);
    flash('Votre don a été enregistré avec succès. Merci pour votre générosité !', 'success');
    header('Location: ' . BASE_URL . 'dons/track.php?id=' . $id); exit;
}

$pageTitle = 'Effectuer un don';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div class="breadcrumb"><a href="<?= BASE_URL ?>index.php">Accueil</a> / <a href="<?= BASE_URL ?>dons/index.php">Mes dons</a> / Nouveau don</div>
    <h1><i class="fas fa-donate"></i> Effectuer un Don</h1>
    <p>Votre générosité fait la différence</p>
</div>
<div class="container" style="padding:40px 20px;">
    <div class="form-card" style="max-width:600px;">
        <div class="form-title">Formulaire de don</div>
        <form method="POST" id="formDon">
            <!-- 1. Type de don -->
            <div class="form-group">
                <label>Type de don <span>*</span></label>
                <select name="type" id="typeDon" class="form-control" required onchange="toggleDonFields()">
                    <option value="">-- Choisir le type --</option>
                    <option value="financier" <?= ($_POST['type'] ?? '') === 'financier' ? 'selected' : '' ?>>💰 Don financier</option>
                    <option value="materiel"  <?= ($_POST['type'] ?? '') === 'materiel'  ? 'selected' : '' ?>>📦 Don matériel</option>
                </select>
            </div>

            <!-- 2. Montant (si financier) -->
            <div id="fieldMontant" style="display:none;">
                <div class="form-group">
                    <label>Montant (DA) <span>*</span></label>
                    <input type="number" name="montant" class="form-control" min="100" step="100" placeholder="Ex: 5000" value="<?= htmlspecialchars($_POST['montant'] ?? '') ?>">
                </div>
            </div>

            <!-- 3. Catégorie / Nature (seulement pour matériel) -->
            <div id="fieldCategorie" style="display:none;">
                <div class="form-group">
                    <label>Catégorie / Nature du don matériel</label>
                    <input type="text" name="categorie" class="form-control" placeholder="Ex: Vêtements, Nourriture, Médicaments..." value="<?= htmlspecialchars($_POST['categorie'] ?? '') ?>">
                </div>
            </div>

            <!-- 4. Choix de l'association (obligatoire) -->
            <div class="form-group">
                <label>Association bénéficiaire <span>*</span></label>
                <select name="association_id" id="selAssoc" class="form-control" required onchange="loadSieges(this.value)">
                    <option value="">-- Choisir une association --</option>
                    <?php foreach ($associations as $a): ?>
                    <option value="<?= $a['id'] ?>" <?= $selectedAssoc === $a['id'] ? 'selected' : '' ?>><?= htmlspecialchars($a['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- 5. Siège (obligatoire, chargé dynamiquement) -->
            <div class="form-group">
                <label>Siège bénéficiaire <span>*</span></label>
                <select name="siege_id" id="selSiege" class="form-control" required>
                    <option value="">-- Choisir d'abord une association --</option>
                    <?php foreach ($sieges as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= ($_POST['siege_id'] ?? '') === $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['nom']) ?> — <?= htmlspecialchars($s['wilaya']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- 6. Description -->
            <div class="form-group">
                <label>Description / Notes</label>
                <textarea name="description" class="form-control" rows="3" placeholder="Informations complémentaires sur votre don..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>

            <div style="padding:14px;background:var(--primary-light);border-radius:8px;margin-bottom:20px;font-size:0.88rem;color:var(--primary);">
                <i class="fas fa-info-circle"></i> Votre don sera examiné par le responsable du siège. Vous recevrez une notification de confirmation.
            </div>
            <div class="form-actions">
                <a href="<?= BASE_URL ?>dons/index.php" class="btn btn-outline">Annuler</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Soumettre le don</button>
            </div>
        </form>
    </div>
</div>
<script>
const BASE_URL = '<?= BASE_URL ?>';

function toggleDonFields() {
    const type = document.getElementById('typeDon').value;
    document.getElementById('fieldMontant').style.display   = (type === 'financier') ? 'block' : 'none';
    document.getElementById('fieldCategorie').style.display = (type === 'materiel')  ? 'block' : 'none';
}

async function loadSieges(assocId) {
    const sel = document.getElementById('selSiege');
    sel.innerHTML = '<option value="">Chargement...</option>';
    if (!assocId) { sel.innerHTML = '<option value="">-- Choisir d\'abord une association --</option>'; return; }
    try {
        const r = await fetch(BASE_URL + 'api/get_sieges.php?association_id=' + encodeURIComponent(assocId));
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

// Initialisation
toggleDonFields();
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
