<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['membre_association', 'president_siege']);
$db = getDB();
$uid = $_SESSION['user_id'];
$role = getRole();

$missions = [];

if ($role === 'president_siege') {
    // Récupérer les assignations où le responsable de siège s'est assigné lui-même
    $sql = "SELECT a.*, 
                   don.type as don_type, don.categorie as don_cat, 
                   dem.sujet as dem_sujet, dem.type_aide as dem_type
            FROM assignation a 
            LEFT JOIN don ON a.don_id = don.id
            LEFT JOIN demande_aide dem ON a.demande_id = dem.id
            WHERE a.president_assigne_id = ? 
            ORDER BY a.date_assignation DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute([$uid]);
    $missions = $stmt->fetchAll();
} else {
    // Trouver le membre_association_id
    $stmt = $db->prepare("SELECT id, association_id, siege_id FROM membre_association WHERE membre_id=? AND statut='actif'");
    $stmt->execute([$uid]);
    $mas = $stmt->fetchAll();

    if ($mas) {
        $ma_ids = array_column($mas, 'id');
        $placeholders = implode(',', array_fill(0, count($ma_ids), '?'));
        
        // Récupérer les assignations (Dons et Demandes)
        $sql = "SELECT a.*, 
                       don.type as don_type, don.categorie as don_cat, 
                       dem.sujet as dem_sujet, dem.type_aide as dem_type
                FROM assignation a 
                LEFT JOIN don ON a.don_id = don.id
                LEFT JOIN demande_aide dem ON a.demande_id = dem.id
                WHERE a.membre_association_id IN ($placeholders) 
                ORDER BY a.date_assignation DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($ma_ids);
        $missions = $stmt->fetchAll();
    }
}

$pageTitle = 'Mes Missions (Assignations)';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div class="breadcrumb"><a href="<?= BASE_URL ?>index.php">Accueil</a> / Mes Missions</div>
    <h1><i class="fas fa-tasks"></i> Mes Missions assignées</h1>
    <p>Gérez les tâches qui vous ont été confiées par le responsable du siège.</p>
</div>
<div class="container py-50">
    <table class="table">
        <thead>
            <tr>
                <th>Date d'assignation</th>
                <th>Type de tâche</th>
                <th>Détail (Sujet / Catégorie)</th>
                <th>Statut</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($missions as $m): ?>
            <tr>
                <td><?= date('d/m/Y', strtotime($m['date_assignation'])) ?></td>
                <td>
                    <?php if ($m['don_id']): ?>
                        <span class="badge badge-info"><i class="fas fa-donate"></i> Collecte de Don</span>
                    <?php elseif ($m['demande_id']): ?>
                        <span class="badge badge-primary"><i class="fas fa-hand-holding-heart"></i> Demande d'aide</span>
                    <?php else: ?>
                        <span class="badge badge-secondary"><i class="fas fa-thumbtack"></i> Mission classique</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?= htmlspecialchars($m['dem_sujet'] ?? $m['don_cat'] ?? 'Mission') ?>
                </td>
                <td>
                    <span class="badge badge-<?= ['assignee'=>'warning','en_cours'=>'primary','terminee'=>'success','annulee'=>'danger'][$m['statut']] ?? 'secondary' ?>">
                        <?= ucfirst($m['statut']) ?>
                    </span>
                </td>
                <td>
                    <a href="traiter.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-cog"></i> Gérer</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($missions)): ?>
            <tr><td colspan="5" class="text-center">Aucune mission assignée.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
