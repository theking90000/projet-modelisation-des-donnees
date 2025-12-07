<?php
    require_once __DIR__ . '/../template/utils.php';

    // 1. Fetch Portfolio Info
    $stmt = Database::instance()->execute("SELECT * FROM Portfolio WHERE id = ?", [$portfolio_id]);
    $portfolio = $stmt->fetch();

    if(!$portfolio) { render_page("404.php"); die(); }

    // 2. Fetch Members (excluding the current user/owner to prevent self-deletion)
    // We explicitly check for 'owner' status to list potential candidates for ownership transfer
    $members = Database::instance()->execute(
        "SELECT u.email, u.nom, u.prenom, mp.niveau_acces 
         FROM Membre_Portfolio mp 
         JOIN Utilisateur u ON mp.email = u.email 
         WHERE mp.id_portfolio = ? 
         ORDER BY mp.niveau_acces DESC, u.nom ASC", 
        [$portfolio_id]
    )->fetchAll();

    // Separate current user from the list for display logic
    $currentUser = Auth::user();
?>

<?= print_portfolio_header_back($portfolio_id, "Paramètres : " . htmlspecialchars($portfolio["nom"])) ?>

<div class="portfolio-main">

    <div class="section">
        <div class="card" style="display: block;">
            <h3>Général</h3>
            <form action="/portfolio/<?= $portfolio_id ?>/parametres" method="post" class="center-col" style="margin-top: 20px;">
                <label for="nom">Nom du portfolio</label>
                <div style="display: flex; gap: 10px;">
                    <input type="text" name="nom" id="nom" value="<?= htmlspecialchars($portfolio['nom']) ?>" required style="margin-bottom: 0;">
                    <input type="submit" value="Renommer" style="margin-top: 0; width: auto;">
                </div>
                <?php if(isset($erreur_nom)) { echo "<span style='color:red'>$erreur_nom</span>"; } ?>
            </form>
        </div>
    </div>

    <div class="section">
        <div class="row header-search">
            <h3>Membres du portfolio</h3>
            
            <div style="display: flex; gap: 10px;">
                <a href="#" class="button secondary" data-open="#transfer-owner">
                     Transférer la propriété
                </a>
                <a href="#" class="button" data-open="#add-member">
                    <span>+</span> Ajouter un membre
                </a>
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
                            <?php if($m['email'] !== $currentUser): // Don't show actions for self ?>
                                <div style="display: flex; gap: 5px; justify-content: flex-end;">
                                    <a href="#" 
                                       onclick="openEditPopup('<?= $m['email'] ?>', '<?= $m['niveau_acces'] ?>'); return false;"
                                       style="background: #e0e7ff; color: #635bff;">
                                       Modifier
                                    </a>
                                    <form action="/portfolio/<?= $portfolio_id ?>/membres" method="post" onsubmit="return confirm('Voulez-vous vraiment retirer ce membre ?');" style="display:inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="email" value="<?= $m['email'] ?>">
                                        <button type="submit" style="background: #fee2e2; color: #dc2626; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: 600;">
                                            Retirer
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
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

            <input type="submit" value="Ajouter">
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
        <p style="color: var(--error-color); font-size: 0.9em; margin-bottom: 20px;">
            Attention : Cette action est irréversible. Vous perdrez le contrôle administratif de ce portfolio.
        </p>
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
    // Helper to open the edit popup with the correct data
    function openEditPopup(email, level) {
        document.getElementById('edit-email-display').value = email;
        document.getElementById('edit-email-input').value = email;
        document.getElementById('edit-niveau').value = level;
        
        // Use your existing logic to open popup
        const popup = document.getElementById('edit-member');
        popup.style.display = 'block';
    }
</script>