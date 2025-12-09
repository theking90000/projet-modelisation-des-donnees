<?= print_header("Modification des informations personnelles", create_button("Se déconnecter", "/logout", image("arrow-right.svg")), "house.svg", "/") ?>

<div class="update_user">
    <a href="#" class="button" data-open="#modif_nom">Modification de votre nom</a>

    <div id="modif_nom" class="popup" data-popup="1" style="display: <?php if (isset($erreur_modif_nom)) {echo "block";} else {echo "none";}  ?>;">
        <h3>Modifier votre nom</h3>
        <form action="" method="post" class="center-col">
            <input type="hidden" name="action" value="name">

            <label for="modif_name">Nom</label>
            <input type="string" name="modif_name" id="modif_name" value="<?= @$name ?>" />

            <?php if (isset($erreur_modif_nom)) { ?>
                <span style="color: red;"><?= $erreur_modif_nom ?></span>
            <?php } ?>

            <input type="submit" value="Modifier" />
        </form>
    </div><br>

    <a href="#" class="button" data-open="#modif_prenom">Modification de votre prénom</a>

    <div id="modif_prenom" class="popup" data-popup="1" style="display: <?php if (isset($erreur_modif_prenom)) {echo "block";} else {echo "none";}  ?>;">
        <h3>Modifier votre prénom</h3>
        <form action="" method="post" class="center-col">
            <input type="hidden" name="action" value="first_name">

            <label for="modif_first_name">Prénom</label>
            <input type="string" name="modif_first_name" id="modif_first_name" value="<?= @$first_name ?>" />

            <?php if (isset($erreur_modif_prenom)) { ?>
                <span style="color: red;"><?= $erreur_modif_prenom ?></span>
            <?php } ?>

            <input type="submit" value="Modifier" />
        </form>
    </div><br>

    <a href="#" class="button" data-open="#modif_motdepasse">Modification de votre mot de passe</a>

    <div id="modif_motdepasse" class="popup" data-popup="1" style="display: <?php if (isset($erreur_modif_password)) {echo "block";} else {echo "none";}  ?>;">
        <h3>Modifier votre mot de passe</h3>
        <form action="" method="post" class="center-col">
            <input type="hidden" name="action" value="password">

            <label for="modif_password_old">Ancien mot de passe</label>
            <input type="password" name="modif_password_old" id="modif_password_old" />

            <label for="modif_password1">Nouveau mot de passe</label>
            <input type="password" name="modif_password1" id="modif_password1" />

            <label for="modif_password2">Confirmez le nouveau mot de passe</label>
            <input type="password" name="modif_password2" id="modif_password2" />

            <?php if (isset($erreur_modif_password)) { ?>
                <span style="color: red;"><?= $erreur_modif_password ?></span>
            <?php } ?>

            <input type="submit" value="Modifier" />
        </form>
    </div>
</div>