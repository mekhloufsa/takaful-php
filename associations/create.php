<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if (isAdmin()) { flash('Les administrateurs ne peuvent pas créer d\'associations.', 'error'); header('Location: ' . BASE_URL . 'admin/index.php'); exit; }
$db = getDB();
$uid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $description = trim($_POST['description'] ?? '');
    if (empty($nom)) { flash('Le nom est obligatoire.', 'error'); header('Location: ' . BASE_URL . 'associations/create.php'); exit; }
    $exists = $db->prepare("SELECT id FROM association WHERE president_id=? AND statut IN ('en_attente','active')"); $exists->execute([$uid]);
    if ($exists->fetch()) { flash('Vous avez déjà une association active ou en attente de validation.', 'error'); header('Location: ' . BASE_URL . 'associations/create.php'); exit; }
    $id = uuid();
    $db->prepare("INSERT INTO association (id,nom,description,president_id,statut) VALUES (?,?,?,?,'en_attente')")->execute([$id, $nom, $description, $uid]);
    $db->prepare("UPDATE membre SET role='president_association' WHERE id=?")->execute([$uid]);
    $_SESSION['role'] = 'president_association';
    $updatedUser = $db->prepare("SELECT * FROM membre WHERE id=?"); $updatedUser->execute([$uid]); $_SESSION['user'] = $updatedUser->fetch();
    flash('Votre association "'.$nom.'" a été créée et est en attente de validation par un administrateur.', 'success');
    header('Location: ' . BASE_URL . 'president_association/dashboard.php'); exit;
}

$pageTitle = 'Créer une association';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div class="breadcrumb"><a href="<?= BASE_URL ?>index.php">Accueil</a> / <a href="<?= BASE_URL ?>associations/index.php">Associations</a> / Créer</div>
    <h1><i class="fas fa-plus-circle"></i> Créer une Association</h1>
    <p>Lancez votre initiative humanitaire</p>
</div>
<div class="container" style="padding:40px 20px;">
    <div class="form-card" style="max-width:580px;">
        <div class="form-title">Nouvelle Association</div>
        <div style="padding:14px;background:var(--primary-light);border-radius:8px;margin-bottom:20px;font-size:0.88rem;color:var(--primary);">
            <i class="fas fa-info-circle"></i> Votre association sera soumise à validation par un administrateur avant d'être visible sur la plateforme.
        </div>
        <form method="POST">
            <div class="form-group">
                <label>Nom de l'association <span>*</span></label>
                <input type="text" name="nom" class="form-control" required placeholder="Ex: Nour El Amal" value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Description et objectifs</label>
                <textarea name="description" class="form-control" rows="5" placeholder="Décrivez les objectifs et les activités de votre association..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>
            <div class="form-actions">
                <a href="<?= BASE_URL ?>associations/index.php" class="btn btn-outline">Annuler</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Soumettre la demande</button>
            </div>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
