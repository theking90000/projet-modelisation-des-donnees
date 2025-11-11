<div class="center center-col h-screen">
    Accueil - sélection du portfolio
    <br>
    <?= Auth::user() ?><br>

    <?php 
    // Rapport: Requete SQL de listing de portfolio.
    $stmt = Database::instance()->execute("SELECT Portfolio.id, Portfolio.nom FROM Portfolio JOIN Membre_Portfolio ON Portfolio.id = Membre_Portfolio.id_portfolio JOIN Utilisateur ON Utilisateur.email = Membre_Portfolio.email WHERE Utilisateur.email = ?", [Auth::user()]);

    while ($row = $stmt->fetch()) { ?>
        <a href="/portfolio/<?= $row["id"] ?>">Portfolio : <?= htmlspecialchars($row["nom"]) ?></a>
    <?php } ?><br>

    <a href="/logout">Se déconnecter</a>
</div>