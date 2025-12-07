<?php
    $ins = Database::instance()->execute("
        SELECT 
            ins.isin, 
            ins.nom,
            ins.symbole,
            di.code as code_devise,
            di.symbole as devise,
            p.nom as nom_portfolio,
            dp.symbole as devise_portfolio,
            ins.type,

            e.nom as nom_entreprise,
            b.id AS id_bourse,
            b.nom AS nom_bourse,
            CONCAT(e.code_pays, e.numero) as id_entreprise
        FROM Instrument_Financier ins
            LEFT JOIN Devise di ON di.code = ins.code_devise
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

    $formatter = new IntlDateFormatter(
                        'fr_FR', 
                        IntlDateFormatter::FULL,
                        IntlDateFormatter::SHORT);
    // EEEE = Nom du Jour, MMMM = Nom du mois
    $formatter->setPattern("EEEE d MMMM yyyy 'à' HH'h'mm");
    $formatter->setTimeZone("Europe/Brussels");

    if (isset($_GET["table"])) {
        require_once __DIR__ . "/../lib/affichage_table2.php";

        $tbl = new TableHelper(
            "/portfolio/$portfolio_id/instrument/$instrument_id?table=1&noLayout=1",
            "#transactions-".$ins["isin"],
            "#date-filter"
        );


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

    
    $formatter->setPattern("EEEE d MMMM yyyy");
    $formatter->setTimeZone("Europe/Brussels");

    //
    $cours = Database::instance()
        ->execute("
        WITH ValeurCours AS (
            SELECT 
                c.*,
                ROW_NUMBER() OVER(PARTITION BY c.isin ORDER BY c.date DESC) as rang
            FROM Cours c
            WHERE 
                c.isin = ?
                AND c.date >= DATE_SUB(CURDATE(), INTERVAL 15 DAY)
            ORDER BY c.date DESC
        )
        SELECT 
            c.date,
            ROUND(c.valeur_maximale, 2) as valeur_maximale,
            ROUND(c.valeur_minimale, 2) as valeur_minimale,
            ROUND(c.valeur_ouverture, 2) as valeur_ouverture,
            ROUND(c.valeur_fermeture, 2) as valeur_fermeture,
            c.volume,
            ROUND(((c.valeur_fermeture - c_prev.valeur_fermeture) / NULLIF(c_prev.valeur_fermeture, 0)) * 100, 2) AS p_change
        FROM ValeurCours c
        LEFT JOIN ValeurCours c_prev ON c_prev.rang = c.rang + 1
        LIMIT 1
        ", [$ins["isin"]])
        ->fetch();

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
    </div>

    <div id="edit-instrument" data-reload-on-callback="edit-ins" class="popup" data-popup="1" style="display: none" data-load="/portfolio/<?=  $portfolio_id ?>/instruments?callback_id=edit-ins&form=1&nopopup=1&update=<?= $instrument_id ?>"></div>

    <br>

    <div class="graph" style="width:100%; padding: 8px; display: flex; flex-direction: column; align-items: center; justify-content: center">
        <div class="graph-container" style="position: relative; height:40vh; width:100%; margin:auto;">
            <canvas id="graph" data="<?= $ins["isin"] ?>" data-type="cours" currency="<?= $ins["devise"] ?>" label="<?= $ins["nom"]?>" type="candlestick"></canvas>
        </div>
        <div style="display: flex; flex-direction: row; gap: 8px;">
            <button class="button" id="week">1 Semaine</button>
            <button class="button" id="month">1 Mois</button>
            <button class="button" id="year">1 Année</button>
        </div>
    </div>

     <div class="section center">
        <div class="card">
            <?php if(/*$ins["type"] === "action"*/true) {
                echo "<div>Devise d'échange : ".htmlspecialchars($ins["devise"]). " (" . htmlspecialchars($ins["code_devise"]) . ") </div>";
            } ?>

            <div>Dernier cours enregistré: <?= ucfirst($formatter->format(new DateTime($cours["date"], new DateTimeZone('Europe/Brussels')))); ?></div>

            <div>Valeur maximale : <?= $cours["valeur_maximale"] ?> <?= $ins["devise"] ?></div>
            <div>Valeur minimale : <?= $cours["valeur_minimale"] ?> <?= $ins["devise"] ?></div>
            <div>Ouverture : <?= $cours["valeur_ouverture"] ?> <?= $ins["devise"] ?></div>
            <div>Clôture : <?= $cours["valeur_fermeture"] ?> <?= $ins["devise"] ?></div>
            <div>Volume : <?= $cours["volume"] ?></div>
            <div>%Change day : <?= with_color_val("span", $cours["p_change"], '%') ?></div>
        </div>
     </div>

     <div class="section">
        <div class="row header-search">
            <h3>Transactions réalisées sur l'instrument financier</h3>
            <label for="date-filer">Après le:</label>
            <input placeholder="Rechercher" id="date-filter" type="date" name="date" value="" oninput="search_ajax_debounce(this, '#transactions-<?= $isin ?>', 0, '/portfolio/<?= $portfolio_id ?>/instrument/<?= $instrument_id ?>?table=1');" />
        </div>

        <div id="transactions-<?=$instrument_id ?>" data-lazy="/portfolio/<?= $portfolio_id ?>/instrument/<?= $instrument_id ?>?table=1&noLayout=1"></div>

    </div>
</div>