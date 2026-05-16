<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Accueil';
$db = getDB();

$nbMembres = $db->query("SELECT COUNT(*) FROM membre")->fetchColumn();
$nbDons = $db->query("SELECT COUNT(*) FROM don WHERE statut='confirme' OR statut='collecte'")->fetchColumn();
$nbDemandes = $db->query("SELECT COUNT(*) FROM demande_aide WHERE statut='resolue'")->fetchColumn();
$nbAssociations = $db->query("SELECT COUNT(*) FROM association WHERE statut='active'")->fetchColumn();

$associations = $db->query("SELECT a.*, m.nom AS pnom, m.prenom AS ppren FROM association a LEFT JOIN membre m ON a.president_id=m.id WHERE a.statut='active' LIMIT 6")->fetchAll();
$annonces = $db->query("SELECT * FROM annonce WHERE (date_fin IS NULL OR date_fin >= NOW()) ORDER BY date_creation DESC LIMIT 3")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<?php if ($annonces): ?>
<div class="announcement-banner">
    <i class="fas fa-bullhorn"></i>
    <?= htmlspecialchars($annonces[0]['titre']) ?> — <?= htmlspecialchars(substr($annonces[0]['contenu'], 0, 100)) ?>...
</div>
<?php endif; ?>

<section class="hero">
    <div class="hero-content">
        <h1><i class="fas fa-hands-helping"></i> Bienvenue sur Takaful</h1>
        <p>Connectons les cœurs généreux aux personnes dans le besoin. Ensemble, nous construisons une société solidaire et bienveillante.</p>
        <div class="hero-btns">
            <?php if (!isLoggedIn()): ?>
                <a href="<?= BASE_URL ?>register.php" class="btn btn-white"><i class="fas fa-user-plus"></i> Rejoindre la communauté</a>
                <a href="<?= BASE_URL ?>dons/create.php" class="btn btn-secondary"><i class="fas fa-donate"></i> Faire un don</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>dons/create.php" class="btn btn-white"><i class="fas fa-donate"></i> Faire un don</a>
                <a href="<?= BASE_URL ?>demandes/create.php" class="btn btn-secondary"><i class="fas fa-hand-holding-heart"></i> Demander de l'aide</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<div class="stats-strip">
    <div class="stat-item"><div class="num"><?= number_format($nbMembres) ?>+</div><div class="label"><i class="fas fa-users"></i> Membres</div></div>
    <div class="stat-item"><div class="num"><?= number_format($nbDons) ?>+</div><div class="label"><i class="fas fa-donate"></i> Dons effectués</div></div>
    <div class="stat-item"><div class="num"><?= number_format($nbDemandes) ?>+</div><div class="label"><i class="fas fa-heart"></i> Personnes aidées</div></div>
    <div class="stat-item"><div class="num"><?= number_format($nbAssociations) ?>+</div><div class="label"><i class="fas fa-building"></i> Associations actives</div></div>
</div>

<section class="section" style="background:var(--white);">
    <div class="container">
        <div class="section-header">
            <h2>Comment fonctionne <span>Takaful</span> ?</h2>
            <p>Une plateforme simple pour donner, recevoir et s'organiser</p>
        </div>
        <div class="cards-grid">
            <div class="card feature-card">
                <div class="icon icon-circle icon-circle-lg icon-primary"><i class="fas fa-user-plus"></i></div>
                <h3>1. Inscrivez-vous</h3>
                <p>Créez votre compte gratuitement et rejoignez la communauté Takaful en quelques minutes.</p>
            </div>
            <div class="card feature-card">
                <div class="icon icon-circle icon-circle-lg icon-secondary"><i class="fas fa-search"></i></div>
                <h3>2. Trouvez une cause</h3>
                <p>Parcourez les associations et les demandes d'aide pour trouver où votre soutien est le plus utile.</p>
            </div>
            <div class="card feature-card">
                <div class="icon icon-circle icon-circle-lg icon-info"><i class="fas fa-donate"></i></div>
                <h3>3. Donnez ou aidez</h3>
                <p>Faites un don financier ou matériel, ou soumettez une demande d'aide si vous en avez besoin.</p>
            </div>
            <div class="card feature-card">
                <div class="icon icon-circle icon-circle-lg icon-success"><i class="fas fa-chart-line"></i></div>
                <h3>4. Suivez l'impact</h3>
                <p>Suivez vos dons et demandes en temps réel grâce à notre système de suivi transparent.</p>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-header">
            <h2>Associations <span>actives</span></h2>
            <p>Des associations qui font la différence dans toute l'Algérie</p>
        </div>
        <?php if ($associations): ?>
        <div class="cards-grid">
            <?php foreach ($associations as $a): ?>
            <div class="card">
                <div class="card-header">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div class="card-icon icon-green"><i class="fas fa-building"></i></div>
                        <div>
                            <h3 style="font-size:1rem;font-weight:700;"><?= htmlspecialchars($a['nom']) ?></h3>
                            <span class="badge badge-success">Active</span>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-light" style="font-size:0.9rem;"><?= htmlspecialchars(substr($a['description'] ?? 'Association humanitaire', 0, 100)) ?>...</p>
                    <?php if ($a['pnom']): ?>
                    <p class="mt-10 text-light" style="font-size:0.85rem;"><i class="fas fa-user"></i> Président : <?= htmlspecialchars($a['pnom'] . ' ' . $a['ppren']) ?></p>
                    <?php endif; ?>
                </div>
                <div class="card-footer">
                    <a href="<?= BASE_URL ?>associations/detail.php?id=<?= $a['id'] ?>" class="btn btn-outline btn-sm">Voir les détails</a>
                    <?php if (isLoggedIn()): ?>
                        <a href="<?= BASE_URL ?>dons/create.php?association=<?= $a['id'] ?>" class="btn btn-primary btn-sm"><i class="fas fa-donate"></i> Donner</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-20">
            <a href="<?= BASE_URL ?>associations/index.php" class="btn btn-primary"><i class="fas fa-building"></i> Voir toutes les associations</a>
        </div>
        <?php else: ?>
        <div class="empty-state"><i class="fas fa-building"></i><h3>Aucune association active pour le moment</h3><p>Soyez le premier à créer une association !</p></div>
        <?php endif; ?>
    </div>
</section>

<section class="section text-center" style="background:linear-gradient(135deg,var(--primary),var(--accent));color:white;">
    <div class="container">
        <h2 style="font-size:2rem;margin-bottom:16px;">Vous avez besoin d'aide ?</h2>
        <p style="opacity:.9;margin-bottom:28px;font-size:1.05rem;">Ne restez pas seul face à vos difficultés. Notre communauté est là pour vous soutenir.</p>
        <a href="<?= BASE_URL ?><?= isLoggedIn() ? 'demandes/create.php' : 'register.php' ?>" class="btn btn-white btn-lg">
            <i class="fas fa-hand-holding-heart"></i> Soumettre une demande d'aide
        </a>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
