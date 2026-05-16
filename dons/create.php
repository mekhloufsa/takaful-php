<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if (isAdmin()) { flash('Les administrateurs ne peuvent pas effectuer de dons.', 'error'); header('Location: ' . BASE_URL . 'admin/index.php'); exit; }
$db = getDB();
$uid = $_SESSION['user_id'];

$sieges = $db->query("SELECT s.*, a.nom as assoc_nom FROM siege s JOIN association a ON s.association_id=a.id WHERE s.statut='actif' ORDER BY s.wilaya, s.nom")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    $categorie = trim($_POST['categorie'] ?? '');
    $montant = (float)($_POST['montant'] ?? 0);
    $siege_id = $_POST['siege_id'] ?? null;
    $description = trim($_POST['description'] ?? '');

    if (empty($type)) { flash('Veuillez choisir le type de don.', 'error'); header('Location: ' . BASE_URL . 'dons/create.php'); exit; }

    $id = uuid();
    $db->prepare("INSERT INTO don (id,donateur_id,siege_id,type,categorie,montant,description,statut) VALUES (?,?,?,?,?,?,?,'en_attente')")
       ->execute([$id, $uid, $siege_id ?: null, $type, $categorie, $montant, $description]);
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
    <div class="form-card" style="max-width:580px;">
        <div class="form-title">Formulaire de don</div>
        <form method="POST">
            <div class="form-group">
                <label>Type de don <span>*</span></label>
                <select name="type" id="typeDon" class="form-control" required onchange="toggleFields()">
                    <option value="">-- Choisir le type --</option>
                    <option value="financier">💰 Don financier</option>
                    <option value="materiel">📦 Don matériel</option>
                </select>
            </div>
            <div id="fieldMontant" style="display:none;">
                <div class="form-group">
                    <label>Montant (DA) <span>*</span></label>
                    <input type="number" name="montant" class="form-control" min="0" step="100" placeholder="Ex: 5000">
                </div>
            </div>
            <div class="form-group">
                <label>Catégorie / Nature du don</label>
                <input type="text" name="categorie" class="form-control" placeholder="Ex: Alimentation, Vêtements, Médicaments...">
            </div>
            <div class="form-group">
                <label>Siège bénéficiaire</label>
                <select name="siege_id" class="form-control">
                    <option value="">-- Choisir un siège (optionnel) --</option>
                    <?php foreach ($sieges as $s): ?>
                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nom']) ?> — <?= htmlspecialchars($s['wilaya']) ?> (<?= htmlspecialchars($s['assoc_nom']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Description / Notes</label>
                <textarea name="description" class="form-control" placeholder="Décrivez votre don..."></textarea>
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
function toggleFields() {
    const type = document.getElementById('typeDon').value;
    document.getElementById('fieldMontant').style.display = type === 'financier' ? 'block' : 'none';
}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
