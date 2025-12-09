<div class="login">

    <h1>Entrez vos informations de connexion</h1>

    <form action="" method="post" class="center-col">
        <label for="name">Nom</label>
        <input type="string" name="name" id="name" value="<?= @$name ?>" />

        <label for="first_name">Prénom</label>
        <input type="string" name="first_name" id="first_name" value="<?= @$first_name ?>" />

        <label for="email">Email</label>
        <input type="email" name="email" id="email" value="<?= @$email ?>" />

        <label for="password">Mot de passe</label>
        <input type="password" name="password" id="password" />

        <label for="password2">Confirmez votre mot de passe</label>
        <input type="password" name="password2" id="password2" />

        <?php if (isset($error)) { ?>
            <span style="color: red;"><?= $error ?></span>
        <?php } ?>

        <input type="submit" value="S'inscrire" />
    </form>

<a href="/login"> Revenir à la connexion </a>

</div>