<?php
require_once __DIR__ . '/includes/auth.php';
if (isLoggedIn()) { header('Location: ' . BASE_URL . 'dashboard.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $isAdmin = isset($_POST['is_admin']);

    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        $ok = $isAdmin ? loginAdmin($email, $password) : loginMembre($email, $password);
        if ($ok) {
            flash('Bienvenue ! Vous êtes connecté.', 'success');
            $dest = isAdmin() ? BASE_URL . 'admin/index.php' : BASE_URL . 'dashboard.php';
            header('Location: ' . $dest); exit;
        } else {
            $error = 'Email ou mot de passe incorrect, ou compte inactif.';
        }
    }
}
$pageTitle = 'Connexion';
include __DIR__ . '/includes/header.php';
?>

<div class="container py-50">
    <div class="form-card">
        <div class="form-title"><i class="fas fa-sign-in-alt text-primary"></i><br>Connexion</div>

        <?php if ($error): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Adresse email <span>*</span></label>
                <input type="email" id="email" name="email" class="form-control" required
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="votre@email.dz">
            </div>
            <div class="form-group">
                <label for="password">Mot de passe <span>*</span></label>
                <input type="password" id="password" name="password" class="form-control" required placeholder="••••••••">
            </div>
            <div class="form-group flex items-center gap-10">
                <input type="checkbox" id="is_admin" name="is_admin" style="width:18px;height:18px;cursor:pointer;">
                <label for="is_admin" style="margin:0;cursor:pointer;font-weight:600;">Connexion en tant qu'administrateur</label>
            </div>
            <button type="submit" class="btn btn-primary w-100 flex-center" style="padding:13px;">
                <i class="fas fa-sign-in-alt"></i> Se connecter
            </button>
        </form>

        <div class="text-center mt-20 pt-20 border-top text-light">
            Pas encore membre ?
            <a href="<?= BASE_URL ?>register.php" class="font-bold text-primary">Créer un compte</a>
        </div>

    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
