<?php
    $t = Database::instance()->execute("
        SELECT 
            t.date,
            t.heure,
            t.isin,

            t.quantite,
            t.type,
            ROUND(t.valeur_devise_portfolio, 2) as valeur_devise_portfolio, 
            t.valeur_devise_instrument,
            ROUND(t.frais, 2) as frais,
            ROUND(t.taxes, 2) as taxes,

            CONCAT(u.nom,' ', u.prenom) as utilsateur,

            ins.nom,
            ins.symbole,
            di.code as code_devise,
            di.symbole as devise,
            p.nom as nom_portfolio,
            dp.symbole as devise_portfolio,
            ins.type as type_instrument
        FROM Transaction t
            JOIN Instrument_Financier ins ON ins.isin = t.isin
            LEFT JOIN Devise di ON di.code = ins.code_devise
            JOIN Portfolio p ON p.id = ?
            JOIN Devise dp ON dp.code = p.code_devise
            LEFT JOIN Utilisateur u ON t.email_utilisateur = u.email
        WHERE 
            t.id = ?;
    ", [$portfolio_id, $transaction_id])->fetch();
    
    if(!$t) {
        render_page(__DIR__."/404.php", ["title"=>"Finance App - Erreur 404"]);
        die();
    }

    $formatter = new IntlDateFormatter(
                        'fr_FR', 
                        IntlDateFormatter::FULL,
                        IntlDateFormatter::SHORT);
    // EEEE = Nom du Jour, MMMM = Nom du mois
    $formatter->setPattern("EEEE d MMMM yyyy 'à' HH'h'mm");
    $formatter->setTimeZone("Europe/Brussels");

    $dt = new DateTime($t["date"]. " ". $t["heure"], new DateTimeZone('Europe/Brussels'));
    $date = ucfirst($formatter->format($dt));
?>

<?= print_portfolio_header($portfolio_id, $t["nom_portfolio"], "/portfolio/$portfolio_id") ?>

<div class="portfolio-main">
    <div class="section">
        <div class="m-col header-search">
            <span>
                <div style="display:flex; align-items:center;">
                    <div>
                    <h3>Transaction du <?= $date ?></h3> 
                    
                    <em>Concernant l'instrument
                        <a href="/portfolio/<?= $portfolio_id ?>/instrument/<?= $t["isin"] ?>">
                            <?= $t["nom"] ?>
                        </a>
                    </em></div>
                </div>
                <sub><?= $ins["isin"] ?></sub>
            </span>

        <a href="#" class="button" data-open="#edit-transaction">Editer</a>
   
        </div>

         <div id="edit-transaction" data-reload-on-callback="edit-transaction" class="popup" data-popup="1" style="display: none" data-load="/portfolio/<?=  $portfolio_id ?>/ajout-transaction?callback_id=edit-transaction&form=1&nopopup=1&update=<?= $transaction_id ?>"></div>

        <br>

        <div class="portfolio-main">
             <div class="section center">
                <div class="card">
                    <div><h3>
                        <?php if($t["type"] === "vente") {
                            echo "Vente";
                        } else {
                            echo "Achat";
                        }?>
                    </div>
                    <div><?= $date ?></div>

                    <div>Quantite : <?= $t["quantite"] ?></div>
                    
                    <div>Frais : <?= $t["frais"] ?> <?= $t["devise_portfolio"] ?></div>
                    <div>Taxes : <?= $t["taxes"] ?> <?= $t["devise_portfolio"] ?></div>
                    <div>Valeur : <?= $t["valeur_devise_portfolio"] ?> <?= $t["devise_portfolio"] ?></div>
                    <div><?= $t["isin"] ?></div>
                    <div>Utilisateur : <?= $t["utilsateur"] ?></div>
                    
                    <a href="/portfolio/<?= $portfolio_id ?>/instrument/<?= $t["isin"] ?>">Voir l'instrument</a>
                </div>
             </div> 

             Affichage PnL de la transaction si "achat"
        </div>
    </div>
</div>