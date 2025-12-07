<?php
    require_once __DIR__ . '/../template/utils.php';

    // 1. Fetch Bourse Details
    $bourse = Database::instance()->execute(
        "SELECT b.*, p.nom as nom_pays 
         FROM Bourse b
         LEFT JOIN Pays p ON b.code_pays = p.code
         WHERE b.id = ?", 
        [$bourse_id]
    )->fetch();

    if(!$bourse) {
        echo "Bourse not found in db";
        #render_page("404.php");
        #die();
    }

    // 2. Fetch Instruments in this Portfolio from this Bourse
    // We reuse the robust logic from home.php/contenu_portfolio.php to get accurate values
    $instruments = Database::instance()->execute("
        WITH 
        -- 1. Get Assets in this portfolio LINKED TO THIS BOURSE
        Actifs AS (
            SELECT DISTINCT t.isin 
            FROM `Transaction` t
            JOIN Instrument_Financier i ON t.isin = i.isin
            WHERE t.id_portfolio = ? AND i.id_bourse = ?
        ),
        -- 2. Get Prices
        ClassementCours AS (
            SELECT 
                c.isin, 
                c.valeur_fermeture, 
                c.date,
                ROW_NUMBER() OVER(PARTITION BY c.isin ORDER BY c.date DESC) as rang
            FROM Cours c
            INNER JOIN Actifs a ON c.isin = a.isin
            WHERE c.date >= DATE_SUB(CURDATE(), INTERVAL 15 DAY)
        ),
        -- 3. Calculate Quantity per Asset
        Holdings AS (
            SELECT 
                t.isin,
                SUM(CASE WHEN t.type = 'achat' THEN t.quantite WHEN t.type = 'vente' THEN -t.quantite ELSE 0 END) AS quantity,
                SUM(CASE WHEN t.type ='achat' THEN t.valeur_devise_portfolio WHEN t.type = 'vente' THEN -t.valeur_devise_portfolio ELSE 0 END) as investissement
            FROM `Transaction` t
            WHERE t.id_portfolio = ?
            GROUP BY t.isin
            HAVING quantity > 0
        )
        -- 4. Final Selection
        SELECT 
            i.nom, 
            i.isin, 
            i.symbole,
            i.type,
            h.quantity,
            ROUND(h.quantity * p1.valeur_fermeture, 2) as val_now,
            ROUND(((p1.valeur_fermeture - p2.valeur_fermeture) / NULLIF(p2.valeur_fermeture, 0)) * 100, 2) AS p_change,
            p1.date as last_update
        FROM Holdings h
        JOIN Instrument_Financier i ON h.isin = i.isin
        LEFT JOIN ClassementCours p1 ON h.isin = p1.isin AND p1.rang = 1
        LEFT JOIN ClassementCours p2 ON h.isin = p2.isin AND p2.rang = 2
        WHERE i.id_bourse = ?
        ORDER BY val_now DESC
    ", [$portfolio_id, $bourse_id, $portfolio_id, $bourse_id])->fetchAll();
    
    // Get Portfolio Currency for display
    $pf_devise = Database::instance()->execute("SELECT d.symbole FROM Portfolio p JOIN Devise d ON p.code_devise = d.code WHERE p.id = ?", [$portfolio_id])->fetch()['symbole'];

    // Format Times
    $open = new DateTime($bourse['heure_ouverture']);
    $close = new DateTime($bourse['heure_fermeture']);
?>

<?= print_portfolio_header_back($portfolio_id, $bourse['nom']) ?>

<div class="portfolio-main">

    <div class="section">
        <div class="card" style="display: flex; flex-direction: row; flex-wrap: wrap; gap: 30px; padding: 30px; align-items: center;">
            
            <div style="flex: 1; min-width: 250px;">
                <div style="font-size: 0.9em; color: #64748b; margin-bottom: 5px;">Place Boursière</div>
                <div style="font-size: 2em; font-weight: 800; color: var(--primary-color); line-height: 1.1;">
                    <?= htmlspecialchars($bourse['id']) ?>
                </div>
                <div style="font-size: 1.1em; color: #64748b; margin-top: 5px; display: flex; align-items: center; gap: 8px;">
                     <?= htmlspecialchars($bourse['ville']) ?>, <?= htmlspecialchars($bourse['nom_pays']) ?>
                </div>
            </div>

            <div style="width: 1px; background: #e2e8f0; height: 80px; display: block;"></div>

            <div style="flex: 2; display: flex; gap: 40px; flex-wrap: wrap;">
                <div>
                    <div style="font-size: 0.85em; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Horaires (Local)</div>
                    <div style="font-weight: 600; font-size: 1.1em; color: #0f172a;">
                        <?= $open->format('H:i') ?> - <?= $close->format('H:i') ?>
                    </div>
                </div>
                
                <div>
                     <div style="font-size: 0.85em; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Fuseau Horaire</div>
                    <div style="font-weight: 600; font-size: 1.1em; color: #0f172a;">
                        <?= htmlspecialchars($bourse['fuseau_horaire']) ?>
                    </div>
                </div>

                <div>
                     <div style="font-size: 0.85em; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Instruments Détenus</div>
                    <div style="font-weight: 600; font-size: 1.1em; color: var(--secondary-color-violet);">
                        <?= count($instruments) ?>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="section">
        <h3>Vos actifs sur <?= htmlspecialchars($bourse['nom']) ?></h3>
        
        <?php if(empty($instruments)): ?>
            <p style="color: #64748b; margin-top: 20px;">Aucun instrument de ce portfolio n'est listé sur cette bourse.</p>
        <?php else: ?>
            <div class="card" style="padding: 0; overflow: hidden; margin-top: 15px;">
                <table class="data-table" style="margin: 0; box-shadow: none;">
                    <thead>
                        <tr>
                            <th style="padding-left: 30px;">Instrument</th>
                            <th>Symbole</th>
                            <th>Type</th>
                            <th>Quantité</th>
                            <th>Valeur (<?= $pf_devise ?>)</th>
                            <th>Var. 24h</th>
                            <th style="padding-right: 30px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($instruments as $i): ?>
                        <tr>
                            <td style="padding-left: 30px; font-weight: 600;">
                                <a href="/portfolio/<?= $portfolio_id ?>/instrument/<?= $i['isin'] ?>" style="text-decoration: none; color: inherit; background: none; padding: 0;">
                                    <?= htmlspecialchars($i['nom']) ?>
                                </a>
                            </td>
                            <td><span style="background: #f1f5f9; padding: 4px 8px; border-radius: 6px; font-size: 0.85em; font-family: monospace;"><?= htmlspecialchars($i['symbole']) ?></span></td>
                            <td style="text-transform: capitalize;"><?= htmlspecialchars($i['type']) ?></td>
                            <td><?= number_format($i['quantity'], 2, ',', ' ') ?></td>
                            <td style="font-weight: 700;"><?= number_format($i['val_now'], 2, ',', ' ') ?></td>
                            <td>
                                <span style="background: <?= $i['p_change'] >= 0 ? '#dcfce7' : '#fee2e2' ?>; color: <?= $i['p_change'] >= 0 ? '#166534' : '#991b1b' ?>; padding: 4px 8px; border-radius: 6px; font-size: 0.9em; font-weight: 600;">
                                    <?= $i['p_change'] > 0 ? '+' : '' ?><?= $i['p_change'] ?>%
                                </span>
                            </td>
                            <td style="padding-right: 30px;">
                                <a href="/portfolio/<?= $portfolio_id ?>/instrument/<?= $i['isin'] ?>">Voir</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>