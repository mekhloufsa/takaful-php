<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['membre_association', 'president_siege']);
$db = getDB();
$uid = $_SESSION['user_id'];
$aid = $_GET['id'] ?? '';

// Vérifier que cette assignation appartient bien au membre connecté (ou au responsable de siège s'il s'est assigné lui-même) avec toutes les infos complémentaires
$stmt = $db->prepare("SELECT a.*, 
                             don.type as don_type, don.categorie as don_cat, don.montant as don_montant, don.description as don_desc, don.statut as don_statut,
                             m_don.nom as donateur_nom, m_don.prenom as donateur_prenom, m_don.telephone as donateur_tel, m_don.email as donateur_email,
                             dem.sujet as dem_sujet, dem.type_aide as dem_type, dem.description as dem_desc, dem.statut as dem_statut, dem.document_path as dem_doc,
                             m_dem.nom as demandeur_nom, m_dem.prenom as demandeur_prenom, m_dem.telephone as demandeur_tel, m_dem.email as demandeur_email
                      FROM assignation a 
                      LEFT JOIN membre_association ma ON a.membre_association_id=ma.id 
                      LEFT JOIN don ON a.don_id = don.id
                      LEFT JOIN membre m_don ON don.donateur_id = m_don.id
                      LEFT JOIN demande_aide dem ON a.demande_id = dem.id
                      LEFT JOIN membre m_dem ON dem.demandeur_id = m_dem.id
                      WHERE a.id=? AND (ma.membre_id=? OR a.president_assigne_id=?)");
$stmt->execute([$aid, $uid, $uid]);
$assign = $stmt->fetch();

if (!$assign) { flash('Mission introuvable ou accès refusé.', 'error'); header('Location: index.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'resoudre') {
        $date_rv = $_POST['date_rendezvous'] ?? '';
        $note = trim($_POST['note_rendezvous'] ?? '');
        
        if (empty($date_rv)) {
            flash('La date et l\'heure du rendez-vous sont obligatoires.', 'error');
        } else {
            $db->beginTransaction();
            try {
                // Maj de l'assignation
                $db->prepare("UPDATE assignation SET statut='terminee', date_rendezvous=?, note_rendezvous=? WHERE id=?")->execute([$date_rv, $note, $aid]);
                
                // Maj du parent (don ou demande)
                if ($assign['don_id']) {
                    $db->prepare("UPDATE don SET statut='collecte' WHERE id=?")->execute([$assign['don_id']]);
                } elseif ($assign['demande_id']) {
                    $db->prepare("UPDATE demande_aide SET statut='resolue' WHERE id=?")->execute([$assign['demande_id']]);
                }
                $db->commit();
                flash('Mission clôturée avec succès.', 'success');
                header("Location: index.php"); exit;
            } catch(Exception $e) {
                $db->rollBack();
                flash('Erreur de base de données.', 'error');
            }
        }
    } elseif ($action === 'annuler') {
        $message = trim($_POST['message_non_resolu'] ?? '');
        if (empty($message)) {
            flash('Le motif de non-résolution est obligatoire.', 'error');
        } else {
            $db->beginTransaction();
            try {
                // Maj de l'assignation
                $db->prepare("UPDATE assignation SET statut='annulee', message_non_resolu=? WHERE id=?")->execute([$message, $aid]);
                
                // Maj du parent (don ou demande)
                // En cas d'échec par le bénévole, on peut remettre la demande en attente pour le président, ou la refuser définitivement.
                // Ici on la repasse à "en_cours" (pour que le président puisse la réassigner) ou "refusee"
                if ($assign['don_id']) {
                    // On remet le don en attente pour qu'il soit réassigné par le responsable de siège
                    $db->prepare("UPDATE don SET statut='en_attente' WHERE id=?")->execute([$assign['don_id']]);
                } elseif ($assign['demande_id']) {
                    // On remet la demande à soumise pour qu'elle soit réassignée par le responsable de siège
                    $db->prepare("UPDATE demande_aide SET statut='soumise' WHERE id=?")->execute([$assign['demande_id']]);
                }
                $db->commit();
                flash('Mission marquée comme non résolue.', 'success');
                header("Location: index.php"); exit;
            } catch(Exception $e) {
                $db->rollBack();
                flash('Erreur de base de données.', 'error');
            }
        }
    }
}

$pageTitle = 'Traiter la Mission';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div class="breadcrumb"><a href="index.php">Mes Missions</a> / Traiter</div>
    <h1>Traiter la Mission</h1>
</div>
<div class="container py-50" style="max-width:800px;">
    <div class="card mb-20">
        <div class="card-header"><h3><i class="fas fa-info-circle"></i> Détails de la mission</h3></div>
        <div class="card-body">
            <p style="margin-bottom: 12px;"><strong>Date d'assignation :</strong> <span class="badge badge-secondary"><?= date('d/m/Y', strtotime($assign['date_assignation'])) ?></span></p>
            
            <?php if ($assign['don_id']): ?>
                <div style="border-top:1px solid var(--border); padding-top:15px; margin-top:15px;">
                    <h4 style="color:var(--primary); margin-bottom:10px;"><i class="fas fa-donate"></i> Informations sur le Don</h4>
                    <p><strong>Type de Don :</strong> <?= ucfirst($assign['don_type']) ?></p>
                    <?php if ($assign['don_cat']): ?>
                        <p><strong>Catégorie :</strong> <?= htmlspecialchars($assign['don_cat']) ?></p>
                    <?php endif; ?>
                    <?php if ($assign['don_montant'] > 0): ?>
                        <p><strong>Montant :</strong> <span style="font-weight:bold; color:#2ecc71;"><?= number_format($assign['don_montant'], 2, ',', ' ') ?> DZD</span></p>
                    <?php endif; ?>
                    <p style="margin-top:8px;"><strong>Description :</strong></p>
                    <div style="padding:10px; background:var(--bg); border-radius:6px; margin-bottom:15px; font-size:0.92rem; border-left:3px solid var(--primary);">
                        <?= nl2br(htmlspecialchars($assign['don_desc'])) ?>
                    </div>
                    
                    <h4 style="color:var(--primary); margin-top:20px; margin-bottom:10px;"><i class="fas fa-user"></i> Contact du Donateur</h4>
                    <p><strong>Nom complet :</strong> <?= htmlspecialchars($assign['donateur_nom'].' '.$assign['donateur_prenom']) ?></p>
                    <p><strong>Téléphone :</strong> <a href="tel:<?= htmlspecialchars($assign['donateur_tel']) ?>" style="font-weight:bold; color:var(--primary);"><i class="fas fa-phone"></i> <?= htmlspecialchars($assign['donateur_tel']) ?></a></p>
                    <?php if ($assign['donateur_email']): ?>
                        <p><strong>Email :</strong> <a href="mailto:<?= htmlspecialchars($assign['donateur_email']) ?>"><i class="fas fa-envelope"></i> <?= htmlspecialchars($assign['donateur_email']) ?></a></p>
                    <?php endif; ?>
                </div>
            <?php elseif ($assign['demande_id']): ?>
                <div style="border-top:1px solid var(--border); padding-top:15px; margin-top:15px;">
                    <h4 style="color:var(--primary); margin-bottom:10px;"><i class="fas fa-hand-holding-heart"></i> Informations sur la Demande</h4>
                    <p><strong>Sujet :</strong> <?= htmlspecialchars($assign['dem_sujet']) ?></p>
                    <p><strong>Type d'aide :</strong> <?= ucfirst($assign['dem_type']) ?></p>
                    <p style="margin-top:8px;"><strong>Description du besoin :</strong></p>
                    <div style="padding:10px; background:var(--bg); border-radius:6px; margin-bottom:15px; font-size:0.92rem; border-left:3px solid var(--primary);">
                        <?= nl2br(htmlspecialchars($assign['dem_desc'])) ?>
                    </div>
                    
                    <?php if (!empty($assign['dem_doc'])): ?>
                        <div style="padding: 12px; background: rgba(52, 152, 219, 0.08); border-radius: 8px; margin-bottom: 20px;">
                            <p style="font-weight:bold; margin-bottom:6px; font-size:0.9rem;"><i class="fas fa-paperclip"></i> Justificatif (Pièce jointe)</p>
                            <a href="<?= BASE_URL . htmlspecialchars($assign['dem_doc']) ?>" target="_blank" class="btn btn-sm btn-outline">
                                <i class="fas fa-eye"></i> Ouvrir le document joint
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <h4 style="color:var(--primary); margin-top:20px; margin-bottom:10px;"><i class="fas fa-user"></i> Contact du Demandeur</h4>
                    <p><strong>Nom complet :</strong> <?= htmlspecialchars($assign['demandeur_nom'].' '.$assign['demandeur_prenom']) ?></p>
                    <p><strong>Téléphone :</strong> <a href="tel:<?= htmlspecialchars($assign['demandeur_tel']) ?>" style="font-weight:bold; color:var(--primary);"><i class="fas fa-phone"></i> <?= htmlspecialchars($assign['demandeur_tel']) ?></a></p>
                    <?php if ($assign['demandeur_email']): ?>
                        <p><strong>Email :</strong> <a href="mailto:<?= htmlspecialchars($assign['demandeur_email']) ?>"><i class="fas fa-envelope"></i> <?= htmlspecialchars($assign['demandeur_email']) ?></a></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (in_array($assign['statut'], ['assignee', 'en_cours'])): ?>
    <div class="card">
        <div class="card-header"><h3>Clôturer la mission</h3></div>
        <div class="card-body">
            <form method="POST">
                <div class="form-group">
                    <label>Action</label>
                    <select name="action" id="actionSelect" class="form-control" onchange="toggleAction()" required>
                        <option value="">-- Choisir --</option>
                        <option value="resoudre">Mission réussie (Donner un rendez-vous)</option>
                        <option value="annuler">Échec de la mission (Non résolue)</option>
                    </select>
                </div>
                
                <div id="resoudreGroup" style="display:none;">
                    <div class="form-group">
                        <label>Date et Heure du rendez-vous</label>
                        <input type="datetime-local" name="date_rendezvous" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Note pour le demandeur/donateur (Lieu exact, instructions...)</label>
                        <textarea name="note_rendezvous" class="form-control" rows="3"></textarea>
                    </div>
                </div>

                <div id="annulerGroup" style="display:none;">
                    <div class="form-group">
                        <label>Motif de l'échec (Obligatoire)</label>
                        <textarea name="message_non_resolu" class="form-control" rows="3" placeholder="Ex: La personne ne répond pas, adresse introuvable..."></textarea>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-save"></i> Enregistrer</button>
            </form>
        </div>
    </div>
    <script>
    function toggleAction() {
        const val = document.getElementById('actionSelect').value;
        document.getElementById('resoudreGroup').style.display = val === 'resoudre' ? 'block' : 'none';
        document.getElementById('annulerGroup').style.display = val === 'annuler' ? 'block' : 'none';
    }
    </script>
    <?php else: ?>
    <div class="card">
        <div class="card-header"><h3>Résultat de la mission</h3></div>
        <div class="card-body">
            <p><strong>Statut final :</strong> <?= ucfirst($assign['statut']) ?></p>
            <?php if ($assign['statut'] === 'terminee'): ?>
                <p><strong>Date de Rendez-vous :</strong> <?= date('d/m/Y H:i', strtotime($assign['date_rendezvous'])) ?></p>
                <p><strong>Note :</strong> <?= nl2br(htmlspecialchars($assign['note_rendezvous'])) ?></p>
            <?php elseif ($assign['statut'] === 'annulee'): ?>
                <p><strong>Motif de l'échec :</strong> <?= nl2br(htmlspecialchars($assign['message_non_resolu'])) ?></p>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
