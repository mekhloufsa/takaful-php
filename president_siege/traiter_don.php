<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['president_siege']);
$db = getDB();
$uid = $_SESSION['user_id'];
$did = $_GET['id'] ?? '';

// Vérifier le siège du président
$stmt = $db->prepare("SELECT id FROM siege WHERE president_siege_id=?");
$stmt->execute([$uid]);
$siege = $stmt->fetch();
if (!$siege) { flash('Accès refusé.', 'error'); header('Location: ../index.php'); exit; }

$stmtD = $db->prepare("SELECT * FROM don WHERE id=? AND siege_id=?");
$stmtD->execute([$did, $siege['id']]);
$don = $stmtD->fetch();
if (!$don) { flash('Don introuvable.', 'error'); header('Location: dons.php'); exit; }

// Liste des membres du siège (pour assignation) - En excluant ceux qui ont une mission active non terminée
$membres = $db->prepare("SELECT ma.id as ma_id, m.nom, m.prenom 
                         FROM membre_association ma 
                         JOIN membre m ON ma.membre_id=m.id 
                         WHERE ma.siege_id=? AND ma.statut='actif'
                         AND ma.id NOT IN (
                             SELECT DISTINCT membre_association_id 
                             FROM assignation 
                             WHERE statut IN ('assignee', 'en_cours') 
                             AND membre_association_id IS NOT NULL
                         )");
$membres->execute([$siege['id']]);
$membres = $membres->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $message = trim($_POST['message_decision'] ?? '');
    $membre_id = $_POST['membre_association_id'] ?? '';

    if (empty($message)) {
        flash('Le message de justification est obligatoire.', 'error');
    } else {
        if ($action === 'accepter') {
            if ($don['type'] === 'financier') {
                $db->beginTransaction();
                try {
                    // Pour un don financier, on passe directement au statut 'collecte' (terminé) sans assignation
                    $db->prepare("UPDATE don SET statut='collecte', message_decision=? WHERE id=?")->execute([$message, $did]);
                    $db->commit();
                    flash('Paiement confirmé. Le don financier a été enregistré comme collecté.', 'success');
                    header("Location: dons.php"); exit;
                } catch(Exception $e) {
                    $db->rollBack();
                    flash('Erreur lors de la validation du don financier.', 'error');
                }
            } else {
                if (empty($membre_id)) {
                    flash('Veuillez sélectionner un membre pour traiter cette collecte/réception.', 'error');
                } else {
                    $db->beginTransaction();
                    try {
                        // On le passe à 'confirme' (qui veut dire qu'il est en cours de traitement/collecte)
                        $db->prepare("UPDATE don SET statut='confirme', message_decision=? WHERE id=?")->execute([$message, $did]);
                        $aid = uuid();
                        if ($membre_id === 'president_self') {
                            $db->prepare("INSERT INTO assignation (id, membre_association_id, president_assigne_id, don_id, statut) VALUES (?, NULL, ?, ?, 'assignee')")->execute([$aid, $uid, $did]);
                        } else {
                            $db->prepare("INSERT INTO assignation (id, membre_association_id, president_assigne_id, don_id, statut) VALUES (?, ?, NULL, ?, 'assignee')")->execute([$aid, $membre_id, $did]);
                        }
                        $db->commit();
                        flash('Don accepté et mission assignée avec succès.', 'success');
                        header("Location: dons.php"); exit;
                    } catch(Exception $e) {
                        $db->rollBack();
                        flash('Erreur lors de l\'assignation.', 'error');
                    }
                }
            }
        } elseif ($action === 'refuser') {
            $db->prepare("UPDATE don SET statut='annule', message_decision=? WHERE id=?")->execute([$message, $did]);
            flash('Don refusé/annulé.', 'success');
            header("Location: dons.php"); exit;
        }
    }
}

// Voir si assignée
$assignation = $db->prepare("SELECT a.*, 
                                    COALESCE(m_vol.nom, m_pres.nom) as nom, 
                                    COALESCE(m_vol.prenom, m_pres.prenom) as prenom 
                             FROM assignation a 
                             LEFT JOIN membre_association ma ON a.membre_association_id=ma.id 
                             LEFT JOIN membre m_vol ON ma.membre_id=m_vol.id 
                             LEFT JOIN membre m_pres ON a.president_assigne_id=m_pres.id 
                             WHERE a.don_id=?");
$assignation->execute([$did]);
$assign = $assignation->fetch();

$pageTitle = 'Traiter le don';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div class="breadcrumb"><a href="dons.php">Dons</a> / Traitement</div>
    <h1>Traiter le don</h1>
</div>
<div class="container py-50" style="max-width:800px;">
    <div class="card mb-20">
        <div class="card-header"><h3>Détails du don</h3></div>
        <div class="card-body">
            <p><strong>Type :</strong> <?= ucfirst($don['type']) ?></p>
            <p><strong>Catégorie :</strong> <?= htmlspecialchars($don['categorie'] ?? '-') ?></p>
            <p><strong>Montant :</strong> <?= $don['montant'] > 0 ? number_format($don['montant'], 2, ',', ' ') . ' DZD' : '-' ?></p>
            <p><strong>Description :</strong> <?= nl2br(htmlspecialchars($don['description'])) ?></p>
            <p><strong>Statut :</strong> <span class="badge badge-secondary"><?= ucfirst($don['statut']) ?></span></p>
        </div>
    </div>

    <?php if ($don['statut'] === 'en_attente'): ?>
    <div class="card">
        <div class="card-header"><h3>Décision</h3></div>
        <div class="card-body">
            <form method="POST">
                <div class="form-group">
                    <label>Action</label>
                    <select name="action" id="actionSelect" class="form-control" onchange="toggleAssign()" required>
                        <option value="">-- Choisir --</option>
                        <?php if ($don['type'] === 'financier'): ?>
                        <option value="accepter">Accepter (Géré directement par le responsable)</option>
                        <?php else: ?>
                        <option value="accepter">Accepter & Assigner à un membre pour la collecte/réception</option>
                        <?php endif; ?>
                        <option value="refuser">Refuser</option>
                    </select>
                </div>
                
                <?php if ($don['type'] !== 'financier'): ?>
                <div class="form-group" id="assignGroup" style="display:none;">
                    <label>Assigner à un bénévole du siège</label>
                    <select name="membre_association_id" class="form-control">
                        <option value="">-- Sélectionner un bénévole --</option>
                        <option value="president_self" style="font-weight: bold; color: var(--primary);">Moi-même (Responsable de Siège)</option>
                        <?php foreach ($membres as $m): ?>
                        <option value="<?= $m['ma_id'] ?>"><?= htmlspecialchars($m['nom'].' '.$m['prenom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="form-group">
                    <label>Message de justification (Obligatoire)</label>
                    <textarea name="message_decision" class="form-control" rows="3" required placeholder="Expliquez pourquoi le don est accepté ou refusé..."></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-save"></i> Enregistrer la décision</button>
            </form>
        </div>
    </div>
    <script>
    function toggleAssign() {
        var assignGrp = document.getElementById('assignGroup');
        if (assignGrp) {
            assignGrp.style.display = document.getElementById('actionSelect').value === 'accepter' ? 'block' : 'none';
        }
    }
    </script>
    <?php else: ?>
    <div class="card">
        <div class="card-header"><h3>Historique de traitement</h3></div>
        <div class="card-body">
            <p><strong>Message de décision :</strong> <?= nl2br(htmlspecialchars($don['message_decision'] ?? '')) ?></p>
            <?php if ($assign): ?>
                <hr>
                <p><strong>Assigné à :</strong> <?= htmlspecialchars($assign['nom'].' '.$assign['prenom']) ?></p>
                <p><strong>Statut de la mission :</strong> <?= ucfirst($assign['statut']) ?></p>
                <?php if ($assign['date_rendezvous']): ?>
                    <p><strong>Date de Rendez-vous :</strong> <?= date('d/m/Y H:i', strtotime($assign['date_rendezvous'])) ?></p>
                    <p><strong>Note :</strong> <?= nl2br(htmlspecialchars($assign['note_rendezvous'])) ?></p>
                <?php endif; ?>
                <?php if ($assign['message_non_resolu']): ?>
                    <p><strong>Raison de l'échec :</strong> <?= nl2br(htmlspecialchars($assign['message_non_resolu'])) ?></p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
