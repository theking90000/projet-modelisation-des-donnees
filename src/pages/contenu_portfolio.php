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
ORDER BY pChange DESC) AS t
WHERE t.nom LIKE CONCAT('%', ?, '%')
ORDER BY $orderBy $orderByType
LIMIT $limit OFFSET $offset", [$portfolio_id, $recherche]);

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
        echo "</tr>\n</thead>\n<tbody>\n";

        while($row = $stmt->fetch()) {
            echo "<tr>";
            foreach ($cols as $k => $v) {
                if ($k == "p_change") {
                    echo with_color("td", $row["inc"], $row["p_change"]);
                } else if ($k == "profit") {
                    echo with_color_val("td", $row["profit"]);
                } else {
                    echo "<td>". htmlspecialchars($row[$k]) ."</td>\n";
                }
            }
            echo "</tr>\n";
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
            if ($page*$limit<$count) $nav($page + 1, "Page suivante",$portfolio_id);
        }
    
        die();
    }

    template_head(["title"=>"Contenu du portfolio"]);
?>

<?= print_portfolio_header_back($portfolio_id, $portfolio["nom"]) ?>

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