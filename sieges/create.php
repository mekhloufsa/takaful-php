<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole(['president_association']);
$db = getDB();
$uid = $_SESSION['user_id'];
$assoc = $db->prepare("SELECT * FROM association WHERE president_id=? AND statut='active'"); $assoc->execute([$uid]); $assoc = $assoc->fetch();
if (!$assoc) { flash('Association non active.', 'error'); header('Location: ' . BASE_URL . 'dashboard.php'); exit; }
$membres = $db->prepare("SELECT m.* FROM membre m JOIN membre_association ma ON m.id=ma.membre_id WHERE ma.association_id=? AND ma.statut='actif' AND m.role IN ('membre','membre_association')"); $membres->execute([$assoc['id']]); $membres = $membres->fetchAll();
$wilayas = ['Adrar','Chlef','Laghouat','Oum El Bouaghi','Batna','Béjaïa','Biskra','Béchar','Blida','Bouira','Tamanrasset','Tébessa','Tlemcen','Tiaret','Tizi Ouzou','Alger','Djelfa','Jijel','Sétif','Saïda','Skikda','Sidi Bel Abbès','Annaba','Guelma','Constantine','Médéa','Mostaganem','M\'Sila','Mascara','Ouargla','Oran','El Bayadh','Illizi','Bordj Bou Arréridj','Boumerdès','El Tarf','Tindouf','Tissemsilt','El Oued','Khenchela','Souk Ahras','Tipaza','Mila','Aïn Defla','Naâma','Aïn Témouchent','Ghardaïa','Relizane'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $wilaya = trim($_POST['wilaya'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $president_siege_id = $_POST['president_siege_id'] ?? null;
    if (empty($nom) || empty($wilaya)) { flash('Nom et wilaya sont obligatoires.', 'error'); header('Location: ' . BASE_URL . 'sieges/create.php'); exit; }
    $id = uuid();
    $db->prepare("INSERT INTO siege (id,nom,wilaya,adresse,association_id,president_siege_id,statut) VALUES (?,?,?,?,?,?,'actif')")->execute([$id,$nom,$wilaya,$adresse,$assoc['id'],$president_siege_id?:null]);
    if ($president_siege_id) {
        $db->prepare("UPDATE membre SET role='president_siege' WHERE id=?")->execute([$president_siege_id]);
    }
    flash('Siège "'.$nom.'" créé avec succès.', 'success');
    header('Location: ' . BASE_URL . 'sieges/index.php'); exit;
}
$pageTitle = 'Créer un siège';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div class="breadcrumb"><a href="<?= BASE_URL ?>sieges/index.php">Sièges</a> / Créer</div>
    <h1><i class="fas fa-plus-circle"></i> Créer un Siège</h1>
</div>
<div class="container py-50">
    <div class="form-card" style="max-width:580px;">
        <div class="form-title text-primary font-bold">Nouveau Siège — <?= htmlspecialchars($assoc['nom']) ?></div>
        <form method="POST">
            <div class="form-group"><label>Nom du siège <span>*</span></label><input type="text" name="nom" class="form-control" required placeholder="Ex: Siège d'Alger Centre" value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>"></div>
            <div class="form-group"><label>Wilaya <span>*</span></label>
                <select name="wilaya" class="form-control" required>
                    <option value="">-- Choisir --</option>
                    <?php foreach ($wilayas as $w): ?><option value="<?= $w ?>" <?= ($_POST['wilaya']??'')===$w?'selected':'' ?>><?= $w ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Adresse complète</label><textarea name="adresse" class="form-control" rows="2" placeholder="Rue, commune..."><?= htmlspecialchars($_POST['adresse'] ?? '') ?></textarea></div>
            <div class="form-group"><label>Président de siège</label>
                <select name="president_siege_id" class="form-control">
                    <option value="">-- Choisir un responsable (optionnel) --</option>
                    <?php foreach ($membres as $m): ?><option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nom'].' '.$m['prenom']) ?> (<?= htmlspecialchars($m['wilaya'] ?? '—') ?>)</option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-actions"><a href="<?= BASE_URL ?>sieges/index.php" class="btn btn-outline">Annuler</a><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Créer le siège</button></div>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
