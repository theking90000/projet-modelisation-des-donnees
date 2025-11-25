<?php
    require_once __DIR__ . '/../template/layout.php';

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

    if(isset($_GET["table"])) {
        $cols = [
            "nom"=>"Instrument financier",
            "valeur"=>"Valeur",
            "prix_moyen_achat"=>"Prix Moyen (achat)",
            "prix_actuel"=> "Prix Actuel",
            "p_change"=> "% Change Day",
            "profit"=> "Profit"
        ];
        $recherche = $_GET["recherche"] ?? "";
        $page = intval($_GET["page"] ?? 0);
        $limit = intval($_GET["perPage"] ?? 20);
        $offset = $page*$limit;
        $orderBy = $_GET["sort"] ?? "valeur";
        $orderByType = $_GET["sortType"] ?? "desc";

        $hideSort = isset($_GET["hideSort"]);

        if(!array_key_exists($orderBy, $cols)
            || ($orderByType != 'desc' && $orderByType != 'asc' )) {
            throw new Exception("Erreur sort invalide");
        }

        // TODO: améliorer la performance de cette requête en mettant en cache `MAX(c.date)`
        // Via un JOIN OU WITH
       
       /* 
        |----------------------------------------------------------|
        | Requete Originale (lente, > 1s pour 3 actifs)            |
        |                                                          |
        |----------------------------------------------------------|

        $stmt = Database::instance()->execute("
            SELECT t.isin, t.nom, ROUND(t.quantite*ajd_val, 2) AS valeur, 
ROUND(t.prix_achat/t.quantite, 2) AS prix_moyen_achat, ROUND(ajd_val, 2) AS prix_actuel,
ROUND(pChange, 2) AS p_change,
ajd_val > hier_val AS inc,
ROUND((t.quantite *ajd_val - t.prix_achat - t.frais - t.taxes), 2) AS profit
FROM (
SELECT
	ins.isin,
	ins.nom,
	SUM(
	CASE 
		WHEN t.type = 'achat' THEN t.quantite
		WHEN t.type = 'vente' THEN -t.quantite 
		ELSE 0
	END) as quantite,
	(SUM(
	CASE 
		WHEN t.type = 'achat' THEN t.valeur_devise_portfolio 
		WHEN t.type = 'vente' THEN -t.valeur_devise_portfolio 
		ELSE 0
	END
	)) as prix_achat,
	SUM(t.taxes) AS taxes,
	SUM(t.frais) AS frais,
	ajd.date AS ajd_date,
	hier.date AS hier_date,
	ajd.valeur_fermeture AS ajd_val,
	hier.valeur_fermeture AS hier_val,
	(1-(LEAST(ajd.valeur_fermeture, hier.valeur_fermeture)/ GREATEST(ajd.valeur_fermeture, hier.valeur_fermeture)))  * 100  AS pChange
FROM Transaction t
JOIN Instrument_Financier ins ON ins.isin = t.isin
LEFT JOIN Cours ajd ON ins.isin = ajd.isin
LEFT JOIN Cours hier ON ins.isin = hier.isin
WHERE 
	t.id_portfolio  = ?
	AND ajd.date = (SELECT MAX(c.date) FROM Cours c WHERE c.isin = ajd.isin)
	AND hier.date = (SELECT MAX(c.date) FROM Cours c WHERE c.isin = hier.isin AND c.date < ajd.date)
GROUP BY t.isin, ajd.date, hier.date
HAVING quantite > 0
ORDER BY pChange DESC) AS t
WHERE t.nom LIKE CONCAT('%', ?, '%')
ORDER BY $orderBy $orderByType
LIMIT $limit OFFSET $offset", [$portfolio_id, $recherche]);

*/

/*
        |----------------------------------------------------------|
        | Version optimisée avec CTE ("With"), tables              |
        | "Virtuelles"                                             |
        |----------------------------------------------------------|

        - Une Table pour les actifs contenu de le portfolio (au moins une transaction existante)
           On ne regarde pas la quantité

        - Une table pour classer les Cours de bourse par ordre décroissant de date.
          et récupérer "aujourd'hui" et "hier" rapidement

        - Ensuite on Join sur les dates avec rang=1 (ajd) et rang=2 (hier)
*/

        $stmt = Database::instance()->execute("
        SELECT 
            isin, 
            nom,
            ROUND(stock_qte * prix_ajd, 2) AS valeur,
            ROUND(prix_ajd, 2) AS prix_actuel,
            ROUND(stock_investi / stock_qte, 2) AS prix_moyen_achat,
            ROUND(((prix_ajd - prix_hier) / NULLIF(prix_hier, 0)) * 100, 2) AS p_change,
            ROUND((stock_qte * prix_ajd) - stock_investi - total_frais - total_taxes, 2) AS profit
        FROM (WITH Actifs AS (
            SELECT DISTINCT isin 
            FROM Transaction 
            WHERE id_portfolio = ?
        ),
        ClassementCours AS (
            SELECT 
                c.isin, 
                c.valeur_fermeture, 
                c.date,
                ROW_NUMBER() OVER(PARTITION BY c.isin ORDER BY c.date DESC) as rang
            FROM Cours c
            INNER JOIN Actifs a ON c.isin = a.isin
            WHERE c.date >= DATE_SUB(CURDATE(), INTERVAL 15 DAY)
        )
        SELECT 
            t.isin, 
            ins.nom,
            ajd.valeur_fermeture AS prix_ajd,
            hier.valeur_fermeture AS prix_hier,
            SUM(CASE WHEN t.type = 'achat' THEN t.quantite ELSE -t.quantite END) AS stock_qte,
            SUM(CASE WHEN t.type ='achat' THEN t.valeur_devise_portfolio ELSE -t.valeur_devise_portfolio END) as stock_investi,
            SUM(t.frais) AS total_frais,
            SUM(t.taxes) AS total_taxes
        FROM Transaction t
            JOIN Instrument_Financier ins ON ins.isin = t.isin
            LEFT JOIN ClassementCours ajd ON t.isin = ajd.isin AND ajd.rang = 1
            LEFT JOIN ClassementCours hier ON t.isin = hier.isin AND hier.rang = 2
        WHERE t.id_portfolio = ? AND ins.nom LIKE CONCAT('%', ?, '%')
        GROUP BY t.isin, ins.nom, ajd.valeur_fermeture, hier.valeur_fermeture, ajd.date
        HAVING stock_qte > 0) s
        ", [$portfolio_id, $portfolio_id, $recherche]);

        echo "<table class=\"data-table\">\n<thead>\n<tr>\n";
        foreach ($cols as $k => $v) {
            echo "<th ";
            if (!$hideSort) {
                echo "style=\"cursor: pointer;\" onclick=\"";
                echo 'search_ajax(\'#contenu-filter\', \'#contenu-portfolio\', 0, \'/portfolio/';
                echo $portfolio_id.'/contenu?table=1&sort=';
                echo urlencode($k) . '&sortType=';
                if($orderByType == "desc" && $orderBy == $k) {
                    echo "asc";
                } else {
                    echo "desc";
                }
                echo '\');';
            }
            echo "\">";
            echo htmlspecialchars($v);
                if(!$hideSort) {
                if ($orderBy == $k && $orderByType == "desc") {
                    echo "&#9660;&nbsp;";
                }
                if ($orderBy == $k && $orderByType == "asc") {
                    echo "&#9650;&nbsp;";
                }
            }
            echo "</th>\n";
        }
        echo "<th></th></tr>\n</thead>\n<tbody>\n";

        while($row = $stmt->fetch()) {
            echo "<tr>";
            foreach ($cols as $k => $v) {
                if ($k == "p_change") {
                    echo with_color_val("td", $row["p_change"]);
                   // echo with_color("td", $row["inc"], $row["p_change"]);
                } else if ($k == "profit") {
                    echo with_color_val("td", $row["profit"]);
                } else {
                    echo "<td>". htmlspecialchars($row[$k]) ."</td>\n";
                }
            }

            echo "<td><a class='button' href=\"/portfolio/";
            echo $portfolio_id. "/instrument/". $row["isin"];
            echo "\">Voir</a>";

            echo "</td></tr>\n";
        }

        echo "</tbody>\n</table>\n";

        if(!isset($_GET["noPagination"])) {
            $count = Database::instance()->execute("
                SELECT COUNT(*) as total FROM (
                    SELECT t.isin FROM Transaction t
                        JOIN Instrument_Financier ins ON ins.isin = t.isin
                        WHERE t.id_portfolio = ? AND ins.nom LIKE CONCAT('%', ?, '%')
                        GROUP BY t.isin
                        HAVING SUM(CASE 
                            WHEN t.type = 'achat' THEN t.quantite
                            WHEN t.type = 'vente' THEN -t.quantite 
                            ELSE 0
                        END) > 0
                ) as f
            ",[$portfolio_id, $recherche])->fetch()["total"];

            // Count total;
            $nav = function ($page, $text, $id) use($orderBy, $orderByType,$recherche) {
                echo "<a href=\"#\" onclick=\"search_ajax('#contenu-filter', '#contenu-portfolio', ";
                
                echo $page;
                echo ", '";
                
                echo "/portfolio/$id/contenu?table=1";
                echo "&sortType=$orderByType&sort=$orderBy&recherche=". htmlspecialchars($recherche);
                echo "'); return false;\" >";

                echo $text;

                echo "</a>\n";
            };

            if ($page>0) $nav($page-1, "Page précédente",$portfolio_id);
            if (($page+1)*$limit<$count) $nav($page + 1, "Page suivante",$portfolio_id);
        }
    
        die();
    }

    template_head(["title"=>"Contenu du portfolio"]);
?>

<?= print_portfolio_header($portfolio_id, $portfolio["nom"], "/portfolio/$portfolio_id") ?>

<div class="portfolio-main">
    <div class="section">
        <div class="m-col header-search">
            <h3>Instruments Financier</h3>
                <input placeholder="Rechercher" id="contenu-filter" type="search" value="" oninput="search_ajax_debounce(this, '#contenu-portfolio', 0, '/portfolio/<?= $portfolio_id ?>/contenu?table=1');" />

        </div>
        <div id="contenu-portfolio">
            
        </div>

        <script>
            search_ajax("#contenu-filter", "#contenu-portfolio", 0, "/portfolio/<?= $portfolio_id ?>/contenu?table=1    ")
        </script>
    </div>
</div>

<?php 
template_tail();