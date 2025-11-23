<?php
    $stmt = Database::instance()->execute("SELECT Portfolio.id, Portfolio.nom, Membre_Portfolio.niveau_acces  FROM Portfolio JOIN Membre_Portfolio ON Membre_Portfolio.id_portfolio = Portfolio.id JOIN Utilisateur ON Utilisateur.email = Membre_Portfolio.email WHERE Portfolio.id = ? AND Utilisateur.email = ?", [$portfolio_id, Auth::user()]);
    
    $portfolio = $stmt->fetch();

    $titres = Database::instance()->execute("
    ")
?>

<?= print_portfolio_header($portfolio_id, $portfolio["nom"]) ?>

<div class="portfolio-main">
    <div class="graph">
        Ici, Graphique
    </div>

    <div class="section">
        <div class="row">
            <h3>Instruments Financier ayant le plus de variations</h3>

            <?= create_button("Contenu du porfolio", "/portfolio/$portfolio_id/contenu" ,image("arrow-right.svg")) ?>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Instrument financier</th>
                    <th>Valeur</th>
                    <th>Prix moyen (ACHAT)</th>
                    <th>Prix actuel</th>
                    <th><strong>% Change day</strong></th>
                    <th><strong>Profit</strong></th>
                    <th></th>
                </tr>
            </thead>
        </table>
    </div>
</div>



<div class="center center-col h-screen">
    <strong>Portfolio <?= $portfolio["nom"] ?></strong>
    <br>

    <a href="/portfolio/<?= $portfolio_id ?>/transactions">Voir transactions</a>

    <div class="">
       
    </div>

    <br>
    <span>Gérer les données</span>
    <a href="/portfolio/<?= $portfolio_id ?>/instruments">Voir les instruments financiers</a>
    <a href="/portfolio/<?= $portfolio_id ?>/entreprises">Voir les entreprises</a>
    <a href="/portfolio/<?= $portfolio_id ?>/bourses">Voir les bourses</a>
    <a href="/portfolio/<?= $portfolio_id ?>/pays">Voir les pays</a>
     <a href="/portfolio/<?= $portfolio_id ?>/devises">Voir les devises</a>
    <br>

    <?php if($portfolio['niveau_acces'] >= 3) { ?>
    <a href="/portfolio/<?= $portfolio_id ?>/parametres">Paramètres</a>
    <?php } ?>
    <a href="/">Retour</a>
</div>