<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$db = getDB();
$admin = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $titre = trim($_POST['titre'] ?? '');
        $contenu = trim($_POST['contenu'] ?? '');
        $date_debut = $_POST['date_debut'] ?? null;
        $date_fin = $_POST['date_fin'] ?? null;
        if (empty($titre) || empty($contenu)) { flash('Titre et contenu obligatoires.', 'error'); header('Location: ' . BASE_URL . 'admin/annonces.php'); exit; }
        $id = uuid();
        $db->prepare("INSERT INTO annonce (id,titre,contenu,admin_id,date_debut,date_fin) VALUES (?,?,?,?,?,?)")->execute([$id,$titre,$contenu,$admin['id'],$date_debut?:null,$date_fin?:null]);
        flash('Annonce créée.', 'success');
    } elseif ($action === 'modifier') {
        $aid = $_POST['annonce_id'] ?? '';
        $titre = trim($_POST['titre'] ?? '');
        $contenu = trim($_POST['contenu'] ?? '');
        $date_fin = $_POST['date_fin'] ?? null;
        if ($aid && $titre && $contenu) { $db->prepare("UPDATE annonce SET titre=?,contenu=?,date_fin=? WHERE id=?")->execute([$titre,$contenu,$date_fin?:null,$aid]); flash('Annonce modifiée.', 'success'); }
    } elseif ($action === 'supprimer') {
        $aid = $_POST['annonce_id'] ?? '';
        if ($aid) { $db->prepare("DELETE FROM annonce WHERE id=?")->execute([$aid]); flash('Annonce supprimée.', 'success'); }
    }
    header('Location: ' . BASE_URL . 'admin/annonces.php'); exit;
}

$annonces = $db->query("SELECT a.*, adm.email as admin_email FROM annonce a LEFT JOIN administrateur adm ON a.admin_id=adm.id ORDER BY a.date_creation DESC")->fetchAll();
$pageTitle = 'Gestion des annonces';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div class="breadcrumb"><a href="<?= BASE_URL ?>admin/index.php">Administration</a> / Annonces</div>
    <h1><i class="fas fa-bullhorn"></i> Annonces de Maintenance</h1>
</div>
<div class="container py-20">
    <div class="card mb-20">
        <div class="card-header"><h3><i class="fas fa-plus"></i> Créer une annonce</h3></div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="form-row">
                    <div class="form-group"><label>Titre <span>*</span></label><input type="text" name="titre" class="form-control" required placeholder="Titre de l'annonce"></div>
                    <div class="form-group"><label>Date de fin (optionnel)</label><input type="date" name="date_fin" class="form-control"></div>
                </div>
                <div class="form-group"><label>Contenu <span>*</span></label><textarea name="contenu" class="form-control" rows="3" required placeholder="Message affiché aux utilisateurs..."></textarea></div>
                <div class="form-actions"><button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Publier l'annonce</button></div>
            </form>
        </div>
    </div>

    <?php if ($annonces): ?>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Titre</th><th>Contenu</th><th>Fin</th><th>Créée le</th><th>Statut</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($annonces as $a): ?>
            <?php $active = !$a['date_fin'] || strtotime($a['date_fin']) >= time(); ?>
            <tr>
                <td><strong><?= htmlspecialchars($a['titre']) ?></strong></td>
                <td style="max-width:200px;"><?= htmlspecialchars(substr($a['contenu'],0,80)) ?></td>
                <td><?= $a['date_fin']?date('d/m/Y',strtotime($a['date_fin'])):'Permanente' ?></td>
                <td><?= date('d/m/Y', strtotime($a['date_creation'])) ?></td>
                <td><span class="badge badge-<?= $active?'success':'secondary' ?>"><?= $active?'Active':'Expirée' ?></span></td>
                <td>
                    <form method="POST" class="inline">
                        <input type="hidden" name="action" value="supprimer">
                        <input type="hidden" name="annonce_id" value="<?= $a['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Supprimer cette annonce ?')"><i class="fas fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state card py-50">
        <i class="fas fa-bullhorn icon-secondary-text" style="opacity:0.3;"></i>
        <h3>Aucune annonce</h3>
    </div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
