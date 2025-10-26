<h1>Veuillez vous connectez</h1>

<form action="" method="post" class="center-col">
    <label for="email">Email</label>
    <input type="email" name="email" id="email" />
    <label for="password">Mot de passe</label>
    <input type="password" name="password" id="password" />

    <?php if(isset($loginError)) { ?>
        <span style="color: red;"><?php echo $loginError ?></span>
    <?php } ?>

    <input type="submit"/>
</form>