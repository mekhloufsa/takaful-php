<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole(['president_siege']);
$db = getDB();
$uid = $_SESSION['user_id'];
$siege = $db->prepare("SELECT * FROM siege WHERE president_siege_id=?"); $siege->execute([$uid]); $siege = $siege->fetch();
if (!$siege) { flash('Siège introuvable.', 'error'); header('Location: ' . BASE_URL . 'dashboard.php'); exit; }
$sid = $siege['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $titre = trim($_POST['titre'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $type = $_POST['type'] ?? 'autre';
        $date_mission = $_POST['date_mission'] ?? null;
        if (empty($titre)) { flash('Titre obligatoire.', 'error'); header('Location: ' . BASE_URL . 'president_siege/missions.php'); exit; }
        $id = uuid();
        $db->prepare("INSERT INTO mission (id,siege_id,titre,description,type,date_mission,statut) VALUES (?,?,?,?,?,?,'planifiee')")->execute([$id,$sid,$titre,$description,$type,$date_mission?:null]);
        flash('Mission créée.', 'success');
    } elseif ($action === 'update_statut') {
        $mid = $_POST['mission_id'] ?? '';
        $statut = $_POST['statut'] ?? '';
        $validStatuts = ['planifiee','en_cours','terminee','annulee'];
        if ($mid && in_array($statut, $validStatuts)) {
            $db->prepare("UPDATE mission SET statut=? WHERE id=? AND siege_id=?")->execute([$statut,$mid,$sid]);
            flash('Statut mis à jour.', 'success');
        }
    }
    header('Location: ' . BASE_URL . 'president_siege/missions.php'); exit;
}

$missions = $db->prepare("SELECT * FROM mission WHERE siege_id=? ORDER BY date_mission DESC, date_creation DESC"); $missions->execute([$sid]); $missions = $missions->fetchAll();
$statusLabels = ['planifiee'=>'Planifiée','en_cours'=>'En cours','terminee'=>'Terminée','annulee'=>'Annulée'];
$statusBadge = ['planifiee'=>'info','en_cours'=>'warning','terminee'=>'success','annulee'=>'danger'];
$pageTitle = 'Gestion des missions';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div class="breadcrumb"><a href="<?= BASE_URL ?>president_siege/dashboard.php">Mon Siège</a> / Missions</div>
    <h1><i class="fas fa-tasks"></i> Gestion des Missions</h1>
    <p><?= htmlspecialchars($siege['nom']) ?></p>
</div>
<div class="container" style="padding:30px 20px;">
    <div style="display:flex;justify-content:flex-end;margin-bottom:20px;">
        <button onclick="openModal('createMission')" class="btn btn-primary"><i class="fas fa-plus"></i> Nouvelle mission</button>
    </div>

    <div id="createMission" class="modal-overlay">
        <div class="modal">
            <button class="modal-close" onclick="closeModal('createMission')"><i class="fas fa-times"></i></button>
            <h3><i class="fas fa-plus"></i> Créer une mission</h3>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="form-group"><label>Titre <span>*</span></label><input type="text" name="titre" class="form-control" required placeholder="Titre de la mission"></div>
                <div class="form-group"><label>Type</label>
                    <select name="type" class="form-control"><option value="distribution">Distribution</option><option value="transport">Transport</option><option value="sensibilisation">Sensibilisation</option><option value="autre">Autre</option></select>
                </div>
                <div class="form-group"><label>Date prévue</label><input type="datetime-local" name="date_mission" class="form-control"></div>
                <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
                <div class="form-actions"><button type="button" onclick="closeModal('createMission')" class="btn btn-outline">Annuler</button><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Créer</button></div>
            </form>
        </div>
    </div>

    <?php if ($missions): ?>
    <div class="cards-grid">
        <?php foreach ($missions as $m): ?>
        <div class="card">
            <div class="card-header">
                <div><h3 style="font-size:1rem;font-weight:800;"><?= htmlspecialchars($m['titre']) ?></h3><span class="badge badge-<?= $statusBadge[$m['statut']]??'secondary' ?>"><?= $statusLabels[$m['statut']]??$m['statut'] ?></span></div>
                <span style="background:var(--bg);padding:5px 10px;border-radius:6px;font-size:0.8rem;color:var(--text-light);"><?= ucfirst($m['type']) ?></span>
            </div>
            <div class="card-body">
                <?php if ($m['description']): ?><p style="color:var(--text-light);font-size:0.88rem;margin-bottom:12px;"><?= htmlspecialchars(substr($m['description'],0,100)) ?></p><?php endif; ?>
                <p><i class="fas fa-calendar" style="color:var(--primary);"></i> <?= $m['date_mission']?date('d/m/Y H:i',strtotime($m['date_mission'])):'Date à définir' ?></p>
            </div>
            <div class="card-footer">
                <form method="POST" style="display:flex;gap:6px;flex-wrap:wrap;">
                    <input type="hidden" name="action" value="update_statut">
                    <input type="hidden" name="mission_id" value="<?= $m['id'] ?>">
                    <select name="statut" class="form-control" style="width:auto;padding:6px 10px;">
                        <?php foreach ($statusLabels as $k=>$v): ?><option value="<?= $k ?>" <?= $m['statut']===$k?'selected':'' ?>><?= $v ?></option><?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i></button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state card" style="padding:60px;"><i class="fas fa-tasks" style="opacity:0.3;color:var(--primary);"></i><h3>Aucune mission créée</h3><button onclick="openModal('createMission')" class="btn btn-primary" style="margin-top:16px;"><i class="fas fa-plus"></i> Créer une mission</button></div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
