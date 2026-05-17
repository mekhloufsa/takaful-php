<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['president_siege']);
$db = getDB();
$uid = $_SESSION['user_id'];

// Trouver le siège du président
$stmt = $db->prepare("SELECT id, nom FROM siege WHERE president_siege_id=?");
$stmt->execute([$uid]);
$siege = $stmt->fetch();

if (!$siege) {
    flash('Vous n\'êtes assigné à aucun siège.', 'error');
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$demandes = $db->prepare("SELECT d.*, m.nom as dnom, m.prenom as dpren 
                          FROM demande_aide d 
                          JOIN membre m ON d.demandeur_id=m.id 
                          WHERE d.siege_id=? ORDER BY d.date_demande DESC");
$demandes->execute([$siege['id']]);
$demandes = $demandes->fetchAll();

$pageTitle = 'Gestion des Demandes d\'aide';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div class="breadcrumb"><a href="<?= BASE_URL ?>president_siege/dashboard.php">Tableau de bord</a> / Demandes</div>
    <h1><i class="fas fa-hand-holding-heart"></i> Demandes d'aide</h1>
    <p>Siège : <?= htmlspecialchars($siege['nom']) ?></p>
</div>
<div class="container py-50">
    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Demandeur</th>
                <th>Sujet</th>
                <th>Type</th>
                <th>Statut</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($demandes as $d): ?>
            <tr>
                <td><?= date('d/m/Y H:i', strtotime($d['date_demande'])) ?></td>
                <td><?= htmlspecialchars($d['dnom'].' '.$d['dpren']) ?></td>
                <td><?= htmlspecialchars($d['sujet']) ?></td>
                <td><span class="badge badge-secondary"><?= ucfirst($d['type_aide']) ?></span></td>
                <td>
                    <span class="badge badge-<?= ['soumise'=>'warning','en_cours'=>'primary','acceptee'=>'success','refusee'=>'danger','resolue'=>'success'][$d['statut']] ?? 'secondary' ?>">
                        <?= ucfirst(str_replace('_',' ',$d['statut'])) ?>
                    </span>
                </td>
                <td>
                    <a href="traiter_demande.php?id=<?= $d['id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i> Voir Détails & Traiter</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($demandes)): ?>
            <tr><td colspan="6" class="text-center">Aucune demande trouvée.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
