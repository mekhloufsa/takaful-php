<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole(['president_association']);
$db  = getDB();
$uid = $_SESSION['user_id'];

// Chercher la demande/association du président
$assoc = $db->prepare("SELECT * FROM association WHERE president_id=? ORDER BY date_creation DESC LIMIT 1");
$assoc->execute([$uid]);
$assoc = $assoc->fetch();

$pageTitle = 'Espace Président d\'Association';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div class="breadcrumb"><a href="<?= BASE_URL ?>index.php">Accueil</a> / Mon Association</div>
    <h1><i class="fas fa-building"></i> <?= $assoc ? htmlspecialchars($assoc['nom']) : 'Mon Association' ?></h1>
    <?php if ($assoc): ?>
    <p>Statut : <span class="badge badge-<?= ['active'=>'success','en_attente'=>'warning','rejetee'=>'danger','suspendue'=>'secondary'][$assoc['statut']]??'secondary' ?>">
        <?= ucfirst($assoc['statut']) ?>
    </span></p>
    <?php endif; ?>
</div>
<div class="container py-20">

<?php if (!$assoc): ?>
    <!-- Aucune association soumise -->
    <div class="empty-state card py-50">
        <i class="fas fa-building icon-primary-text" style="opacity:0.3;"></i>
        <h3>Vous n'avez pas encore soumis de demande d'association</h3>
        <p>Créez votre association pour commencer à organiser des activités humanitaires.</p>
        <a href="<?= BASE_URL ?>associations/create.php" class="btn btn-primary mt-20"><i class="fas fa-plus"></i> Créer une association</a>
    </div>

<?php elseif ($assoc['statut'] === 'en_attente'): ?>
    <!-- En attente de validation -->
    <div class="card" style="max-width:700px;margin:0 auto;">
        <div class="card-body" style="padding:40px;text-align:center;">
            <div style="width:80px;height:80px;background:var(--warning-light);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;font-size:2rem;color:var(--warning);">
                <i class="fas fa-hourglass-half"></i>
            </div>
            <h2 style="font-size:1.4rem;margin-bottom:12px;">Demande en cours d'examen</h2>
            <p style="color:var(--text-light);max-width:500px;margin:0 auto 24px;">
                Votre demande de création de l'association <strong><?= htmlspecialchars($assoc['nom']) ?></strong> a bien été reçue.
                Un administrateur va examiner votre dossier et vous notifiera de sa décision.
            </p>
            <!-- Barre de progression visuelle -->
            <div style="display:flex;gap:0;max-width:400px;margin:30px auto;position:relative;">
                <div style="position:absolute;top:20px;left:0;right:0;height:3px;background:var(--border);z-index:0;"></div>
                <div style="position:absolute;top:20px;left:0;width:50%;height:3px;background:var(--warning);z-index:1;"></div>
                <?php foreach ([['Demande soumise','paper-plane'],['En cours d\'examen','search'],['Validation admin','check-circle']] as $i => $step): ?>
                <div style="flex:1;text-align:center;position:relative;z-index:2;">
                    <div style="width:42px;height:42px;border-radius:50%;margin:0 auto 10px;display:flex;align-items:center;justify-content:center;font-size:1rem;
                        background:<?= $i<=1?'var(--warning)':'var(--border)' ?>;color:<?= $i<=1?'white':'var(--text-light)' ?>;">
                        <i class="fas fa-<?= $step[1] ?>"></i>
                    </div>
                    <div style="font-size:0.78rem;font-weight:<?= $i===1?'800':'600' ?>;color:<?= $i<=1?'var(--warning)':'var(--text-light)' ?>;"><?= $step[0] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <div style="padding:14px;background:var(--warning-light);border-radius:var(--radius-sm);font-size:0.88rem;color:var(--warning);text-align:left;">
                <i class="fas fa-info-circle"></i>
                Le délai de traitement est généralement de 2 à 5 jours ouvrables.
                Si votre dossier est incomplet, l'administrateur vous contactera par email.
            </div>
        </div>
    </div>

<?php elseif ($assoc['statut'] === 'rejetee'): ?>
    <!-- Association rejetée -->
    <div class="card" style="max-width:700px;margin:0 auto;">
        <div class="card-body" style="padding:40px;text-align:center;">
            <div style="width:80px;height:80px;background:var(--danger-light);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;font-size:2rem;color:var(--danger);">
                <i class="fas fa-times-circle"></i>
            </div>
            <h2 style="font-size:1.4rem;margin-bottom:12px;color:var(--danger);">Demande rejetée</h2>
            <p style="color:var(--text-light);max-width:500px;margin:0 auto 24px;">
                Votre demande pour l'association <strong><?= htmlspecialchars($assoc['nom']) ?></strong> a été rejetée par l'administration.
                Veuillez corriger votre dossier et soumettre une nouvelle demande.
            </p>
            <a href="<?= BASE_URL ?>associations/create.php" class="btn btn-primary"><i class="fas fa-redo"></i> Soumettre une nouvelle demande</a>
        </div>
    </div>

<?php elseif ($assoc['statut'] === 'suspendue'): ?>
    <!-- Suspendue -->
    <div class="alert alert-warning"><i class="fas fa-ban"></i> Votre association a été suspendue par l'administration. Veuillez contacter l'admin pour plus d'informations.</div>

<?php else: ?>
    <!-- Association ACTIVE — afficher le tableau de bord complet -->
    <?php
    $sid = $assoc['id'];
    $nbSieges   = $db->prepare("SELECT COUNT(*) FROM siege WHERE association_id=?"); $nbSieges->execute([$sid]); $nbSieges = $nbSieges->fetchColumn();
    $nbMembres  = $db->prepare("SELECT COUNT(*) FROM membre_association WHERE association_id=? AND statut='actif'"); $nbMembres->execute([$sid]); $nbMembres = $nbMembres->fetchColumn();
    $nbDons     = $db->prepare("SELECT COUNT(*) FROM don d JOIN siege s ON d.siege_id=s.id WHERE s.association_id=?"); $nbDons->execute([$sid]); $nbDons = $nbDons->fetchColumn();
    $nbDemandes = $db->prepare("SELECT COUNT(*) FROM demande_aide da JOIN siege s ON da.siege_id=s.id WHERE s.association_id=?"); $nbDemandes->execute([$sid]); $nbDemandes = $nbDemandes->fetchColumn();

    // Vérifier si au moins un siège avec responsable existe
    $hasSiegeAvecResponsable = $db->prepare("SELECT COUNT(*) FROM siege WHERE association_id=? AND president_siege_id IS NOT NULL AND statut='actif'");
    $hasSiegeAvecResponsable->execute([$sid]);
    $hasSiegeAvecResponsable = (int)$hasSiegeAvecResponsable->fetchColumn();
    ?>

    <?php if ($nbSieges == 0): ?>
    <div class="alert alert-danger mb-20">
        <i class="fas fa-exclamation-triangle"></i>
        <strong>Action requise :</strong> Votre association n'apparaît pas dans la liste publique et ne peut recevoir ni dons ni demandes d'aide.
        Vous devez créer au moins un siège pour l'activer publiquement.
        <a href="<?= BASE_URL ?>sieges/create.php" class="btn btn-primary btn-sm" style="margin-left:10px;"><i class="fas fa-plus"></i> Créer un siège</a>
    </div>
    <?php elseif (!$hasSiegeAvecResponsable): ?>
    <div class="alert alert-warning mb-20">
        <i class="fas fa-info-circle"></i>
        <strong>Information :</strong> Votre association apparaît dans la liste publique avec le badge <em>"Cherche responsable"</em>.
        Pour finaliser votre structure, assignez un responsable à votre siège, ou attendez des candidatures de membres.
    </div>
    <?php endif; ?>

    <div class="stats-grid mb-20" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px;">
        <a href="<?= BASE_URL ?>sieges/index.php" style="text-decoration:none; color:inherit;">
            <div class="stat-card" style="cursor: pointer; height: 100%;">
                <div class="stat-card-icon icon-primary-text"><i class="fas fa-map-marker-alt"></i></div>
                <div class="stat-card-info"><div class="num"><?= $nbSieges ?></div><div class="label">Sièges</div></div>
            </div>
        </a>
        <a href="<?= BASE_URL ?>president_association/membres.php" style="text-decoration:none; color:inherit;">
            <div class="stat-card orange" style="cursor: pointer; height: 100%;">
                <div class="stat-card-icon icon-secondary-text"><i class="fas fa-users"></i></div>
                <div class="stat-card-info"><div class="num"><?= $nbMembres ?></div><div class="label">Bénévoles Actifs</div></div>
            </div>
        </a>
        <a href="<?= BASE_URL ?>president_association/dons.php" style="text-decoration:none; color:inherit;">
            <div class="stat-card blue" style="cursor: pointer; height: 100%;">
                <div class="stat-card-icon icon-info-text"><i class="fas fa-donate"></i></div>
                <div class="stat-card-info"><div class="num"><?= $nbDons ?></div><div class="label">Dons reçus</div></div>
            </div>
        </a>
        <a href="<?= BASE_URL ?>president_association/demandes.php" style="text-decoration:none; color:inherit;">
            <div class="stat-card red" style="cursor: pointer; height: 100%;">
                <div class="stat-card-icon icon-danger-text"><i class="fas fa-hand-holding-heart"></i></div>
                <div class="stat-card-info"><div class="num"><?= $nbDemandes ?></div><div class="label">Demandes d'aide</div></div>
            </div>
        </a>
    </div>

    <div class="flex gap-10 mb-20" style="flex-wrap:wrap;">
        <a href="<?= BASE_URL ?>sieges/index.php" class="btn btn-primary"><i class="fas fa-map-marker-alt"></i> Gérer les sièges</a>
        <a href="<?= BASE_URL ?>president_association/membres.php" class="btn btn-outline"><i class="fas fa-users"></i> Bénévoles des Sièges</a>
        <a href="<?= BASE_URL ?>president_association/dons.php" class="btn btn-outline"><i class="fas fa-donate"></i> Dons reçus</a>
        <a href="<?= BASE_URL ?>president_association/demandes.php" class="btn btn-outline"><i class="fas fa-hand-holding-heart"></i> Demandes d'aide</a>
        <a href="<?= BASE_URL ?>sieges/create.php" class="btn btn-secondary"><i class="fas fa-plus"></i> Nouveau siège</a>
    </div>

    <div class="card">
        <div class="card-header"><h3><i class="fas fa-info-circle icon-primary-text"></i> Informations de l'association</h3></div>
        <div class="card-body">
            <div class="two-col-grid">
                <div><strong>Nom :</strong><br><?= htmlspecialchars($assoc['nom']) ?></div>
                <div><strong>Créée le :</strong><br><?= date('d/m/Y', strtotime($assoc['date_creation'])) ?></div>
                <div style="grid-column:span 2;"><strong>Description :</strong><br><?= nl2br(htmlspecialchars($assoc['description'] ?? 'Aucune description.')) ?></div>
            </div>
        </div>
    </div>

<?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
