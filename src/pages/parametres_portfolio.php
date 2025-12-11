<?php
    require_once __DIR__ . '/../template/utils.php';

    // 1. Fetch Portfolio Info
    $stmt = Database::instance()->execute("SELECT * FROM Portfolio WHERE id = ?", [$portfolio_id]);
    $portfolio = $stmt->fetch();

    if(!$portfolio) { render_page("404.php"); die(); }

    // 2. Fetch Members
    $members = Database::instance()->execute(
        "SELECT u.email, u.nom, u.prenom, mp.niveau_acces 
         FROM Membre_Portfolio mp 
         JOIN Utilisateur u ON mp.email = u.email 
         WHERE mp.id_portfolio = ? 
         ORDER BY mp.niveau_acces DESC, u.nom ASC", 
        [$portfolio_id]
    )->fetchAll();

    $currentUser = Auth::user();
?>

<?= print_portfolio_header_back($portfolio_id, "Paramètres : " . htmlspecialchars($portfolio["nom"])) ?>

<div class="portfolio-main">

    <?php if(isset($_GET['error']) && $_GET['error'] === 'not_empty'): ?>
        <div class="section">
            <div style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 15px; border-radius: 8px; text-align: center; font-weight: 600;">
                ❌ Impossible de supprimer le portfolio : sa valeur totale n'est pas de 0.<br>
                <span style="font-weight: 400; font-size: 0.9em;">Veuillez vendre tous vos actifs avant de le supprimer.</span>
            </div>
        </div>
    <?php endif; ?>

    <div class="section">
        <div class="card" style="display: block;">
            <h3>Général</h3>
            
            <form action="/portfolio/<?= $portfolio_id ?>/parametres" method="post" style="display: flex; gap: 20px; align-items: flex-end; flex-wrap: wrap; margin-top: 15px;">
                <input type="hidden" name="action" value="update">

                <div style="flex: 1; min-width: 250px;">
                    <label for="nom" style="display: block; margin-bottom: 5px; font-size: 0.85em; color: #64748b; font-weight: 600;">NOM DU PORTFOLIO</label>
                    <input type="text" name="nom" id="nom" value="<?= htmlspecialchars($portfolio['nom']) ?>" required style="margin-bottom: 0;">
                </div>

                <div style="flex: 2; min-width: 300px;">
                    <label for="description" style="display: block; margin-bottom: 5px; font-size: 0.85em; color: #64748b; font-weight: 600;">DESCRIPTION</label>
                    <textarea name="description" id="description" rows="1" placeholder="Optionnel" style="width: 100%; height: 46px; padding: 10px; border: 1px solid #d9dce1; border-radius: 8px; font-family: inherit; resize: none; vertical-align: bottom;"><?= htmlspecialchars($portfolio['description'] ?? '') ?></textarea>
                </div>

                <div>
                    <input type="submit" value="Enregistrer" style="margin: 0; height: 46px; padding: 0 24px;">
                </div>
            </form>

            <?php if(isset($_GET['success'])): ?>
                <div style="color: var(--success-color); font-weight: 600; margin-top: 10px; font-size: 0.9em;">
                    ✅ Modifications enregistrées.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="section">
        <div class="row header-search">
            <h3>Membres du portfolio</h3>
            <div style="display: flex; gap: 10px;">
                <a href="#" class="button secondary" data-open="#transfer-owner">Transférer la propriété</a>
                <a href="#" class="button" data-open="#add-member"><span>+</span> Ajouter un membre</a>
            </div>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Utilisateur</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($members as $m): ?>
                    <tr>
                        <td><?= htmlspecialchars($m['nom'] . ' ' . $m['prenom']) ?></td>
                        <td><?= htmlspecialchars($m['email']) ?></td>
                        <td>
                            <?php 
                                if($m['niveau_acces'] == 3) echo '<span style="color:var(--primary-color); font-weight:bold;">Propriétaire</span>';
                                elseif($m['niveau_acces'] == 2) echo 'Éditeur';
                                else echo 'Lecteur';
                            ?>
                        </td>
                        <td>
                            <?php if($m['email'] !== $currentUser): ?>
                                <div style="display: flex; gap: 5px; justify-content: flex-end;">
                                    <a href="#" onclick="openEditPopup('<?= $m['email'] ?>', '<?= $m['niveau_acces'] ?>'); return false;" style="background: #e0e7ff; color: #635bff;">Modifier</a>
                                    <form action="/portfolio/<?= $portfolio_id ?>/membres" method="post" onsubmit="return confirm('Voulez-vous vraiment retirer ce membre ?');" style="display:inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="email" value="<?= $m['email'] ?>">
                                        <button type="submit" style="background: #fee2e2; color: #dc2626; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: 600;">Retirer</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="section" style="margin-top: 40px;">
        <div style="border: 1px solid #fecaca; border-radius: 12px; overflow: hidden;">
            <!-- <div style="background: #fef2f2; padding: 20px; border-bottom: 1px solid #fecaca;">
                <h3 style="color: #991b1b; font-size: 1.1em; margin: 0;">Zone de danger</h3>
            </div> -->
            <div style="background: white; padding: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
                <div>
                    <strong style="display: block; margin-bottom: 5px;">Supprimer ce portfolio</strong>
                    <span style="color: #64748b; font-size: 0.9em;">Cette action est irréversible. Le portfolio doit être vide (valeur totale à 0) pour être supprimé.</span>
                </div>
                <button class="button danger" data-open="#delete-confirm">Supprimer le portfolio</button>
            </div>
        </div>
    </div>

    <div id="delete-confirm" class="popup" data-popup="1" style="display: none;">
        <h3 style="color: var(--error-color);">Supprimer le portfolio ?</h3>
        <p>Êtes-vous sûr de vouloir supprimer définitivement <strong><?= htmlspecialchars($portfolio['nom']) ?></strong> ?</p>
        <p style="color: #64748b; font-size: 0.9em;">Toutes les transactions et les historiques associés seront effacés.</p>
        
        <form action="/portfolio/<?= $portfolio_id ?>/parametres" method="post" class="center-col" style="margin-top: 20px;">
            <input type="hidden" name="action" value="delete_portfolio">
            
            <div style="display: flex; gap: 10px; width: 100%;">
                <a href="#" class="button secondary" onclick="document.getElementById('delete-confirm').style.display='none'; return false;" style="flex: 1; text-align: center;">Annuler</a>
                <input type="submit" value="Oui, supprimer" class="button danger" style="flex: 1; margin: 0;">
            </div>
        </form>
    </div>

    <div id="add-member" class="popup" data-popup="1" style="display: none;">
        <h3>Ajouter un membre</h3>
        <form action="/portfolio/<?= $portfolio_id ?>/membres" method="post" class="center-col">
            <input type="hidden" name="action" value="add">
            <label>Email de l'utilisateur</label>
            <input type="email" name="email" required placeholder="exemple@email.com">
            <label>Niveau d'accès</label>
            <select name="niveau">
                <option value="1">Lecture Seule</option>
                <option value="2">Édition (Lecture + Écriture)</option>
            </select>
            <input type="submit" value="Ajouter le membre">
        </form>
    </div>

    <div id="edit-member" class="popup" data-popup="1" style="display: none;">
        <h3>Modifier les accès</h3>
        <form action="/portfolio/<?= $portfolio_id ?>/membres" method="post" class="center-col">
            <input type="hidden" name="action" value="update">
            <label>Utilisateur</label>
            <input type="text" id="edit-email-display" readonly style="background: #f4f6f8; color: #64748b;">
            <input type="hidden" name="email" id="edit-email-input">
            <label>Nouveau Niveau</label>
            <select name="niveau" id="edit-niveau">
                <option value="1">Lecture Seule</option>
                <option value="2">Édition</option>
            </select>
            <input type="submit" value="Enregistrer">
        </form>
    </div>

    <div id="transfer-owner" class="popup" data-popup="1" style="display: none;">
        <h3>Transférer la propriété</h3>
        <div style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 20px;">
            <strong>Attention :</strong> Cette action est irréversible.
        </div>
        <form action="/portfolio/<?= $portfolio_id ?>/membres" method="post" class="center-col">
            <input type="hidden" name="action" value="transfer">
            <label>Nouveau Propriétaire</label>
            <select name="new_owner_email" required>
                <option value="" disabled selected>Choisir un membre...</option>
                <?php foreach ($members as $m): ?>
                    <?php if($m['email'] !== $currentUser): ?>
                        <option value="<?= $m['email'] ?>"><?= $m['nom'] . ' ' . $m['prenom'] ?> (<?= $m['email'] ?>)</option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
            <label>Votre nouveau statut</label>
            <select name="my_new_role">
                <option value="2">Devenir Éditeur</option>
                <option value="1">Devenir Lecteur</option>
                <option value="0">Quitter le portfolio</option>
            </select>
            <input type="submit" value="Confirmer le transfert" class="button danger">
        </form>
    </div>

</div>

<script>
    function openEditPopup(email, level) {
        document.getElementById('edit-email-display').value = email;
        document.getElementById('edit-email-input').value = email;
        document.getElementById('edit-niveau').value = level;
        const popup = document.getElementById('edit-member');
        popup.style.display = 'block';
    }
</script>