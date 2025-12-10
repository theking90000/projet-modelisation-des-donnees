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
    <div class="graph" style="width:100%; padding: 8px; display: flex; flex-direction: column; align-items: center; justify-content: center">
        <div class="graph-container" style="position: relative; height:40vh; width:100%; margin:auto;">
            <canvas id="graph" data="<?= $portfolio_id ?>" data-type="portfolio" currency="<?= $devise ?>" label="<?= $portfolio["nom"] ?>"></canvas>
        </div>
        <div style="display: flex; flex-direction: row; gap: 8px;">
            <button class="button" id="week">1 Semaine</button>
            <button class="button" id="month">1 Mois</button>
            <button class="button" id="year">1 Année</button>
        </div>
    </div>

    <div class="section">
        <div class="row">
            <h3>Instruments Financier ayant le plus de variations</h3>

            <?= create_button("Contenu du porfolio", "/portfolio/$portfolio_id/contenu" ,image("arrow-right.svg")) ?>
        </div>

        <div data-lazy="/portfolio/<?= $portfolio_id ?>/contenu?table=1&page=0&perPage=3&sort=p_change&hideSort=1&noPagination=1"></div>
        
    </div>

    <div class="section">
        <div class="row">
            <h3>Dernières transactions</h3>

            <?= create_button("Transactions", "/portfolio/$portfolio_id/transactions" ,image("arrow-right.svg")) ?>
        </div>

        <div data-lazy="/portfolio/<?= $portfolio_id ?>/transactions?table=1&page=0&perPage=3&sort=date&hideSort=1&noPagination=1&noLayout=1"></div>
    </div>
</div>

