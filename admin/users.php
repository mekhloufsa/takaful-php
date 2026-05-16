<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $mid = $_POST['membre_id'] ?? '';
    if ($mid) {
        if ($action === 'supprimer') {
            $db->prepare("DELETE FROM membre WHERE id=?")->execute([$mid]);
            flash('Membre supprimé.', 'success');
        } elseif ($action === 'suspendre') {
            $db->prepare("UPDATE membre SET statut='suspendu' WHERE id=?")->execute([$mid]);
            flash('Membre suspendu.', 'success');
        } elseif ($action === 'activer') {
            $db->prepare("UPDATE membre SET statut='actif' WHERE id=?")->execute([$mid]);
            flash('Membre activé.', 'success');
        }
    }
    header('Location: ' . BASE_URL . 'admin/users.php'); exit;
}

$search = trim($_GET['q'] ?? '');
$filtre_role = $_GET['role'] ?? '';
$filtre_statut = $_GET['statut'] ?? '';
$sql = "SELECT * FROM membre WHERE 1=1";
$params = [];
if ($search) { $sql .= " AND (nom LIKE ? OR prenom LIKE ? OR email LIKE ?)"; $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]); }
if ($filtre_role) { $sql .= " AND role=?"; $params[] = $filtre_role; }
if ($filtre_statut) { $sql .= " AND statut=?"; $params[] = $filtre_statut; }
$sql .= " ORDER BY date_inscription DESC";
$stmt = $db->prepare($sql); $stmt->execute($params); $membres = $stmt->fetchAll();

$pageTitle = 'Gestion des utilisateurs';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div class="breadcrumb"><a href="<?= BASE_URL ?>admin/index.php">Administration</a> / Utilisateurs</div>
    <h1><i class="fas fa-users"></i> Gestion des Utilisateurs</h1>
    <p><?= count($membres) ?> utilisateur(s) trouvé(s)</p>
</div>
<div class="container py-20">
    <div class="filters-bar">
        <form method="GET" class="flex gap-10 items-center" style="flex-wrap:wrap;width:100%;">
            <div class="form-group" style="flex:1;"><label>Rechercher</label><input type="text" name="q" class="form-control mb-0" value="<?= htmlspecialchars($search) ?>" placeholder="Nom, prénom, email..."></div>
            <div class="form-group"><label>Rôle</label>
                <select name="role" class="form-control">
                    <option value="">Tous</option>
                    <option value="membre" <?= $filtre_role==='membre'?'selected':'' ?>>Membre</option>
                    <option value="membre_association" <?= $filtre_role==='membre_association'?'selected':'' ?>>Membre d'association</option>
                    <option value="president_siege" <?= $filtre_role==='president_siege'?'selected':'' ?>>Président de siège</option>
                    <option value="president_association" <?= $filtre_role==='president_association'?'selected':'' ?>>Président d'association</option>
                </select>
            </div>
            <div class="form-group"><label>Statut</label>
                <select name="statut" class="form-control">
                    <option value="">Tous</option>
                    <option value="actif" <?= $filtre_statut==='actif'?'selected':'' ?>>Actif</option>
                    <option value="suspendu" <?= $filtre_statut==='suspendu'?'selected':'' ?>>Suspendu</option>
                    <option value="inactif" <?= $filtre_statut==='inactif'?'selected':'' ?>>Inactif</option>
                </select>
            </div>
            <div class="flex gap-10">
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filtrer</button>
                <a href="<?= BASE_URL ?>admin/users.php" class="btn btn-outline">Réinitialiser</a>
            </div>
        </form>
    </div>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Nom</th><th>Email</th><th>Wilaya</th><th>Rôle</th><th>Statut</th><th>Inscription</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if ($membres): foreach ($membres as $m): ?>
            <tr>
                <td><strong><?= htmlspecialchars($m['nom'].' '.$m['prenom']) ?></strong></td>
                <td><?= htmlspecialchars($m['email']) ?></td>
                <td><?= htmlspecialchars($m['wilaya'] ?? '—') ?></td>
                <td><span class="badge badge-info"><?= ucfirst(str_replace('_',' ',$m['role'])) ?></span></td>
                <td><span class="badge badge-<?= $m['statut']==='actif'?'success':($m['statut']==='suspendu'?'warning':'secondary') ?>"><?= ucfirst($m['statut']) ?></span></td>
                <td><?= date('d/m/Y', strtotime($m['date_inscription'])) ?></td>
                <td>
                    <form method="POST" class="flex gap-10" style="flex-wrap:wrap;">
                        <input type="hidden" name="membre_id" value="<?= $m['id'] ?>">
                        <?php if ($m['statut'] === 'actif'): ?>
                        <button name="action" value="suspendre" type="submit" class="btn btn-warning btn-sm" onclick="return confirm('Suspendre ce membre ?')"><i class="fas fa-ban"></i></button>
                        <?php elseif ($m['statut'] === 'suspendu'): ?>
                        <button name="action" value="activer" type="submit" class="btn btn-success btn-sm"><i class="fas fa-check"></i></button>
                        <?php endif; ?>
                        <button name="action" value="supprimer" type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Supprimer définitivement ce membre ?')"><i class="fas fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="7" class="text-center py-20 text-light">Aucun utilisateur trouvé.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
