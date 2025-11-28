<?php
    $en = null;

    if(strlen($entreprise_id) >= 3) {
        $code_pays = substr($entreprise_id, 0, 2);
        $numero = substr($entreprise_id, 2);

        $en  = Database::instance()->execute("
            SELECT
                p.nom as nom_portfolio,
                e.*,
                ep.nom AS nom_pays
            FROM 
                Entreprise e
                JOIN Portfolio p ON p.id = ?
                JOIN Pays ep ON e.code_pays = ep.code
            WHERE
                e.code_pays = ?
                AND e.numero = ?
        ", [$portfolio_id, $code_pays, $numero])->fetch();
    }

    if(!$en) {
        render_page(__DIR__."/404.php", ["title"=>"Finance App - Erreur 404"]);
        die();
    }

?>

<?= print_portfolio_header($portfolio_id, $ins["nom_portfolio"], "/portfolio/$portfolio_id") ?>

<div class="portfolio-main">
    <div class="section">
        <div class="m-col header-search">
            <span>
                <div style="display:flex; align-items:center;">
                    <h3><?= $ins["symbole"]?></h3> 
                    <div style="margin: 0 8px;"> <?= $ins["nom"] ?> </div>
                    <?php if($ins["type"] === "action") {
                        echo '<em>(<a href="/portfolio/';
                        echo $portfolio_id. "/entreprise/".$ins["id_entreprise"];
                        echo '">';
                        echo $ins["nom_entreprise"];
                        echo "</a> - cotée à la bourse <em><a href=\"/portfolio/";
                        echo $portfolio_id . "/bourse/".$ins["id_bourse"] ;
                        echo "\">";
                        echo $ins["id_bourse"];
                        echo "</a>)</em>";
                    } ?>
                </div>
                <sub><?= $ins["isin"] ?></sub>
            </span>

        <a href="#" class="button" data-open="#edit-entreprise">Editer</a>
   
        </div>

         <div id="edit-entreprise" data-reload-on-callback="edit-entreprise" class="popup" data-popup="1" style="display: none" data-load="/portfolio/<?=  $portfolio_id ?>/entreprises?callback_id=edit-entreprise&form=1&nopopup=1&update=<?= $entreprise_id ?>"></div>

        <br>

        <div class="portfolio-main">
            

            

             <div class="section">
                <div class="row header-search">
                    <h3>Instruments financiers liés à l'entreprise</h3>
                    <label for="date-filer">Après le:</label>
                    <input placeholder="Rechercher" id="contenu-filter" type="search" name="recherche" value="" oninput="search_ajax_debounce(this, '#contenu-portfolio', 0, '/portfolio/<?= $portfolio_id ?>/contenu?table=1&entreprise=<?= $entreprise_id ?>');" />
                </div>

                <div id="contenu-portfolio" data-lazy="/portfolio/<?= $portfolio_id ?>/contenu?table=1&noLayout=1&entreprise=<?= $entreprise_id ?>"></div>
                
            </div>
        </div>
    </div>
</div>