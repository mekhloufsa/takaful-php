<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole(['president_association']);
$db = getDB();
$uid = $_SESSION['user_id'];
$id = $_GET['id'] ?? '';
$siege = $db->prepare("SELECT s.*, a.president_id FROM siege s JOIN association a ON s.association_id=a.id WHERE s.id=? AND a.president_id=?");
$siege->execute([$id, $uid]); $siege = $siege->fetch();
if (!$siege) { flash('Siège introuvable.', 'error'); header('Location: ' . BASE_URL . 'sieges/index.php'); exit; }
$membres = $db->prepare("SELECT m.* FROM membre m JOIN membre_association ma ON m.id=ma.membre_id WHERE ma.siege_id=? AND ma.statut='actif'"); 
$membres->execute([$id]); 
$membres = $membres->fetchAll();
$wilayas = ['Adrar','Chlef','Laghouat','Oum El Bouaghi','Batna','Béjaïa','Biskra','Béchar','Blida','Bouira','Tamanrasset','Tébessa','Tlemcen','Tiaret','Tizi Ouzou','Alger','Djelfa','Jijel','Sétif','Saïda','Skikda','Sidi Bel Abbès','Annaba','Guelma','Constantine','Médéa','Mostaganem','M\'Sila','Mascara','Ouargla','Oran','El Bayadh','Illizi','Bordj Bou Arréridj','Boumerdès','El Tarf','Tindouf','Tissemsilt','El Oued','Khenchela','Souk Ahras','Tipaza','Mila','Aïn Defla','Naâma','Aïn Témouchent','Ghardaïa','Relizane'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $wilaya = trim($_POST['wilaya'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $president_siege_id = $_POST['president_siege_id'] ?? null;
    $statut = $_POST['statut'] ?? 'actif';
    if (empty($nom) || empty($wilaya)) { flash('Champs obligatoires manquants.', 'error'); header('Location: ' . BASE_URL . 'sieges/edit.php?id=' . $id); exit; }
    $db->prepare("UPDATE siege SET nom=?,wilaya=?,adresse=?,president_siege_id=?,statut=? WHERE id=?")->execute([$nom,$wilaya,$adresse,$president_siege_id?:null,$statut,$id]);
    if ($president_siege_id) $db->prepare("UPDATE membre SET role='president_siege' WHERE id=?")->execute([$president_siege_id]);
    flash('Siège modifié avec succès.', 'success');
    header('Location: ' . BASE_URL . 'sieges/index.php'); exit;
}
$pageTitle = 'Modifier le siège';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header"><h1><i class="fas fa-edit"></i> Modifier le Siège</h1></div>
<div class="container py-50">
    <div class="form-card" style="max-width:580px;">
        <div class="form-title text-primary font-bold">Modification — <?= htmlspecialchars($siege['nom']) ?></div>
        <form method="POST">
            <div class="form-group"><label>Nom <span>*</span></label><input type="text" name="nom" class="form-control" required value="<?= htmlspecialchars($_POST['nom'] ?? $siege['nom']) ?>"></div>
            <div class="form-group"><label>Wilaya <span>*</span></label>
                <select name="wilaya" class="form-control" required>
                    <?php foreach ($wilayas as $w): ?><option value="<?= $w ?>" <?= ($_POST['wilaya']??$siege['wilaya'])===$w?'selected':'' ?>><?= $w ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Adresse</label><textarea name="adresse" class="form-control"><?= htmlspecialchars($_POST['adresse'] ?? $siege['adresse'] ?? '') ?></textarea></div>
            <div class="form-group"><label>Président de siège</label>
                <select name="president_siege_id" class="form-control">
                    <option value="">-- Aucun --</option>
                    <?php foreach ($membres as $m): ?><option value="<?= $m['id'] ?>" <?= ($_POST['president_siege_id']??$siege['president_siege_id'])===$m['id']?'selected':'' ?>><?= htmlspecialchars($m['nom'].' '.$m['prenom']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Statut</label>
                <select name="statut" class="form-control">
                    <option value="actif" <?= ($_POST['statut']??$siege['statut'])==='actif'?'selected':'' ?>>Actif</option>
                    <option value="inactif" <?= ($_POST['statut']??$siege['statut'])==='inactif'?'selected':'' ?>>Inactif</option>
                </select>
            </div>
            <div class="form-actions"><a href="<?= BASE_URL ?>sieges/index.php" class="btn btn-outline">Annuler</a><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Sauvegarder</button></div>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
