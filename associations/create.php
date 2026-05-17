<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if (isAdmin()) { flash('Les administrateurs ne peuvent pas créer d\'associations.', 'error'); header('Location: ' . BASE_URL . 'admin/index.php'); exit; }
$db  = getDB();
$uid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom         = trim($_POST['nom'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($nom)) {
        flash('Le nom de l\'association est obligatoire.', 'error');
        header('Location: ' . BASE_URL . 'associations/create.php'); exit;
    }

    // Vérifier pièce jointe
    if (empty($_FILES['document']['name']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
        flash('La pièce jointe (document officiel) est obligatoire.', 'error');
        header('Location: ' . BASE_URL . 'associations/create.php'); exit;
    }

    // Vérifier une seule demande en attente ou active
    $exists = $db->prepare("SELECT id FROM association WHERE president_id=? AND statut IN ('en_attente','active')");
    $exists->execute([$uid]);
    if ($exists->fetch()) {
        flash('Vous avez déjà une association active ou en attente de validation.', 'error');
        header('Location: ' . BASE_URL . 'associations/create.php'); exit;
    }

    // Upload du document
    $allowedTypes = ['application/pdf','image/jpeg','image/png','image/jpg'];
    $fileType     = mime_content_type($_FILES['document']['tmp_name']);
    if (!in_array($fileType, $allowedTypes)) {
        flash('Format non accepté. Utilisez PDF, JPG ou PNG.', 'error');
        header('Location: ' . BASE_URL . 'associations/create.php'); exit;
    }
    if ($_FILES['document']['size'] > 5 * 1024 * 1024) {
        flash('Le fichier ne doit pas dépasser 5 Mo.', 'error');
        header('Location: ' . BASE_URL . 'associations/create.php'); exit;
    }

    $uploadDir  = __DIR__ . '/../uploads/associations/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $ext        = pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION);
    $fileName   = uuid() . '.' . strtolower($ext);
    move_uploaded_file($_FILES['document']['tmp_name'], $uploadDir . $fileName);
    $documentPath = 'uploads/associations/' . $fileName;

    // Insérer la demande — statut en_attente, SANS changer le rôle
    $id = uuid();
    $db->prepare("INSERT INTO association (id,nom,description,document_path,president_id,statut) VALUES (?,?,?,?,?,'en_attente')")
       ->execute([$id, $nom, $description, $documentPath, $uid]);

    flash('Votre demande de création d\'association a été soumise avec succès. Un administrateur examinera votre dossier.', 'success');
    header('Location: ' . BASE_URL . 'dashboard.php'); exit;
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
    <div class="form-card" style="max-width:600px;">
        <div class="form-title">Demande de création d'association</div>
        <div style="padding:14px;background:var(--primary-light);border-radius:8px;margin-bottom:20px;font-size:0.88rem;color:var(--primary);">
            <i class="fas fa-info-circle"></i> Votre demande sera examinée par un administrateur avant activation. Vous devez joindre un document officiel prouvant la légitimité de l'association.
        </div>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Nom de l'association <span>*</span></label>
                <input type="text" name="nom" class="form-control" required placeholder="Ex: Nour El Amal" value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Description et objectifs</label>
                <textarea name="description" class="form-control" rows="5" placeholder="Décrivez les objectifs et les activités de votre association..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>Document officiel (agrément, statuts, etc.) <span>*</span></label>
                <div style="border:2px dashed var(--border);border-radius:var(--radius-sm);padding:20px;text-align:center;background:var(--bg);cursor:pointer;" onclick="document.getElementById('docFile').click()">
                    <i class="fas fa-file-upload" style="font-size:2rem;color:var(--primary);margin-bottom:8px;display:block;"></i>
                    <p style="color:var(--text-light);font-size:0.9rem;margin:0;" id="docLabel">Cliquez pour choisir un fichier (PDF, JPG, PNG — max 5 Mo)</p>
                </div>
                <input type="file" id="docFile" name="document" accept=".pdf,.jpg,.jpeg,.png" required style="display:none;" onchange="document.getElementById('docLabel').textContent = this.files[0]?.name || 'Aucun fichier'">
                <p class="form-hint"><i class="fas fa-lock"></i> Ce document reste confidentiel et n'est visible que par les administrateurs.</p>
            </div>
            <div class="form-actions">
                <a href="<?= BASE_URL ?>associations/index.php" class="btn btn-outline">Annuler</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Soumettre la demande</button>
            </div>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
