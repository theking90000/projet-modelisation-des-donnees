<?php

    $stmt = Database::instance()->execute("SELECT Portfolio.id, Portfolio.nom, Membre_Portfolio.niveau_acces  FROM Portfolio JOIN Membre_Portfolio ON Membre_Portfolio.id_portfolio = Portfolio.id JOIN Utilisateur ON Utilisateur.email = Membre_Portfolio.email WHERE Portfolio.id = ? AND Utilisateur.email = ?", [$portfolio_id, Auth::user()]);
    
    $portfolio = $stmt->fetch();

    $stmt = Database::instance()->execute("SELECT Transaction.* FROM Transaction WHERE Transaction.id_portfolio = ?", [$portfolio_id]);

    $transactions = $stmt->fetchAll();
?>

<div class="center center-col h-screen">
    Transactions du portfolio <?= $portfolio["nom"] ?>
    <br>
    
    <a href="#" data-open="#ajout-transaction">Ajouter une transaction</a>

    <div id="ajout-transaction" class="popup" data-popup="1" style="display: none" data-load="/portfolio/<?= $portfolio_id ?>/ajout-transaction"></div>

    <a href="/portfolio/<?= $portfolio_id ?>">Retour</a>
</div>