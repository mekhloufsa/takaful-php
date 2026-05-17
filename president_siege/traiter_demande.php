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

$stmtD = $db->prepare("SELECT * FROM demande_aide WHERE id=? AND siege_id=?");
$stmtD->execute([$did, $siege['id']]);
$demande = $stmtD->fetch();
if (!$demande) { flash('Demande introuvable.', 'error'); header('Location: demandes.php'); exit; }

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
            if (empty($membre_id)) {
                flash('Veuillez sélectionner un membre pour traiter cette demande.', 'error');
            } else {
                $db->beginTransaction();
                try {
                    $db->prepare("UPDATE demande_aide SET statut='en_cours', message_decision=? WHERE id=?")->execute([$message, $did]);
                    $aid = uuid();
                    if ($membre_id === 'president_self') {
                        $db->prepare("INSERT INTO assignation (id, membre_association_id, president_assigne_id, demande_id, statut) VALUES (?, NULL, ?, ?, 'assignee')")->execute([$aid, $uid, $did]);
                    } else {
                        $db->prepare("INSERT INTO assignation (id, membre_association_id, president_assigne_id, demande_id, statut) VALUES (?, ?, NULL, ?, 'assignee')")->execute([$aid, $membre_id, $did]);
                    }
                    $db->commit();
                    flash('Demande acceptée et assignée avec succès.', 'success');
                    header("Location: demandes.php"); exit;
                } catch(Exception $e) {
                    $db->rollBack();
                    flash('Erreur lors de l\'assignation.', 'error');
                }
            }
        } elseif ($action === 'refuser') {
            $db->prepare("UPDATE demande_aide SET statut='refusee', message_decision=? WHERE id=?")->execute([$message, $did]);
            flash('Demande refusée.', 'success');
            header("Location: demandes.php"); exit;
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
                             WHERE a.demande_id=?");
$assignation->execute([$did]);
$assign = $assignation->fetch();

$pageTitle = 'Traiter la demande';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div class="breadcrumb"><a href="demandes.php">Demandes</a> / Traitement</div>
    <h1>Traiter la demande</h1>
</div>
<div class="container py-50" style="max-width:800px;">
    <div class="card mb-20">
        <div class="card-header"><h3>Détails de la demande</h3></div>
        <div class="card-body">
            <p><strong>Sujet :</strong> <?= htmlspecialchars($demande['sujet']) ?></p>
            <p><strong>Type :</strong> <?= ucfirst($demande['type_aide']) ?></p>
            <p><strong>Description :</strong> <?= nl2br(htmlspecialchars($demande['description'])) ?></p>
            <p><strong>Statut :</strong> <span class="badge badge-secondary"><?= ucfirst($demande['statut']) ?></span></p>
            <?php if (!empty($demande['document_path'])): ?>
                <p style="margin-top: 15px;"><strong>Pièce jointe :</strong><br>
                    <a href="<?= BASE_URL . htmlspecialchars($demande['document_path']) ?>" target="_blank" class="btn btn-sm btn-outline mt-5" style="display:inline-block;">
                        <i class="fas fa-paperclip"></i> Ouvrir le document joint
                    </a>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($demande['statut'] === 'soumise'): ?>
    <div class="card">
        <div class="card-header"><h3>Décision</h3></div>
        <div class="card-body">
            <form method="POST">
                <div class="form-group">
                    <label>Action</label>
                    <select name="action" id="actionSelect" class="form-control" onchange="toggleAssign()" required>
                        <option value="">-- Choisir --</option>
                        <option value="accepter">Accepter & Assigner à un membre</option>
                        <option value="refuser">Refuser</option>
                    </select>
                </div>
                
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

                <div class="form-group">
                    <label>Message de justification (Obligatoire)</label>
                    <textarea name="message_decision" class="form-control" rows="3" required placeholder="Expliquez pourquoi la demande est acceptée ou refusée..."></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-save"></i> Enregistrer la décision</button>
            </form>
        </div>
    </div>
    <script>
    function toggleAssign() {
        document.getElementById('assignGroup').style.display = document.getElementById('actionSelect').value === 'accepter' ? 'block' : 'none';
    }
    </script>
    <?php else: ?>
    <div class="card">
        <div class="card-header"><h3>Historique de traitement</h3></div>
        <div class="card-body">
            <p><strong>Message de décision :</strong> <?= nl2br(htmlspecialchars($demande['message_decision'] ?? '')) ?></p>
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
