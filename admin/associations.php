<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $aid    = $_POST['assoc_id'] ?? '';

    if ($aid) {
        if ($action === 'valider') {
            // Activer l'association
            $db->prepare("UPDATE association SET statut='active' WHERE id=?")->execute([$aid]);
            // Promouvoir le président au bon rôle
            $assoc = $db->prepare("SELECT president_id FROM association WHERE id=?");
            $assoc->execute([$aid]);
            $assoc = $assoc->fetch();
            if ($assoc && $assoc['president_id']) {
                $db->prepare("UPDATE membre SET role='president_association' WHERE id=?")
                   ->execute([$assoc['president_id']]);
            }
            flash('Association validée et activée. Le président a reçu ses droits.', 'success');
        } elseif ($action === 'rejeter') {
            $db->prepare("UPDATE association SET statut='rejetee' WHERE id=?")->execute([$aid]);
            // Rétrograder le rôle si le membre avait été promu par erreur
            $assoc = $db->prepare("SELECT president_id FROM association WHERE id=?");
            $assoc->execute([$aid]);
            $assoc = $assoc->fetch();
            if ($assoc && $assoc['president_id']) {
                $db->prepare("UPDATE membre SET role='membre' WHERE id=? AND role='president_association'")
                   ->execute([$assoc['president_id']]);
            }
            flash('Association rejetée.', 'success');
        } elseif ($action === 'suspendre') {
            $db->prepare("UPDATE association SET statut='suspendue' WHERE id=?")->execute([$aid]);
            flash('Association suspendue.', 'success');
        } elseif ($action === 'reactiver') {
            $db->prepare("UPDATE association SET statut='active' WHERE id=?")->execute([$aid]);
            flash('Association réactivée.', 'success');
        } elseif ($action === 'supprimer') {
            // Rétrograder le rôle du président
            $assoc = $db->prepare("SELECT president_id FROM association WHERE id=?");
            $assoc->execute([$aid]);
            $assoc = $assoc->fetch();
            if ($assoc && $assoc['president_id']) {
                $db->prepare("UPDATE membre SET role='membre' WHERE id=?")->execute([$assoc['president_id']]);
            }
            $db->prepare("DELETE FROM association WHERE id=?")->execute([$aid]);
            flash('Association supprimée.', 'success');
        }
    }
    header('Location: ' . BASE_URL . 'admin/associations.php'); exit;
}

$filtre_statut = $_GET['statut'] ?? '';
$sql = "SELECT a.*, m.nom AS pnom, m.prenom AS ppren, m.email AS pemail, COUNT(DISTINCT s.id) as nb_sieges FROM association a LEFT JOIN membre m ON a.president_id=m.id LEFT JOIN siege s ON s.association_id=a.id WHERE 1=1";
$params = [];
if ($filtre_statut) { $sql .= " AND a.statut=?"; $params[] = $filtre_statut; }
$sql .= " GROUP BY a.id ORDER BY a.date_creation DESC";
$stmt = $db->prepare($sql); $stmt->execute($params); $associations = $stmt->fetchAll();

$pageTitle = 'Gestion des associations';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div class="breadcrumb"><a href="<?= BASE_URL ?>admin/index.php">Administration</a> / Associations</div>
    <h1><i class="fas fa-building"></i> Gestion des Associations</h1>
</div>
<div class="container py-20">
    <div class="filters-bar">
        <form method="GET" class="flex gap-10 items-center" style="flex-wrap:wrap;width:100%;">
            <div class="form-group mb-0"><label>Filtrer par statut</label>
                <select name="statut" class="form-control" onchange="this.form.submit()">
                    <option value="">Toutes</option>
                    <option value="en_attente" <?= $filtre_statut==='en_attente'?'selected':'' ?>>En attente</option>
                    <option value="active" <?= $filtre_statut==='active'?'selected':'' ?>>Active</option>
                    <option value="rejetee" <?= $filtre_statut==='rejetee'?'selected':'' ?>>Rejetée</option>
                    <option value="suspendue" <?= $filtre_statut==='suspendue'?'selected':'' ?>>Suspendue</option>
                </select>
            </div>
            <?php if ($filtre_statut): ?><div class="flex items-center"><a href="<?= BASE_URL ?>admin/associations.php" class="btn btn-outline">Réinitialiser</a></div><?php endif; ?>
        </form>
    </div>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Nom</th><th>Président</th><th>Sièges</th><th>Statut</th><th>Date</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if ($associations): foreach ($associations as $a): ?>
            <tr>
                <td>
                    <strong><?= htmlspecialchars($a['nom']) ?></strong>
                    <?php if (!empty($a['description'])): ?>
                    <br><small class="text-light"><?= htmlspecialchars(substr($a['description'], 0, 60)) ?>...</small>
                    <?php endif; ?>
                </td>
                <td><?= $a['pnom'] ? htmlspecialchars($a['pnom'].' '.$a['ppren']).'<br><small class="text-light">'.htmlspecialchars($a['pemail']).'</small>' : '—' ?></td>
                <td class="text-center"><span class="badge badge-info"><?= $a['nb_sieges'] ?></span></td>
                <td><span class="badge badge-<?= ['en_attente'=>'warning','active'=>'success','suspendue'=>'secondary','rejetee'=>'danger'][$a['statut']]??'secondary' ?>"><?= ucfirst($a['statut']) ?></span></td>
                <td><?= date('d/m/Y', strtotime($a['date_creation'])) ?></td>
                <td>
                    <div class="table-actions" style="flex-wrap:wrap;">
                        <!-- Bouton Voir Détail -->
                        <a href="<?= BASE_URL ?>admin/association_detail.php?id=<?= $a['id'] ?>" class="btn btn-outline btn-sm"><i class="fas fa-eye"></i> Détail</a>
                        <form method="POST" class="flex gap-10" style="flex-wrap:wrap;">
                            <input type="hidden" name="assoc_id" value="<?= $a['id'] ?>">
                            <?php if ($a['statut'] === 'en_attente'): ?>
                                <button name="action" value="valider" class="btn btn-success btn-sm" onclick="return confirm('Valider et activer cette association ?')"><i class="fas fa-check"></i> Valider</button>
                                <button name="action" value="rejeter" class="btn btn-warning btn-sm" onclick="return confirm('Rejeter cette demande ?')"><i class="fas fa-times"></i> Rejeter</button>
                            <?php elseif ($a['statut'] === 'active'): ?>
                                <button name="action" value="suspendre" class="btn btn-warning btn-sm" onclick="return confirm('Suspendre cette association ?')"><i class="fas fa-ban"></i></button>
                            <?php elseif (in_array($a['statut'],['rejetee','suspendue'])): ?>
                                <button name="action" value="reactiver" class="btn btn-success btn-sm" onclick="return confirm('Réactiver cette association ?')"><i class="fas fa-check"></i></button>
                            <?php endif; ?>
                            <button name="action" value="supprimer" class="btn btn-danger btn-sm" onclick="return confirm('Supprimer définitivement cette association et tous ses sièges ?')"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="6" class="text-center py-20 text-light">Aucune association.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
