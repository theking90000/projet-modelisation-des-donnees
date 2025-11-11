<?php
    $stmt = Database::instance()->execute("SELECT Portfolio.id, Portfolio.nom FROM Portfolio WHERE Portfolio.id = ?", [$portfolio_id]);
    
    $portfolio = $stmt->fetch();

    // TODO: gérer la recherche via un paramètre query.
    $stmt = Database::instance()->execute("SELECT Utilisateur.email, Membre_Portfolio.niveau_acces FROM Membre_Portfolio JOIN Utilisateur ON Utilisateur.email = Membre_Portfolio.email WHERE Membre_Portfolio.id_portfolio = ?", [$portfolio_id]);

    $membres = $stmt->fetchAll();
?>

<div class="center center-col h-screen">
    Membres du portfolio <?= $portfolio["nom"] ?>
    <br>

    <table>
        <tbody>
        <?php foreach ($membres as $membre) {?>
            <tr>
                <td><?= $membre['email'] ?></td>
                <td>Niveau acces: <?= $membre['niveau_acces'] ?></td>
            </tr>
        <?php } ?>
        </tbody>
    </table>

    <a href="#" data-open="#ajout-membre">Ajouter un membre</a>

    <a href="#" data-open="#transfer">Transférer propriété</a>

    <div id="ajout-membre" style="display: <?php if (isset($erreur_ajout_membre)) { echo "block"; } else { echo "none"; }  ?>;">
        <h3>Ajouter un membre</h3>
        <form action="" method="post" class="center-col">
            <input name="ajout_membre_email" id="ajout_membre_email" type="email" placeholder="Adresse Email" value="<?= $ajout_membre_email ?>" />

            <?php if(isset($erreur_ajout_membre_email)) { ?>
                <span style="color: red;"><?= $erreur_ajout_membre_email ?></span>
            <?php } ?>

            SELECT - POUR TYPE acces
            
        <input type="submit" value="Ajouter" />
        </form>
    </div>

    <div id="transfer" style="display: <?php if (isset($erreur_transfer_email)) { echo "block"; } else { echo "none"; }  ?>;">
        <h3>Transférer la propriété</h3>
        <form action="" method="post" class="center-col">
            <input name="transfer_email" id="transfer_email" type="email" placeholder="Adresse Email" value="<?= $transfer_email ?>" />

            <?php if(isset($erreur_transfer_email)) { ?>
                <span style="color: red;"><?= $erreur_transfer_email ?></span>
            <?php } ?>

            <label for="transfer_garder_acces">Garder accès au portfolio</label>
            <input name="transfer_garder_acces" id="transfer_garder_acces" type="checkbox" />

            SELECT - POUR TYPE acces
            
            <input type="submit" value="Transférer la propriété" />
        </form>
    </div>

    <a href="/portfolio/<?= $portfolio_id ?>/parametres">Retour</a>
</div>

