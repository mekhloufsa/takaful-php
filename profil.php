<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
$db = getDB();
$uid = $_SESSION['user_id'];
$user = $db->prepare("SELECT * FROM membre WHERE id=?"); $user->execute([$uid]); $user = $user->fetch();

$wilayas = ['Adrar','Chlef','Laghouat','Oum El Bouaghi','Batna','Béjaïa','Biskra','Béchar','Blida','Bouira','Tamanrasset','Tébessa','Tlemcen','Tiaret','Tizi Ouzou','Alger','Djelfa','Jijel','Sétif','Saïda','Skikda','Sidi Bel Abbès','Annaba','Guelma','Constantine','Médéa','Mostaganem','M\'Sila','Mascara','Ouargla','Oran','El Bayadh','Illizi','Bordj Bou Arréridj','Boumerdès','El Tarf','Tindouf','Tissemsilt','El Oued','Khenchela','Souk Ahras','Tipaza','Mila','Aïn Defla','Naâma','Aïn Témouchent','Ghardaïa','Relizane'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $wilaya = trim($_POST['wilaya'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if (!empty($password)) {
        if ($password !== $confirm) { flash('Les mots de passe ne correspondent pas.', 'error'); header('Location: ' . BASE_URL . 'profil.php'); exit; }
        if (strlen($password) < 6) { flash('Mot de passe trop court.', 'error'); header('Location: ' . BASE_URL . 'profil.php'); exit; }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $db->prepare("UPDATE membre SET nom=?,prenom=?,telephone=?,wilaya=?,mot_de_passe=? WHERE id=?")->execute([$nom,$prenom,$telephone,$wilaya,$hash,$uid]);
    } else {
        $db->prepare("UPDATE membre SET nom=?,prenom=?,telephone=?,wilaya=? WHERE id=?")->execute([$nom,$prenom,$telephone,$wilaya,$uid]);
    }
    $updatedUser = $db->prepare("SELECT * FROM membre WHERE id=?"); $updatedUser->execute([$uid]); $_SESSION['user'] = $updatedUser->fetch();
    flash('Profil mis à jour avec succès.', 'success');
    header('Location: ' . BASE_URL . 'profil.php'); exit;
}

$pageTitle = 'Mon Profil';
include __DIR__ . '/includes/header.php';
?>
<div class="page-header"><h1><i class="fas fa-user"></i> Mon Profil</h1></div>
<div class="container py-20">
    <div class="form-card" style="max-width:600px;">
        <div class="form-title"><i class="fas fa-user-circle text-primary"></i><br>Modifier mon profil</div>
        <form method="POST">
            <div class="form-row">
                <div class="form-group"><label>Nom</label><input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($user['nom']) ?>" required></div>
                <div class="form-group"><label>Prénom</label><input type="text" name="prenom" class="form-control" value="<?= htmlspecialchars($user['prenom']) ?>" required></div>
            </div>
            <div class="form-group"><label>Email</label><input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled style="background:var(--bg);cursor:not-allowed;"></div>
            <div class="form-row">
                <div class="form-group"><label>Téléphone</label><input type="tel" name="telephone" class="form-control" value="<?= htmlspecialchars($user['telephone'] ?? '') ?>"></div>
                <div class="form-group"><label>Wilaya</label>
                    <select name="wilaya" class="form-control">
                        <option value="">-- Choisir --</option>
                        <?php foreach ($wilayas as $w): ?><option value="<?= $w ?>" <?= $user['wilaya']===$w?'selected':'' ?>><?= $w ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="py-20 px-20 border-top mt-20" style="background:var(--bg);border-radius:8px;margin-bottom:16px;padding:14px;">
                <p class="font-bold mb-20" style="font-size:0.9rem;">Changer le mot de passe (optionnel)</p>
                <div class="form-row">
                    <div class="form-group mb-0"><input type="password" name="password" class="form-control" placeholder="Nouveau mot de passe"></div>
                    <div class="form-group mb-0"><input type="password" name="confirm" class="form-control" placeholder="Confirmer"></div>
                </div>
            </div>
            <div class="flex items-center gap-10 py-20 px-20 mt-20" style="background:var(--primary-light);border-radius:8px;margin-bottom:16px;padding:12px;">
                <i class="fas fa-id-badge icon-primary-text" style="font-size:1.2rem;"></i>
                <div style="font-size:0.85rem;"><strong>Rôle :</strong> <?= ucfirst(str_replace('_',' ',$user['role'])) ?></div>
            </div>
            <button type="submit" class="btn btn-primary w-100 flex-center">
                <i class="fas fa-save"></i> Sauvegarder
            </button>
        </form>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
