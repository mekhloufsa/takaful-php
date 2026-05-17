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

$dons = $db->prepare("SELECT d.*, m.nom as dnom, m.prenom as dpren 
                      FROM don d 
                      JOIN membre m ON d.donateur_id=m.id 
                      WHERE d.siege_id=? ORDER BY d.date_don DESC");
$dons->execute([$siege['id']]);
$dons = $dons->fetchAll();

$pageTitle = 'Gestion des Dons';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div class="breadcrumb"><a href="<?= BASE_URL ?>president_siege/dashboard.php">Tableau de bord</a> / Dons</div>
    <h1><i class="fas fa-donate"></i> Dons</h1>
    <p>Siège : <?= htmlspecialchars($siege['nom']) ?></p>
</div>
<div class="container py-50">
    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Donateur</th>
                <th>Type</th>
                <th>Catégorie / Montant</th>
                <th>Statut</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($dons as $d): ?>
            <tr>
                <td><?= date('d/m/Y H:i', strtotime($d['date_don'])) ?></td>
                <td><?= htmlspecialchars($d['dnom'].' '.$d['dpren']) ?></td>
                <td><span class="badge badge-secondary"><?= ucfirst($d['type']) ?></span></td>
                <td>
                    <?php if($d['type'] === 'financier') echo number_format($d['montant'], 2, ',', ' ') . ' DZD'; else echo htmlspecialchars($d['categorie']); ?>
                </td>
                <td>
                    <span class="badge badge-<?= ['en_attente'=>'warning','en_cours'=>'primary','confirme'=>'success','collecte'=>'success','annule'=>'danger'][$d['statut']] ?? 'secondary' ?>">
                        <?= ucfirst(str_replace('_',' ',$d['statut'])) ?>
                    </span>
                </td>
                <td>
                    <a href="traiter_don.php?id=<?= $d['id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i> Voir Détails & Traiter</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($dons)): ?>
            <tr><td colspan="6" class="text-center">Aucun don trouvé.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
