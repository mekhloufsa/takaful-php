<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if (!hasRole(['president_siege'])) {
    flash('Accès refusé.', 'error');
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}
$db = getDB();
$userId = $_SESSION['user_id'];

// Get the siege managed by this president
$stmt = $db->prepare("SELECT id, nom, association_id FROM siege WHERE president_siege_id = ? LIMIT 1");
$stmt->execute([$userId]);
$siege = $stmt->fetch();

if (!$siege) {
    flash('Vous n\'êtes assigné à aucun siège en tant que responsable.', 'error');
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

// Handle accept/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['ma_id'])) {
    $ma_id = $_POST['ma_id'];
    $action = $_POST['action']; // accepter or rejeter
    $message_decision = trim($_POST['message_decision'] ?? '');

    if (empty($message_decision)) {
        flash('Le message de justification est obligatoire.', 'error');
    } else {
        // Fetch candidate details
        $stmtMA = $db->prepare("SELECT ma.*, m.id as mid FROM membre_association ma JOIN membre m ON ma.membre_id=m.id WHERE ma.id=? AND ma.siege_id=?");
        $stmtMA->execute([$ma_id, $siege['id']]);
        $ma = $stmtMA->fetch();

        if ($ma) {
            if ($action === 'accepter') {
                $db->beginTransaction();
                try {
                    $db->prepare("UPDATE membre_association SET statut='actif', date_adhesion=CURRENT_DATE, message_decision=? WHERE id=?")->execute([$message_decision, $ma_id]);
                    
                    // Update user role if they are a simple membre
                    $userCheck = $db->prepare("SELECT role FROM membre WHERE id=?");
                    $userCheck->execute([$ma['mid']]);
                    if ($userCheck->fetchColumn() === 'membre') {
                        $db->prepare("UPDATE membre SET role='membre_association' WHERE id=?")->execute([$ma['mid']]);
                    }
                    $db->commit();
                    flash('Candidature acceptée ! Le membre a été ajouté au siège.', 'success');
                } catch(Exception $e) {
                    $db->rollBack();
                    flash('Erreur lors de l\'acceptation.', 'error');
                }
            } elseif ($action === 'rejeter') {
                $db->prepare("UPDATE membre_association SET statut='inactif', message_decision=? WHERE id=?")->execute([$message_decision, $ma_id]);
                flash('Candidature refusée.', 'success');
            }
            header('Location: ' . BASE_URL . 'president_siege/membres.php');
            exit;
        } else {
            flash('Candidature introuvable.', 'error');
        }
    }
}

// Fetch members of this siege
$membres = $db->prepare("SELECT ma.*, m.nom, m.prenom, m.email, m.telephone, m.wilaya, m.role 
                         FROM membre_association ma 
                         JOIN membre m ON ma.membre_id=m.id 
                         WHERE ma.siege_id=? 
                         ORDER BY FIELD(ma.statut, 'en_attente', 'actif', 'inactif'), ma.date_adhesion DESC");
$membres->execute([$siege['id']]);
$membresList = $membres->fetchAll();

$pageTitle = 'Gestion des Membres — ' . htmlspecialchars($siege['nom']);
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div class="breadcrumb"><a href="<?= BASE_URL ?>president_siege/dashboard.php">Mon Siège</a> / Bénévoles</div>
    <h1><i class="fas fa-users"></i> Gestion des Bénévoles</h1>
    <p>Siège : <?= htmlspecialchars($siege['nom']) ?></p>
</div>

<div class="container py-50">
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-list icon-primary-text"></i> Liste des Candidatures et Membres</h3>
        </div>
        <div class="card-body">
            <?php if (empty($membresList)): ?>
                <div class="empty-state text-center py-30">
                    <i class="fas fa-users-slash" style="font-size: 3rem; opacity: 0.3;"></i>
                    <h3 class="mt-10">Aucun bénévole</h3>
                    <p>Aucun citoyen n'a encore postulé pour rejoindre votre siège.</p>
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nom & Prénom</th>
                            <th>Email & Téléphone</th>
                            <th>Wilaya</th>
                            <th>Statut</th>
                            <th>Pièce Jointe</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($membresList as $m): ?>
                            <tr style="<?= $m['statut'] === 'en_attente' ? 'background: rgba(243, 156, 18, 0.05);' : '' ?>">
                                <td>
                                    <strong><?= htmlspecialchars($m['nom'] . ' ' . $m['prenom']) ?></strong>
                                    <br><small class="text-light">Rôle : <?= ucfirst(str_replace('_', ' ', $m['role'])) ?></small>
                                </td>
                                <td>
                                    <i class="fas fa-envelope"></i> <?= htmlspecialchars($m['email']) ?>
                                    <?php if ($m['telephone']): ?>
                                        <br><i class="fas fa-phone"></i> <?= htmlspecialchars($m['telephone']) ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($m['wilaya'] ?? '—') ?></td>
                                <td>
                                    <span class="badge badge-<?= ['en_attente'=>'warning','actif'=>'success','inactif'=>'danger'][$m['statut']] ?? 'secondary' ?>">
                                        <?= ucfirst(str_replace('_', ' ', $m['statut'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($m['document_path'])): ?>
                                        <a href="<?= BASE_URL . htmlspecialchars($m['document_path']) ?>" target="_blank" class="btn btn-sm btn-outline">
                                            <i class="fas fa-paperclip"></i> Voir le document
                                        </a>
                                    <?php else: ?>
                                        <span class="text-light">Aucune</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-right">
                                    <div style="display:flex; justify-content: flex-end; gap: 8px; flex-wrap: wrap;">
                                        <!-- Bouton pour voir les détails (Message de motivation, etc.) -->
                                        <button class="btn btn-sm btn-outline" onclick="showMemberDetails(<?= htmlspecialchars(json_encode([
                                            'nom' => $m['nom'] . ' ' . $m['prenom'],
                                            'email' => $m['email'],
                                            'telephone' => $m['telephone'] ?? 'Non fourni',
                                            'wilaya' => $m['wilaya'] ?? 'Non fournie',
                                            'statut' => $m['statut'],
                                            'message' => $m['message'] ?? 'Aucun message de motivation rédigé.',
                                            'message_decision' => $m['message_decision'] ?? '',
                                            'doc' => !empty($m['document_path']) ? BASE_URL . $m['document_path'] : ''
                                        ])) ?>)">
                                            <i class="fas fa-eye"></i> Détails
                                        </button>

                                        <?php if ($m['statut'] === 'en_attente'): ?>
                                            <button class="btn btn-sm btn-success" onclick="openDecisionModal('accepter', '<?= $m['id'] ?>', '<?= htmlspecialchars($m['nom'] . ' ' . $m['prenom']) ?>')">
                                                <i class="fas fa-check"></i> Accepter
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="openDecisionModal('rejeter', '<?= $m['id'] ?>', '<?= htmlspecialchars($m['nom'] . ' ' . $m['prenom']) ?>')">
                                                <i class="fas fa-times"></i> Refuser
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Détails -->
<div id="detailsModal" class="modal" style="display:none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); overflow: auto;">
    <div style="background: white; margin: 10% auto; padding: 24px; border-radius: 8px; max-width: 600px; width: 90%; box-shadow: 0 4px 20px rgba(0,0,0,0.15); position: relative;">
        <span onclick="closeModal('detailsModal')" style="position: absolute; right: 20px; top: 15px; font-size: 24px; cursor: pointer; color: var(--text-light);">&times;</span>
        <h2 id="modalTitle" style="margin-bottom: 20px; font-weight: 700; border-bottom: 1px solid var(--border); padding-bottom: 10px;">Détails de la candidature</h2>
        
        <div style="font-size: 0.95rem; line-height: 1.6;">
            <p><strong>Candidat :</strong> <span id="detNom"></span></p>
            <p><strong>Email :</strong> <span id="detEmail"></span></p>
            <p><strong>Téléphone :</strong> <span id="detTel"></span></p>
            <p><strong>Wilaya :</strong> <span id="detWilaya"></span></p>
            <p><strong>Statut :</strong> <span id="detStatut" class="badge"></span></p>
            
            <div style="margin-top: 15px; padding: 12px; background: var(--bg); border-radius: 6px; border-left: 4px solid var(--primary);">
                <strong><i class="fas fa-quote-left"></i> Message de motivation :</strong>
                <p id="detMessage" style="margin-top: 5px; font-style: italic;"></p>
            </div>

            <div id="detDecisionBlock" style="margin-top: 15px; padding: 12px; background: #fffde7; border-radius: 6px; border-left: 4px solid #fbc02d; display: none;">
                <strong><i class="fas fa-comment-dots"></i> Message de décision :</strong>
                <p id="detDecision" style="margin-top: 5px;"></p>
            </div>

            <div id="detDocBlock" style="margin-top: 15px;">
                <strong>Document joint :</strong><br>
                <a id="detDocLink" href="#" target="_blank" class="btn btn-sm btn-outline mt-5" style="display: inline-block;">
                    <i class="fas fa-external-link-alt"></i> Ouvrir la pièce jointe
                </a>
            </div>
        </div>
        <div style="text-align: right; margin-top: 24px;">
            <button class="btn btn-outline" onclick="closeModal('detailsModal')">Fermer</button>
        </div>
    </div>
</div>

<!-- Modal Décision -->
<div id="decisionModal" class="modal" style="display:none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); overflow: auto;">
    <div style="background: white; margin: 15% auto; padding: 24px; border-radius: 8px; max-width: 500px; width: 90%; box-shadow: 0 4px 20px rgba(0,0,0,0.15); position: relative;">
        <span onclick="closeModal('decisionModal')" style="position: absolute; right: 20px; top: 15px; font-size: 24px; cursor: pointer; color: var(--text-light);">&times;</span>
        <h2 id="decTitle" style="margin-bottom: 20px; font-weight: 700; border-bottom: 1px solid var(--border); padding-bottom: 10px;">Décision</h2>
        
        <form method="POST">
            <input type="hidden" name="ma_id" id="decMaId">
            <input type="hidden" name="action" id="decAction">
            
            <div class="form-group">
                <label id="decLabel" style="font-weight: 600; margin-bottom: 8px; display: block;"></label>
                <textarea name="message_decision" class="form-control" rows="4" required placeholder="Saisissez votre message ici... (Obligatoire)"></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" class="btn btn-outline" onclick="closeModal('decisionModal')">Annuler</button>
                <button type="submit" id="decSubmitBtn" class="btn"></button>
            </div>
        </form>
    </div>
</div>

<script>
function showMemberDetails(data) {
    document.getElementById('detNom').textContent = data.nom;
    document.getElementById('detEmail').textContent = data.email;
    document.getElementById('detTel').textContent = data.telephone;
    document.getElementById('detWilaya').textContent = data.wilaya;
    document.getElementById('detMessage').textContent = data.message;
    
    const badge = document.getElementById('detStatut');
    badge.textContent = data.statut.charAt(0).toUpperCase() + data.statut.slice(1);
    badge.className = 'badge badge-' + (data.statut === 'en_attente' ? 'warning' : (data.statut === 'actif' ? 'success' : 'danger'));

    if (data.message_decision) {
        document.getElementById('detDecision').textContent = data.message_decision;
        document.getElementById('detDecisionBlock').style.display = 'block';
    } else {
        document.getElementById('detDecisionBlock').style.display = 'none';
    }

    if (data.doc) {
        document.getElementById('detDocLink').href = data.doc;
        document.getElementById('detDocBlock').style.display = 'block';
    } else {
        document.getElementById('detDocBlock').style.display = 'none';
    }

    document.getElementById('detailsModal').style.display = 'block';
}

function openDecisionModal(action, id, nom) {
    document.getElementById('decMaId').value = id;
    document.getElementById('decAction').value = action;
    
    const label = document.getElementById('decLabel');
    const submitBtn = document.getElementById('decSubmitBtn');
    
    if (action === 'accepter') {
        document.getElementById('decTitle').innerHTML = '<i class="fas fa-check text-success"></i> Accepter la candidature';
        label.textContent = "Message d'acceptation pour " + nom + " :";
        submitBtn.className = "btn btn-success";
        submitBtn.innerHTML = '<i class="fas fa-check"></i> Confirmer l\'adhésion';
    } else {
        document.getElementById('decTitle').innerHTML = '<i class="fas fa-times text-danger"></i> Refuser la candidature';
        label.textContent = "Motif de refus pour " + nom + " :";
        submitBtn.className = "btn btn-danger";
        submitBtn.innerHTML = '<i class="fas fa-times"></i> Confirmer le refus';
    }

    document.getElementById('decisionModal').style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Close modals when clicking outside
window.onclick = function(event) {
    const dModal = document.getElementById('detailsModal');
    const decModal = document.getElementById('decisionModal');
    if (event.target == dModal) closeModal('detailsModal');
    if (event.target == decModal) closeModal('decisionModal');
}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
