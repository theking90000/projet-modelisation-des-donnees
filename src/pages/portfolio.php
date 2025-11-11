<?php
    $stmt = Database::instance()->execute("SELECT Portfolio.id, Portfolio.nom, Membre_Portfolio.niveau_acces  FROM Portfolio JOIN Membre_Portfolio ON Membre_Portfolio.id_portfolio = Portfolio.id JOIN Utilisateur ON Utilisateur.email = Membre_Portfolio.email WHERE Portfolio.id = ? AND Utilisateur.email = ?", [$portfolio_id, Auth::user()]);
    
    $portfolio = $stmt->fetch();
?>

<div class="center center-col h-screen">
    Portfolio <?= $portfolio["nom"] ?>
    <br>

    <?php if($portfolio['niveau_acces'] >= 3) { ?>
    <a href="/portfolio/<?= $portfolio_id ?>/parametres">Paramètres</a>
    <?php } ?>
    <a href="/">Retour</a>
</div>