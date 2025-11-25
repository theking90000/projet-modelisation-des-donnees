<?php
    $ins = Database::instance()->execute("
        SELECT 
            ins.isin, 
            ins.nom,
            ins.symbole,
            p.nom as nom_portfolio,
            dp.symbole as devise_portfolio,
            ins.type,

            e.nom as nom_entreprise,
            b.id AS id_bourse,
            b.nom AS nom_bourse,
            CONCAT(e.code_pays, e.numero) as id_entreprise
        FROM Instrument_Financier ins
            JOIN Portfolio p ON p.id = ?
            JOIN Devise dp ON dp.code = p.code_devise
            LEFT JOIN Entreprise e ON e.numero = ins.numero_entreprise AND e.code_pays = ins.pays_entreprise
            LEFT JOIN Bourse b ON b.id = ins.id_bourse
        WHERE 
            isin = ?;
    ", [$portfolio_id, $instrument_id]);
    
    $ins = $ins->fetch();

    if(!$ins) {
        render_page(__DIR__."/404.php", ["title"=>"Finance App - Erreur 404"]);
        die();
    }

    if (isset($_GET["table"])) {
        require_once __DIR__ . "/../lib/affichage_table2.php";

        $tbl = new TableHelper(
            "/portfolio/$portfolio_id/instrument/$instrument_id?table=1&noLayout=1",
            "#transactions-".$ins["isin"],
            "#date-filter"
        );

        $formatter = new IntlDateFormatter(
                        'fr_FR', 
                        IntlDateFormatter::FULL,
                        IntlDateFormatter::SHORT);
        // EEEE = Nom du Jour, MMMM = Nom du mois
        $formatter->setPattern("EEEE d MMMM yyyy 'à' HH'h'mm");
        $formatter->setTimeZone("Europe/Brussels");


        $tbl->addColumn("date", "Date", [
                "renderer"=> function ($row) use ($formatter) {
                    $dt = new DateTime($row["date"]. " ". $row["heure"], new DateTimeZone('Europe/Brussels'));
                    return ucfirst($formatter->format($dt));
                }
            ])
            ->addColumn("nom_utilisateur", "Utilisateur")
            ->addColumn("type", "type")
            ->addColumn("quantite", "Quantité")
            ->addColumn("valeur", "Valeur (".$ins['devise_portfolio'].")", ["type"=>"colored_number"]);

        $tbl->setDefaultSort("date", "desc");

        $tbl->render(
            // Résultats
            function($page, $limit, $orderBy, $orderByType, $recherche) use ($portfolio_id, $instrument_id) {
            $offset = $page * $limit;
            $sql = "
            SELECT 
                t.date,
                t.heure,
                CONCAT(u.nom, ' ', u.prenom) AS nom_utilisateur,
                t.type,
                t.quantite,
                ROUND(t.valeur_devise_portfolio * CASE WHEN t.type = 'achat' THEN 1 ELSE -1 END, 2) as valeur
            FROM Transaction t
                LEFT JOIN Utilisateur u ON u.email = t.email_utilisateur
            WHERE t.id_portfolio = ? AND t.isin = ? ". (!empty($recherche) ? "AND t.date >= ? " : '') ."
            ORDER BY $orderBy $orderByType
            LIMIT $limit OFFSET $offset 
            ";

            return Database::instance()->execute($sql, [$portfolio_id,  $instrument_id,  empty($recherche)?null:$recherche])->fetchAll();
            },
            // Total
            function($recherche) use ($portfolio_id, $instrument_id) {
            return Database::instance()->execute("
                SELECT 
                    COUNT(*) as total 
                FROM Transaction t
                WHERE t.id_portfolio = ? AND t.isin = ? ".(!empty($recherche) ? "AND t.date >= ? " : '')."
                ",
                [$portfolio_id,  $instrument_id,  empty($recherche)?null:$recherche])
                ->fetch()["total"];
            }
        );
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

        <a href="#" class="button" data-open="#edit-instrument">Editer</a>
   
        </div>

         <div id="edit-instrument" data-reload-on-callback="edit-ins" class="popup" data-popup="1" style="display: none" data-load="/portfolio/<?=  $portfolio_id ?>/instruments?callback_id=edit-ins&form=1&nopopup=1&update=<?= $instrument_id ?>"></div>

        <br>
        <div>Afficher les infos sur la performance ici</div> 

        <div class="portfolio-main">
            <div class="graph">
                 Ici, Graphique
            </div>

             <div class="section">
                <div class="row header-search">
                    <h3>Transactions réalisées sur l'instrument financier</h3>
                    <label for="date-filer">Après le:</label>
                    <input placeholder="Rechercher" id="date-filter" type="date" name="date" value="" oninput="search_ajax_debounce(this, '#contenu-portfolio', 0, '/portfolio/<?= $portfolio_id ?>/instrument/<?= $instrument_id ?>?table=1');" />
                </div>

                <div id="transactions-<?=$instrument_id ?>" data-lazy="/portfolio/<?= $portfolio_id ?>/instrument/<?= $instrument_id ?>?table=1&noLayout=1"></div>
                
            </div>
        </div>
    </div>
</div>