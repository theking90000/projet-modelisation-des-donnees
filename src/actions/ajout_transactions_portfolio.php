<?php
    if($_REQUEST["REQUEST_METHOD"] == "POST") {
        
        die();
    }
?>

<h3>Ajouter une transaction</h3>

<form action="" method="post" class="center-col">
    <input name="nom" id="nom" placeholder="Nouveau nom" value="<?= $nom ?>" />

    <?php if(isset($erreur_nom)) { ?>
        <span style="color: red;"><?= $erreur_nom ?></span>
    <?php } ?>
    
    <input type="submit" value="Enregistrer" />
</form>