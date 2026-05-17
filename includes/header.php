<?php
require_once __DIR__ . '/../includes/auth.php';
$flash = getFlash();
$user = getCurrentUser();
$role = getRole();
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Takaful' ?> — Plateforme Humanitaire</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

<nav class="navbar">
    <div class="nav-container">
        <a href="<?= BASE_URL ?>index.php" class="nav-logo">
            <i class="fas fa-hands-helping"></i>
            <span>Takaful</span>
        </a>

        <button class="nav-toggle" onclick="toggleMenu()"><i class="fas fa-bars"></i></button>

        <ul class="nav-links" id="navMenu">
            <li><a href="<?= BASE_URL ?>index.php"><i class="fas fa-home"></i> Accueil</a></li>
            <li><a href="<?= BASE_URL ?>associations/index.php"><i class="fas fa-users"></i> Associations</a></li>

            <?php if (isLoggedIn()): ?>
                <?php if (!isAdmin()): ?>
                    <li><a href="<?= BASE_URL ?>dons/index.php"><i class="fas fa-donate"></i> Dons</a></li>
                    <li><a href="<?= BASE_URL ?>demandes/index.php"><i class="fas fa-hand-holding-heart"></i> Demandes d'aide</a></li>
                <?php endif; ?>

                <?php if (hasRole(['president_association'])): ?>
                    <?php
                    // Vérifier si l'association du président est active
                    $__db = getDB();
                    $__assocCheck = $__db->prepare("SELECT statut FROM association WHERE president_id=? LIMIT 1");
                    $__assocCheck->execute([$_SESSION['user_id']]);
                    $__assocStatut = $__assocCheck->fetchColumn();
                    ?>
                    <?php if ($__assocStatut === 'active'): ?>
                    <li class="dropdown">
                        <a href="#"><i class="fas fa-building"></i> Mon Association <i class="fas fa-chevron-down"></i></a>
                        <ul class="dropdown-menu">
                            <li><a href="<?= BASE_URL ?>president_association/dashboard.php">Tableau de bord</a></li>
                            <li><a href="<?= BASE_URL ?>sieges/index.php">Gérer les sièges</a></li>
                            <li><a href="<?= BASE_URL ?>president_association/membres.php">Membres des Sièges</a></li>
                            <li><a href="<?= BASE_URL ?>president_association/dons.php">Dons reçus</a></li>
                            <li><a href="<?= BASE_URL ?>president_association/demandes.php">Demandes d'aide</a></li>
                            <li><a href="<?= BASE_URL ?>president_association/candidatures.php">Candidatures</a></li>
                        </ul>
                    </li>
                    <?php else: ?>
                    <li><a href="<?= BASE_URL ?>president_association/dashboard.php"><i class="fas fa-clock"></i> Ma demande (en attente)</a></li>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if (hasRole(['president_siege'])): ?>
                    <li class="dropdown">
                        <a href="#"><i class="fas fa-map-marker-alt"></i> Mon Siège <i class="fas fa-chevron-down"></i></a>
                        <ul class="dropdown-menu">
                            <li><a href="<?= BASE_URL ?>president_siege/dashboard.php">Tableau de bord</a></li>
                            <li><a href="<?= BASE_URL ?>president_siege/membres.php">Gérer les membres</a></li>
                            <li><a href="<?= BASE_URL ?>president_siege/dons.php">Gérer les dons</a></li>
                            <li><a href="<?= BASE_URL ?>missions/index.php">Mes Missions Personnelles</a></li>
                            <li><a href="<?= BASE_URL ?>president_siege/demandes.php">Demandes d'aide</a></li>
                        </ul>
                    </li>
                <?php endif; ?>

                <?php if (hasRole(['membre_association'])): ?>
                    <li><a href="<?= BASE_URL ?>missions/index.php"><i class="fas fa-tasks"></i> Mes missions</a></li>
                <?php endif; ?>

                <?php if (isAdmin()): ?>
                    <li class="dropdown">
                        <a href="#"><i class="fas fa-cog"></i> Administration <i class="fas fa-chevron-down"></i></a>
                        <ul class="dropdown-menu">
                            <li><a href="<?= BASE_URL ?>admin/index.php">Tableau de bord</a></li>
                            <li><a href="<?= BASE_URL ?>admin/users.php">Utilisateurs</a></li>
                            <li><a href="<?= BASE_URL ?>admin/associations.php">Associations</a></li>
                            <li><a href="<?= BASE_URL ?>admin/annonces.php">Annonces</a></li>
                        </ul>
                    </li>
                <?php endif; ?>

                <li class="dropdown">
                    <a href="#"><i class="fas fa-user-circle"></i> <?= htmlspecialchars($user['prenom'] ?? $user['email'] ?? 'Mon compte') ?> <i class="fas fa-chevron-down"></i></a>
                    <ul class="dropdown-menu">
                        <li><a href="<?= BASE_URL ?>dashboard.php">Mon tableau de bord</a></li>
                        <li><a href="<?= BASE_URL ?>profil.php">Mon profil</a></li>
                        <?php if (!isAdmin()): ?>
                            <li><hr></li>
                            <li><a href="<?= BASE_URL ?>dons/track.php"><i class="fas fa-donate"></i> Mes dons</a></li>
                            <li><a href="<?= BASE_URL ?>demandes/track.php"><i class="fas fa-hand-holding-heart"></i> Mes demandes d'aide</a></li>
                            <li><a href="<?= BASE_URL ?>candidatures/track.php"><i class="fas fa-handshake"></i> Mes candidatures</a></li>
                        <?php endif; ?>
                        <li><hr></li>
                        <li><a href="<?= BASE_URL ?>logout.php" class="text-danger"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
                    </ul>
                </li>
            <?php else: ?>
                <li><a href="<?= BASE_URL ?>login.php" class="btn-nav"><i class="fas fa-sign-in-alt"></i> Connexion</a></li>
                <li><a href="<?= BASE_URL ?>register.php" class="btn-nav btn-nav-outline"><i class="fas fa-user-plus"></i> Inscription</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<?php if (!empty($_GET['error'])): ?>
<div class="alert alert-danger">
    <i class="fas fa-exclamation-circle"></i>
    <?php
    $errors = [
        'acces_refuse' => 'Accès refusé. Vous n\'avez pas les permissions nécessaires.',
        'non_connecte' => 'Veuillez vous connecter pour accéder à cette page.',
    ];
    echo htmlspecialchars($errors[$_GET['error']] ?? 'Une erreur est survenue.');
    ?>
</div>
<?php endif; ?>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>">
    <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
    <?= htmlspecialchars($flash['msg']) ?>
</div>
<?php endif; ?>

<main class="main-content">
