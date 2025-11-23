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

    /* Instruments Financier ayant le plus de variations */
    $instruments = Database::instance()->execute("
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
LIMIT 3", [$portfolio_id]);
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
            <tbody>
                <?php foreach ($instruments as $ins) {?>
                    <tr>
                        <td><?= @$ins["nom"] ?></td>
                        <td><?= @$ins["valeur"] ?></td>
                        <td><?= @$ins["prix_moyen_achat"] ?></td>
                        <td><?= @$ins["prix_actuel"] ?></td>
                        <?= with_color("td", $ins["inc"], $ins["p_change"]) ?>
                        <?= with_color_val("td", $ins["profit"]) ?>
                       
                    </tr>
                <?php } ?>
            </tbody>
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