<?php
require_once __DIR__ . '/includes/auth.php';
if (isLoggedIn()) { header('Location: ' . BASE_URL . 'dashboard.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom      = trim($_POST['nom'] ?? '');
    $prenom   = trim($_POST['prenom'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $telephone= trim($_POST['telephone'] ?? '');
    $wilaya   = trim($_POST['wilaya'] ?? '');
    $nin      = trim($_POST['nin'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    // NIN : NULL si vide, sinon valider format 18 chiffres
    $ninValue = !empty($nin) ? $nin : null;
    if ($ninValue !== null && !preg_match('/^\d{18}$/', $ninValue)) {
        $error = 'Le NIN doit contenir exactement 18 chiffres.';
    } elseif (empty($nom) || empty($prenom) || empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs obligatoires.';
    } elseif ($password !== $confirm) {
        $error = 'Les mots de passe ne correspondent pas.';
    } elseif (strlen($password) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères.';
    } else {
        $db = getDB();
        // Vérifier email
        $exists = $db->prepare("SELECT id FROM membre WHERE email = ?");
        $exists->execute([$email]);
        if ($exists->fetch()) {
            $error = 'Cette adresse email est déjà utilisée.';
        }
        // Vérifier NIN si fourni
        elseif ($ninValue !== null) {
            $existsNin = $db->prepare("SELECT id FROM membre WHERE nin = ?");
            $existsNin->execute([$ninValue]);
            if ($existsNin->fetch()) { $error = 'Ce NIN est déjà utilisé.'; }
        }
        if (!$error) {
            $id   = uuid();
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO membre (id,nom,prenom,email,mot_de_passe,telephone,wilaya,nin,role,statut) VALUES (?,?,?,?,?,?,?,?,'membre','actif')");
            $stmt->execute([$id, $nom, $prenom, $email, $hash, $telephone, $wilaya, $ninValue]);
            loginMembre($email, $password);
            flash('Compte créé avec succès ! Bienvenue sur Takaful.', 'success');
            header('Location: ' . BASE_URL . 'dashboard.php'); exit;
        }
    }
}

$wilayas = ['Adrar','Chlef','Laghouat','Oum El Bouaghi','Batna','Béjaïa','Biskra','Béchar','Blida','Bouira','Tamanrasset','Tébessa','Tlemcen','Tiaret','Tizi Ouzou','Alger','Djelfa','Jijel','Sétif','Saïda','Skikda','Sidi Bel Abbès','Annaba','Guelma','Constantine','Médéa','Mostaganem','M\'Sila','Mascara','Ouargla','Oran','El Bayadh','Illizi','Bordj Bou Arréridj','Boumerdès','El Tarf','Tindouf','Tissemsilt','El Oued','Khenchela','Souk Ahras','Tipaza','Mila','Aïn Defla','Naâma','Aïn Témouchent','Ghardaïa','Relizane','Timimoun','Bordj Badji Mokhtar','Ouled Djellal','Béni Abbès','In Salah','In Guezzam','Touggourt','Djanet','El M\'Ghair','El Menia'];
$pageTitle = 'Inscription';
include __DIR__ . '/includes/header.php';
?>
<div class="container py-20">
    <div class="form-card" style="max-width:620px;">
        <div class="form-title"><i class="fas fa-user-plus text-primary"></i><br>Créer un compte</div>
        <?php if ($error): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Nom <span>*</span></label>
                    <input type="text" name="nom" class="form-control" required value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>" placeholder="Votre nom">
                </div>
                <div class="form-group">
                    <label>Prénom <span>*</span></label>
                    <input type="text" name="prenom" class="form-control" required value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>" placeholder="Votre prénom">
                </div>
            </div>
            <div class="form-group">
                <label>Email <span>*</span></label>
                <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="votre@email.dz">
            </div>
            <div class="form-group">
                <label>NIN — Numéro d'Identification National</label>
                <input type="text" name="nin" class="form-control" 
                       value="<?= htmlspecialchars($_POST['nin'] ?? '') ?>"
                       placeholder="18 chiffres (ex: 100198500010001234)"
                       maxlength="18" pattern="\d{18}"
                       inputmode="numeric"
                       title="Le NIN doit contenir exactement 18 chiffres">
                <p class="form-hint"><i class="fas fa-info-circle"></i> Optionnel. Le NIN se trouve sur votre carte d'identité nationale.</p>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Téléphone</label>
                    <input type="tel" name="telephone" class="form-control" value="<?= htmlspecialchars($_POST['telephone'] ?? '') ?>" placeholder="05XXXXXXXX">
                </div>
                <div class="form-group">
                    <label>Wilaya</label>
                    <select name="wilaya" class="form-control">
                        <option value="">-- Choisir --</option>
                        <?php foreach ($wilayas as $w): ?>
                        <option value="<?= $w ?>" <?= ($_POST['wilaya'] ?? '') === $w ? 'selected' : '' ?>><?= $w ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Mot de passe <span>*</span></label>
                    <input type="password" name="password" class="form-control" required placeholder="Min. 6 caractères">
                </div>
                <div class="form-group">
                    <label>Confirmer <span>*</span></label>
                    <input type="password" name="confirm" class="form-control" required placeholder="Répétez le mot de passe">
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 flex-center" style="padding:13px;">
                <i class="fas fa-user-plus"></i> Créer mon compte
            </button>
        </form>
        <div class="text-center mt-20 pt-20 border-top text-light">
            Déjà membre ? <a href="<?= BASE_URL ?>login.php" class="font-bold text-primary">Se connecter</a>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
