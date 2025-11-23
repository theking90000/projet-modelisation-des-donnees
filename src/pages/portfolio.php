<?php
    /* Récuperer les informations du portfolio,
       de l'utilisateur (niveau accès),
       devise du portfolio */
    $stmt = Database::instance()->execute("
    SELECT p.id, p.nom, mp.niveau_acces,
           d.symbole AS devise
    FROM Portfolio p
    JOIN Membre_Portfolio mp ON mp.id_portfolio = p.id 
    JOIN Utilisateur u ON u.email = mp.email
    JOIN Devise d ON d.code = p.code_devise
    WHERE p.id = ? AND u.email = ?", [$portfolio_id, Auth::user()]);
    
    $portfolio = $stmt->fetch();

    $devise = $portfolio['devise'];
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

        <div data-lazy="/portfolio/<?= $portfolio_id ?>/contenu?table=1&page=0&perPage=3&sort=p_change&hideSort=1"></div>
        
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