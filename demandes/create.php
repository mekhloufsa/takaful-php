<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if (isAdmin()) { flash('Les administrateurs ne peuvent pas soumettre de demandes d\'aide.', 'error'); header('Location: ' . BASE_URL . 'admin/index.php'); exit; }
$db = getDB();
$uid = $_SESSION['user_id'];
$sieges = $db->query("SELECT s.*, a.nom as assoc_nom FROM siege s JOIN association a ON s.association_id=a.id WHERE s.statut='actif' ORDER BY s.wilaya, s.nom")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sujet = trim($_POST['sujet'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $type_aide = $_POST['type_aide'] ?? 'autre';
    $siege_id = $_POST['siege_id'] ?? null;

    if (empty($sujet)) { flash('Le sujet est obligatoire.', 'error'); header('Location: ' . BASE_URL . 'demandes/create.php'); exit; }
    $id = uuid();
    $db->prepare("INSERT INTO demande_aide (id,demandeur_id,siege_id,sujet,description,type_aide,statut) VALUES (?,?,?,?,?,?,'soumise')")
       ->execute([$id, $uid, $siege_id ?: null, $sujet, $description, $type_aide]);
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
    <div class="form-card" style="max-width:580px;">
        <div class="form-title">Formulaire de demande d'aide</div>
        <div style="padding:14px;background:#fff3cd;border-radius:8px;margin-bottom:20px;font-size:0.88rem;color:#856404;">
            <i class="fas fa-shield-alt"></i> Vos informations restent confidentielles et ne seront partagées qu'avec les bénévoles assignés à votre demande.
        </div>
        <form method="POST">
            <div class="form-group">
                <label>Type d'aide nécessaire <span>*</span></label>
                <select name="type_aide" class="form-control" required>
                    <option value="financiere">💰 Aide financière</option>
                    <option value="medicale">🏥 Aide médicale</option>
                    <option value="alimentaire">🍞 Aide alimentaire</option>
                    <option value="autre">📋 Autre</option>
                </select>
            </div>
            <div class="form-group">
                <label>Sujet de la demande <span>*</span></label>
                <input type="text" name="sujet" class="form-control" required placeholder="Résumez brièvement votre besoin..." maxlength="255" value="<?= htmlspecialchars($_POST['sujet'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Description détaillée</label>
                <textarea name="description" class="form-control" rows="5" placeholder="Expliquez votre situation, ce dont vous avez besoin et toute information utile..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>Siège le plus proche</label>
                <select name="siege_id" class="form-control">
                    <option value="">-- Choisir un siège (optionnel) --</option>
                    <?php foreach ($sieges as $s): ?>
                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nom']) ?> — <?= htmlspecialchars($s['wilaya']) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="form-hint">Choisir un siège proche de chez vous accélérera le traitement de votre demande.</p>
            </div>
            <div class="form-actions">
                <a href="<?= BASE_URL ?>demandes/index.php" class="btn btn-outline">Annuler</a>
                <button type="submit" class="btn btn-secondary"><i class="fas fa-paper-plane"></i> Soumettre la demande</button>
            </div>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
