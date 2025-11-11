<?php
    $stmt = Database::instance()->execute("SELECT Portfolio.id, Portfolio.nom FROM Portfolio WHERE Portfolio.id = ?", [$portfolio_id]);
    
    $portfolio = $stmt->fetch();
?>

<div class="center center-col h-screen">
    Portfolio <?= $portfolio["nom"] ?>
    <br>

    <a href="/">Retour</a>
</div>