<?php
    $stmt = Database::instance()->execute("SELECT Portfolio.id, Portfolio.nom FROM Portfolio WHERE Portfolio.id = ?", [$portfolio_id]);
    
    $portfolio = $stmt->fetch();
?>

<div class="center center-col h-screen">
    Paramètres du portfolio <?= $portfolio["nom"] ?>
    <br>

    <a href="#" data-open="#modifier-nom">Modifier le nom</a>

    <div id="modifier-nom" style="display: <?php if (isset($erreur_nom)) { echo "block"; } else { echo "none"; }  ?>;">
        <h3>Modifier le nom du portfolio</h3>
        <form action="" method="post" class="center-col">
            <input name="nom" id="nom" placeholder="Nouveau nom" value="<?= $nom ?>" />

            <?php if(isset($erreur_nom)) { ?>
                <span style="color: red;"><?= $erreur_nom ?></span>
            <?php } ?>
            
            <input type="submit" value="Enregistrer" />
        </form>
    </div>

    <a href="/portfolio/<?= $portfolio_id ?>/membres">Gérer les membres</a>
    <a href="/portfolio/<?= $portfolio_id ?>">Retour</a>
</div>

