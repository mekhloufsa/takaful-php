<?php
require_once __DIR__ . '/../includes/auth.php';
$pageTitle = 'Annonces';
$db = getDB();

$annonces = $db->query("SELECT * FROM annonce WHERE (date_fin IS NULL OR date_fin >= NOW()) ORDER BY date_creation DESC")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1><i class="fas fa-bullhorn"></i> Annonces</h1>
        <p>Dernières actualités et informations de la plateforme</p>
    </div>
</div>

<section class="section">
    <div class="container">
        <?php if ($annonces): ?>
        <div class="cards-grid">
            <?php foreach ($annonces as $ann): ?>
            <div class="news-card">
                <div class="date"><?= date('d/m/Y', strtotime($ann['date_creation'])) ?></div>
                <h3><?= htmlspecialchars($ann['titre']) ?></h3>
                <p><?= nl2br(htmlspecialchars($ann['contenu'])) ?></p>
                <?php if ($ann['date_fin']): ?>
                <p style="margin-top:12px;font-size:0.8rem;color:var(--text-light);">
                    <i class="fas fa-calendar-alt"></i> Expire le <?= date('d/m/Y', strtotime($ann['date_fin'])) ?>
                </p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-bullhorn"></i>
            <h3>Aucune annonce pour le moment</h3>
            <p>Revenez bientôt pour les dernières actualités.</p>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
