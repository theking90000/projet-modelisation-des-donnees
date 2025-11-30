<?php
    $portfolio = Database::instance()->execute("
        SELECT 
            p.nom as nom,
            dp.symbole as devise_portfolio
        FROM Portfolio p
            JOIN Devise dp ON dp.code = p.code_devise
        WHERE 
            p.id = ?;
    ", [$portfolio_id])->fetch();
    
    if(!$portfolio) {
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
            "/portfolio/$portfolio_id/transactions?table=1&noLayout=1",
            "#transactions",
            "#transaction-filter"
        );


        $tbl->addColumn("date", "Date", [
                "renderer"=> function ($row) use ($formatter) {
                    $dt = new DateTime($row["date"]. " ". $row["heure"], new DateTimeZone('Europe/Brussels'));
                    return ucfirst($formatter->format($dt));
                }
            ])
            ->addColumn("nom_instrument", "Instrument", [
                "renderer"=> function ($row) use ($formatter, $portfolio_id) {
                    return "<a href=\"/portfolio/".$portfolio_id. "/instrument/" . $row["isin"]
                    . "\">" . htmlspecialchars($row["nom_instrument"]) . "</a>";
                }
            ])
            ->addColumn("nom_utilisateur", "Utilisateur")
            ->addColumn("type", "type")
            ->addColumn("quantite", "Quantité")
            ->addColumn("valeur", "Valeur (".$portfolio['devise_portfolio'].")", ["type"=>"colored_number"]);

        $tbl->setDefaultSort("date", "desc");

        $tbl->render(
            // Résultats
            function($page, $limit, $orderBy, $orderByType, $recherche) use ($portfolio_id) {
            $offset = $page * $limit;
            $sql = "
            SELECT 
                t.date,
                t.heure,
                CONCAT(u.nom, ' ', u.prenom) AS nom_utilisateur,
                t.type,
                t.quantite,
                i.nom AS nom_instrument,
                i.isin AS isin,
                ROUND(t.valeur_devise_portfolio * CASE WHEN t.type = 'achat' THEN 1 ELSE -1 END, 2) as valeur
            FROM Transaction t
                LEFT JOIN Utilisateur u ON u.email = t.email_utilisateur
                LEFT JOIN Instrument_Financier i ON i.isin = t.isin
            WHERE t.id_portfolio = ? ". (!empty($recherche) ? "AND i.nom LIKE CONCAT('%', ?, '%') " : '') ."
            ORDER BY $orderBy $orderByType
            LIMIT $limit OFFSET $offset 
            ";

            return Database::instance()->execute($sql, [$portfolio_id,  empty($recherche)?null:$recherche])->fetchAll();
            },
            // Total
            function($recherche) use ($portfolio_id) {
            return Database::instance()->execute("
                SELECT 
                    COUNT(*) as total 
                FROM Transaction t
                WHERE t.id_portfolio = ? ".(!empty($recherche) ? "AND t.date >= ? " : '')."
                ",
                [$portfolio_id,  empty($recherche)?null:$recherche])
                ->fetch()["total"];
            }
        );
        die();
    }



?>

<?= print_portfolio_header($portfolio_id, $portfolio["nom"], "/portfolio/$portfolio_id") ?>

<div class="portfolio-main">

    <div class="section">
            <div class="m-col header-search">
                <h3>Transactions</h3>
                <input placeholder="Rechercher" id="transaction-filter" type="search" value="" oninput="search_ajax_debounce(this, '#transactions', 0, '/portfolio/<?= $portfolio_id ?>/transactions?table=1&noLayout=1');" />
            </div>

            <div id="transactions" data-lazy="/portfolio/<?= $portfolio_id ?>/transactions?table=1&noLayout=1"></div>

            <script>
                search_ajax("#transactions", "#contenu-portfolio", 0, "/portfolio/<?= $portfolio_id ?>/contenu?table=1")
            </script>
    </div>
</div>