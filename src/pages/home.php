<?php
    require_once __DIR__ . '/../template/utils.php';

    // 1. Fetch User's Portfolios
    $stmt = Database::instance()->execute(
        "SELECT p.id, p.nom, p.description, p.date_creation, d.symbole as devise 
         FROM Portfolio p 
         JOIN Membre_Portfolio mp ON p.id = mp.id_portfolio 
         JOIN Utilisateur u ON u.email = mp.email 
         JOIN Devise d ON p.code_devise = d.code
         WHERE u.email = ?
         ORDER BY p.date_creation DESC", 
        [Auth::user()]
    );
    $portfolios = $stmt->fetchAll();
?>

<?= print_header("Mes Portfolios", create_button("Se déconnecter", "/logout", image("arrow-right.svg")), "house.svg", "/") ?>

<div class="portfolio-main">
    
    <div class="section">
        <div class="row header-search">
            <h3>Bienvenue, <?= htmlspecialchars(Auth::user()) ?></h3>
            <a href="#" class="button" data-open="#nouveau-portfolio">
                <span>+</span> Nouveau Portfolio
            </a>
        </div>
    </div>

    <div class="section" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(600px, 1fr)); gap: 30px; margin-top: 30px;">
        <?php if (empty($portfolios)): ?>
            <div class="card" style="text-align: center; color: #64748b; padding: 40px;">
                <p>Vous n'avez aucun portfolio pour le moment.</p>
            </div>
        <?php else: ?>
            <?php foreach ($portfolios as $p): ?>
                <?php
                    // --- STRICT MATCHING CALCULATION LOGIC ---
                    // This query mirrors `contenu_portfolio.php` logic exactly:
                    // 1. Filter prices > 15 days
                    // 2. Round each asset value to 2 decimals BEFORE summing
                    
                    $stats = Database::instance()->execute("
                        WITH 
                        -- 1. Get Assets in this portfolio
                        Actifs AS (
                            SELECT DISTINCT isin 
                            FROM `Transaction` 
                            WHERE id_portfolio = ?
                        ),
                        -- 2. Get Prices (Limit to 15 days to match details page)
                        ClassementCours AS (
                            SELECT 
                                c.isin, 
                                c.valeur_fermeture, 
                                ROW_NUMBER() OVER(PARTITION BY c.isin ORDER BY c.date DESC) as rang
                            FROM Cours c
                            INNER JOIN Actifs a ON c.isin = a.isin
                            WHERE c.date >= DATE_SUB(CURDATE(), INTERVAL 15 DAY)
                        ),
                        -- 3. Calculate Quantity per Asset
                        Holdings AS (
                            SELECT 
                                t.isin,
                                SUM(CASE WHEN t.type = 'achat' THEN t.quantite WHEN t.type = 'vente' THEN -t.quantite ELSE 0 END) AS quantity
                            FROM `Transaction` t
                            WHERE t.id_portfolio = ?
                            GROUP BY t.isin
                            HAVING quantity > 0
                        ),
                        -- 4. Calculate Values per Asset
                        AssetValues AS (
                            SELECT
                                h.isin,
                                -- Important: Round individual lines like the details page does
                                ROUND(h.quantity * p1.valeur_fermeture, 2) as val_now,
                                ROUND(h.quantity * p2.valeur_fermeture, 2) as val_prev
                            FROM Holdings h
                            LEFT JOIN ClassementCours p1 ON h.isin = p1.isin AND p1.rang = 1
                            LEFT JOIN ClassementCours p2 ON h.isin = p2.isin AND p2.rang = 2
                        )
                        -- 5. Sum the Rounded Values
                        SELECT 
                            ROUND(SUM(val_now), 2) as total_now,
                            ROUND(SUM(val_prev), 2) as total_prev,
                            ROUND(((SUM(val_now) - SUM(val_prev)) / NULLIF(SUM(val_prev), 0)) * 100, 2) AS p_change,
                            ROUND(SUM(val_now) - SUM(val_prev) , 2) AS profit_day
                        FROM AssetValues
                    ", [$p['id'], $p['id']])->fetch();

                    $valeur = $stats['total_now'];
                    $prev = $stats['total_prev'];
                    $diff = $stats['profit_day'];
                    $pct = $stats['p_change'];
                ?>

                <div class="card" style="display: flex; flex-direction: column; justify-content: space-between; padding: 30px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
                    
                    <div style="margin-bottom: 20px;">
                        <div style="font-weight: 800; font-size: 1.6em; color: var(--primary-color); letter-spacing: -0.5px; margin-bottom: 6px;">
                            <?= htmlspecialchars($p['nom']) ?>
                        </div>
                        <div style="color: #64748b; font-size: 1em; font-weight: 500;">
                            <?= htmlspecialchars($p['description'] ?: 'Gérez vos actifs financiers') ?>
                        </div>
                    </div>

                    <div style="background: #f1f5f9; padding: 25px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; margin-bottom: 25px;">
                        
                        <div style="flex-grow: 1;">
                            <div style="font-size: 0.85em; text-transform: uppercase; letter-spacing: 1px; color: #64748b; margin-bottom: 5px;">Valeur Totale</div>
                            <div style="font-size: 2.2em; font-weight: 800; color: #0f172a; line-height: 1; white-space: nowrap;">
                                <?= $valeur ?? 0 ?> 
                                <span style="font-size: 0.5em; vertical-align: super; color: #64748b;"><?= $p['devise'] ?></span>
                            </div>
                        </div>

                        <div style="text-align: right;">
                            <div style="font-size: 0.85em; color: #64748b; margin-bottom: 5px;">Variation 24h</div>
                            <div style="display: flex; align-items: center; gap: 10px; justify-content: flex-end;">
                                <div style="font-size: 1.1em; font-weight: 600;">
                                    <?= with_color_val("span", $diff, $p['devise']) ?>
                                </div>
                                <div style="background: <?= $diff >= 0 ? '#dcfce7' : '#fee2e2' ?>; padding: 4px 10px; border-radius: 6px; font-weight: 700; font-size: 0.9em;">
                                    <?= with_color_val("span", $pct, "%") ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div style="text-align: right;">
                        <?= create_button("Accéder au portfolio", "/portfolio/" . $p['id'], image("arrow-right.svg")) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div id="nouveau-portfolio" class="popup" data-popup="1" style="display: <?php 
        if (isset($errors)) { echo 'block'; } else { echo 'none'; }
    ?>;">
        <h3>Créer un nouveau portfolio</h3>
        <form action="/" method="post" class="center-col">
            <label for="nom">Nom du portfolio</label>
            <input type="text" name="nom" id="nom" value="<?= @$value["nom"] ?>" placeholder="Ex: Investissements 2025">
             <?php if(isset($errors["nom"])) { ?>
                <span style="color: red;"><?= $errors["nom"] ?></span>
            <?php } ?>

            <label for="description">Description</label>
            <input type="text" name="description" id="description" value="<?= @$value["description"] ?>" placeholder="Optionnel">
            <?php if(isset($errors["description"])) { ?>
                <span style="color: red;"><?= $errors["description"] ?></span>
            <?php } ?>

            <button type="button" data-name="devise" data-value="<?= @$value["devise"] ?>" 
            placeholder="Devise principale"
            value="<?= @$value["devise"] ?>"
            data-ext-select="/devises"
            ><?= $value["nom_devise"] ?? "Devise principale" ?> </button>
            <?php if(isset($errors["devise"])) { ?>
                <span style="color: red;"><?= $errors["devise"] ?></span>
            <?php } ?>

            <input type="submit" value="Créer le portfolio">
            <?php if(isset($errors["general"])) { ?>
                <span style="color: red;"><?= $errors["general"] ?></span>
            <?php } ?>
        </form>
    </div>

</div>