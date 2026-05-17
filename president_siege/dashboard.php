<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['president_siege']);
$db = getDB();
$uid = $_SESSION['user_id'];

// Trouver le siège du président
$stmt = $db->prepare("SELECT s.*, a.nom as assoc_nom FROM siege s JOIN association a ON s.association_id=a.id WHERE s.president_siege_id=?");
$stmt->execute([$uid]);
$siege = $stmt->fetch();

if (!$siege) {
    flash('Vous n\'êtes assigné à aucun siège en tant que responsable.', 'error');
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

// Statistiques
$nbDemandes = $db->prepare("SELECT COUNT(*) FROM demande_aide WHERE siege_id=? AND statut='soumise'");
$nbDemandes->execute([$siege['id']]); $nbDemandes = $nbDemandes->fetchColumn();

$nbDons = $db->prepare("SELECT COUNT(*) FROM don WHERE siege_id=? AND statut='en_attente'");
$nbDons->execute([$siege['id']]); $nbDons = $nbDons->fetchColumn();

$nbMembres = $db->prepare("SELECT COUNT(*) FROM membre_association WHERE siege_id=? AND statut='actif'");
$nbMembres->execute([$siege['id']]); $nbMembres = $nbMembres->fetchColumn();

$nbCandidatures = $db->prepare("SELECT COUNT(*) FROM membre_association WHERE siege_id=? AND statut='en_attente'");
$nbCandidatures->execute([$siege['id']]); $nbCandidatures = $nbCandidatures->fetchColumn();

$nbMesMissions = $db->prepare("SELECT COUNT(*) FROM assignation WHERE president_assigne_id=? AND statut IN ('assignee','en_cours')");
$nbMesMissions->execute([$uid]); $nbMesMissions = $nbMesMissions->fetchColumn();

$pageTitle = 'Tableau de bord - Siège';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div class="breadcrumb"><a href="<?= BASE_URL ?>index.php">Accueil</a> / Mon Siège</div>
    <h1><i class="fas fa-map-marker-alt"></i> Espace Responsable de Siège</h1>
    <p><?= htmlspecialchars($siege['nom']) ?> - <?= htmlspecialchars($siege['assoc_nom']) ?></p>
</div>

<div class="container py-50">
    <?php if ($nbMesMissions > 0): ?>
        <div class="alert alert-info mb-20" style="display:flex; justify-content:space-between; align-items:center; border-left: 5px solid var(--primary); background: rgba(52, 152, 219, 0.1);">
            <div>
                <i class="fas fa-tasks icon-primary-text" style="font-size:1.2rem; margin-right: 8px;"></i>
                <strong>Vous vous êtes assigné des missions personnelles !</strong> Vous avez <strong><?= $nbMesMissions ?></strong> mission(s) en cours à finaliser.
            </div>
            <a href="<?= BASE_URL ?>missions/index.php" class="btn btn-sm btn-primary" style="margin-left: 15px;"><i class="fas fa-external-link-alt"></i> Gérer mes missions</a>
        </div>
    <?php endif; ?>
    <div class="stats-grid mb-20" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px;">
        <div class="stat-card">
            <div class="stat-icon" style="background:var(--primary);color:white;"><i class="fas fa-hand-holding-heart"></i></div>
            <div class="stat-info">
                <h3><?= $nbDemandes ?></h3>
                <p>Demandes en attente</p>
                <a href="demandes.php" class="btn btn-sm btn-outline mt-10">Traiter</a>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#2ecc71;color:white;"><i class="fas fa-donate"></i></div>
            <div class="stat-info">
                <h3><?= $nbDons ?></h3>
                <p>Dons en attente</p>
                <a href="dons.php" class="btn btn-sm btn-outline mt-10">Traiter</a>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#e67e22;color:white;"><i class="fas fa-user-plus"></i></div>
            <div class="stat-info">
                <h3><?= $nbCandidatures ?></h3>
                <p>Candidatures bénévoles</p>
                <a href="membres.php" class="btn btn-sm btn-outline mt-10">Gérer</a>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#3498db;color:white;"><i class="fas fa-users"></i></div>
            <div class="stat-info">
                <h3><?= $nbMembres ?></h3>
                <p>Bénévoles actifs</p>
                <a href="membres.php" class="btn btn-sm btn-outline mt-10">Voir la liste</a>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
