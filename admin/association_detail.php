<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$db  = getDB();
$aid = $_GET['id'] ?? '';
if (!$aid) { header('Location: ' . BASE_URL . 'admin/associations.php'); exit; }

$assoc = $db->prepare("SELECT a.*, m.nom AS pnom, m.prenom AS ppren, m.email AS pemail, m.telephone AS ptel, m.wilaya AS pwilaya, m.nin AS pnin FROM association a LEFT JOIN membre m ON a.president_id=m.id WHERE a.id=?");
$assoc->execute([$aid]);
$assoc = $assoc->fetch();
if (!$assoc) { flash('Association introuvable.', 'error'); header('Location: ' . BASE_URL . 'admin/associations.php'); exit; }

// Sièges de cette association
$sieges = $db->prepare("SELECT s.*, m.nom AS rnom, m.prenom AS rpren, m.email AS remail FROM siege s LEFT JOIN membre m ON s.president_siege_id=m.id WHERE s.association_id=? ORDER BY s.date_creation DESC");
$sieges->execute([$aid]);
$sieges = $sieges->fetchAll();

// Actions sur les sièges et l'association
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'supprimer_siege') {
        $sid = $_POST['siege_id'] ?? '';
        if ($sid) {
            // Rétrograder le responsable
            $s = $db->prepare("SELECT president_siege_id FROM siege WHERE id=?");
            $s->execute([$sid]);
            $s = $s->fetch();
            if ($s && $s['president_siege_id']) {
                $db->prepare("UPDATE membre SET role='membre' WHERE id=? AND role='president_siege'")->execute([$s['president_siege_id']]);
            }
            $db->prepare("DELETE FROM siege WHERE id=?")->execute([$sid]);
            flash('Siège supprimé.', 'success');
        }
        header('Location: ' . BASE_URL . 'admin/association_detail.php?id=' . $aid); exit;
    }

    if ($action === 'supprimer_association') {
        // Rétrograder le rôle du président
        if ($assoc['president_id']) {
            $db->prepare("UPDATE membre SET role='membre' WHERE id=?")->execute([$assoc['president_id']]);
        }
        $db->prepare("DELETE FROM association WHERE id=?")->execute([$aid]);
        flash('Association et tous ses sièges supprimés avec succès.', 'success');
        header('Location: ' . BASE_URL . 'admin/associations.php'); exit;
    }
}

$statusBadge = ['en_attente'=>'warning','active'=>'success','suspendue'=>'secondary','rejetee'=>'danger'];
$pageTitle   = 'Détail association — ' . htmlspecialchars($assoc['nom']);
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div class="breadcrumb">
        <a href="<?= BASE_URL ?>admin/index.php">Administration</a> /
        <a href="<?= BASE_URL ?>admin/associations.php">Associations</a> /
        <?= htmlspecialchars($assoc['nom']) ?>
    </div>
    <h1><i class="fas fa-building"></i> <?= htmlspecialchars($assoc['nom']) ?></h1>
    <p>Statut : <span class="badge badge-<?= $statusBadge[$assoc['statut']] ?? 'secondary' ?>"><?= ucfirst($assoc['statut']) ?></span></p>
</div>

<div class="container py-20">
    <div class="two-col-grid">

        <!-- Colonne gauche : Informations de l'association -->
        <div>
            <div class="card mb-20">
                <div class="card-header">
                    <h3><i class="fas fa-info-circle icon-primary-text"></i> Informations de la demande</h3>
                </div>
                <div class="card-body">
                    <table style="width:100%; border-collapse:collapse;">
                        <tr style="border-bottom:1px solid var(--border);"><td style="padding:10px 0; color:var(--text-light); width:40%"><strong>Nom</strong></td><td><?= htmlspecialchars($assoc['nom']) ?></td></tr>
                        <tr style="border-bottom:1px solid var(--border);"><td style="padding:10px 0; color:var(--text-light)"><strong>Date demande</strong></td><td><?= date('d/m/Y H:i', strtotime($assoc['date_creation'])) ?></td></tr>
                        <tr style="border-bottom:1px solid var(--border);"><td style="padding:10px 0; color:var(--text-light)"><strong>Statut</strong></td><td><span class="badge badge-<?= $statusBadge[$assoc['statut']] ?? 'secondary' ?>"><?= ucfirst($assoc['statut']) ?></span></td></tr>
                        <tr><td style="padding:10px 0; color:var(--text-light)"><strong>Description</strong></td><td><?= nl2br(htmlspecialchars($assoc['description'] ?? '—')) ?></td></tr>
                    </table>
                </div>
            </div>

            <div class="card mb-20">
                <div class="card-header">
                    <h3><i class="fas fa-user icon-primary-text"></i> Demandeur (Président)</h3>
                </div>
                <div class="card-body">
                    <table style="width:100%; border-collapse:collapse;">
                        <tr style="border-bottom:1px solid var(--border);"><td style="padding:8px 0; color:var(--text-light); width:40%"><strong>Nom complet</strong></td><td><?= $assoc['pnom'] ? htmlspecialchars($assoc['pnom'].' '.$assoc['ppren']) : '—' ?></td></tr>
                        <tr style="border-bottom:1px solid var(--border);"><td style="padding:8px 0; color:var(--text-light)"><strong>Email</strong></td><td><?= htmlspecialchars($assoc['pemail'] ?? '—') ?></td></tr>
                        <tr style="border-bottom:1px solid var(--border);"><td style="padding:8px 0; color:var(--text-light)"><strong>Téléphone</strong></td><td><?= htmlspecialchars($assoc['ptel'] ?? '—') ?></td></tr>
                        <tr style="border-bottom:1px solid var(--border);"><td style="padding:8px 0; color:var(--text-light)"><strong>Wilaya</strong></td><td><?= htmlspecialchars($assoc['pwilaya'] ?? '—') ?></td></tr>
                        <tr><td style="padding:8px 0; color:var(--text-light)"><strong>NIN</strong></td><td><?= htmlspecialchars($assoc['pnin'] ?? '—') ?></td></tr>
                    </table>
                </div>
            </div>

            <!-- Document joint -->
            <div class="card mb-20">
                <div class="card-header">
                    <h3><i class="fas fa-file-alt icon-primary-text"></i> Document officiel joint</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($assoc['document_path'])): ?>
                        <div style="display:flex; align-items:center; gap:14px; padding:14px; background:var(--primary-light); border-radius:var(--radius-sm);">
                            <i class="fas fa-file-pdf" style="font-size:2rem; color:var(--primary);"></i>
                            <div>
                                <p style="font-weight:700; margin-bottom:4px;">Document constitutif</p>
                                <a href="<?= BASE_URL . htmlspecialchars($assoc['document_path']) ?>" target="_blank" class="btn btn-primary btn-sm">
                                    <i class="fas fa-eye"></i> Visualiser / Télécharger
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="empty-state" style="padding:20px;">
                            <i class="fas fa-file-excel" style="opacity:0.3;"></i>
                            <p>Aucun document joint.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Actions de Décision Admin -->
            <?php if ($assoc['statut'] === 'en_attente'): ?>
            <div class="card mb-20">
                <div class="card-header"><h3><i class="fas fa-gavel icon-primary-text"></i> Décision</h3></div>
                <div class="card-body">
                    <form method="POST" action="<?= BASE_URL ?>admin/associations.php" class="flex gap-10">
                        <input type="hidden" name="assoc_id" value="<?= $assoc['id'] ?>">
                        <button name="action" value="valider" class="btn btn-success btn-sm" onclick="return confirm('Valider et activer cette association ? Le président recevra ses droits.')"><i class="fas fa-check"></i> Valider et activer</button>
                        <button name="action" value="rejeter" class="btn btn-danger btn-sm" onclick="return confirm('Rejeter cette demande ?')"><i class="fas fa-times"></i> Rejeter</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Suppression de l'association -->
            <div class="card" style="border:1px solid #e74c3c; background:rgba(231, 76, 60, 0.02);">
                <div class="card-header" style="border-bottom:1px solid #e74c3c;">
                    <h3 style="color:#c0392b;"><i class="fas fa-exclamation-triangle"></i> Zone de danger</h3>
                </div>
                <div class="card-body" style="padding:15px; text-align:center;">
                    <p style="margin-bottom:15px; font-size:0.9rem;">Supprimer définitivement l'association et tous ses sièges. Cette action est irréversible.</p>
                    <form method="POST" onsubmit="return confirm('⚠️ Êtes-vous sûr de vouloir supprimer définitivement cette association ? Tous ses sièges locaux seront supprimés !')">
                        <input type="hidden" name="action" value="supprimer_association">
                        <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Supprimer l'Association</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Colonne droite : Liste & Gestion (Suppression) des sièges -->
        <div>
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-map-marker-alt icon-primary-text"></i> Sièges de l'association</h3>
                </div>
                <?php if ($sieges): ?>
                <div class="table-wrapper" style="box-shadow:none;">
                    <table>
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Wilaya</th>
                                <th>Responsable</th>
                                <th>Statut</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($sieges as $s): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($s['nom']) ?></strong></td>
                            <td><?= htmlspecialchars($s['wilaya']) ?></td>
                            <td><?= $s['rnom'] ? htmlspecialchars($s['rnom'].' '.$s['rpren']) : '<span class="badge badge-warning">Non assigné</span>' ?></td>
                            <td><span class="badge badge-<?= $s['statut']==='actif'?'success':'secondary' ?>"><?= ucfirst($s['statut']) ?></span></td>
                            <td>
                                <div style="display:flex; gap:5px; align-items:center;">
                                    <a href="<?= BASE_URL ?>sieges/detail.php?id=<?= $s['id'] ?>" class="btn btn-outline btn-sm" title="Voir le détail"><i class="fas fa-eye"></i></a>
                                    <form method="POST" onsubmit="return confirm('⚠️ Supprimer définitivement ce siège ?')" style="margin:0;">
                                        <input type="hidden" name="action" value="supprimer_siege">
                                        <input type="hidden" name="siege_id" value="<?= $s['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" title="Supprimer le siège"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="card-body empty-state" style="padding:30px;">
                    <i class="fas fa-map-marker-alt" style="opacity:0.3; font-size:2.5rem; margin-bottom:10px;"></i>
                    <p>Aucun siège créé pour cette association.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
