<?php
    require_once __DIR__ . '/../template/utils.php';

    // FIX: Decode the ID from the URL
    $bourse_id = urldecode($bourse_id);

    // 1. Fetch Bourse Details
    $bourse = Database::instance()->execute(
        "SELECT b.*, p.nom as nom_pays 
         FROM Bourse b
         LEFT JOIN Pays p ON b.code_pays = p.code
         WHERE b.id = ?", 
        [$bourse_id]
    )->fetch();

    if(!$bourse) {
        echo "<div class='section'><h3>Erreur : Bourse '$bourse_id' introuvable</h3></div>";
        die();
    }

    // 2. Fetch Instruments
    $instruments = Database::instance()->execute("
        WITH 
        Actifs AS (
            SELECT DISTINCT t.isin 
            FROM `Transaction` t
            JOIN Instrument_Financier i ON t.isin = i.isin
            WHERE t.id_portfolio = ? AND i.id_bourse = ?
        ),
        ClassementCours AS (
            SELECT c.isin, c.valeur_fermeture, ROW_NUMBER() OVER(PARTITION BY c.isin ORDER BY c.date DESC) as rang
            FROM Cours c JOIN Actifs a ON c.isin = a.isin
            WHERE c.date >= DATE_SUB(CURDATE(), INTERVAL 15 DAY)
        ),
        Holdings AS (
            SELECT t.isin, SUM(CASE WHEN t.type = 'achat' THEN t.quantite WHEN t.type = 'vente' THEN -t.quantite ELSE 0 END) AS quantity
            FROM `Transaction` t WHERE t.id_portfolio = ? GROUP BY t.isin HAVING quantity > 0
        )
        SELECT 
            i.nom, i.isin, i.symbole, i.type, h.quantity,
            ROUND(h.quantity * p1.valeur_fermeture, 2) as val_now,
            ROUND(((p1.valeur_fermeture - p2.valeur_fermeture) / NULLIF(p2.valeur_fermeture, 0)) * 100, 2) AS p_change
        FROM Holdings h
        JOIN Instrument_Financier i ON h.isin = i.isin
        LEFT JOIN ClassementCours p1 ON h.isin = p1.isin AND p1.rang = 1
        LEFT JOIN ClassementCours p2 ON h.isin = p2.isin AND p2.rang = 2
        WHERE i.id_bourse = ?
        ORDER BY val_now DESC
    ", [$portfolio_id, $bourse_id, $portfolio_id, $bourse_id])->fetchAll();
    
    // Get Currency
    $pf_devise = Database::instance()->execute("SELECT d.symbole FROM Portfolio p JOIN Devise d ON p.code_devise = d.code WHERE p.id = ?", [$portfolio_id])->fetch()['symbole'];
    $open = new DateTime($bourse['heure_ouverture']);
    $close = new DateTime($bourse['heure_fermeture']);
?>

<?= print_portfolio_header_back($portfolio_id, $bourse['nom']) ?>

<div class="portfolio-main">
    
    <div class="section">
        <div class="card" style="display: flex; justify-content: space-between; align-items: center; padding: 40px; flex-wrap: wrap; gap: 30px; max-width: 1100px; margin: 0 auto; width: 100%;">
            
            <div style="flex: 1; min-width: 280px;">
                <div style="font-size: 2.5em; font-weight: 800; color: var(--primary-color); line-height: 1.1;">
                    <?= htmlspecialchars($bourse['id']) ?>
                </div>
                <div style="font-size: 1.2em; color: #64748b; margin-top: 15px;">
                     <?= htmlspecialchars($bourse['ville']) ?>, <?= htmlspecialchars($bourse['nom_pays']) ?>
                </div>
            </div>

            <div style="display: flex; gap: 50px; background: #f8fafc; padding: 25px 40px; border-radius: 12px;">
                
                <div>
                    <div style="font-size: 0.8em; color: #94a3b8; text-transform: uppercase; font-weight: 700; margin-bottom: 6px;">Horaires</div>
                    <div style="font-weight: 600; font-size: 1.2em; color: #334155;">
                        <?= $open->format('H:i') ?> - <?= $close->format('H:i') ?>
                    </div>
                </div>
                
                <div>
                     <div style="font-size: 0.8em; color: #94a3b8; text-transform: uppercase; font-weight: 700; margin-bottom: 6px;">Zone</div>
                    <div style="font-weight: 600; font-size: 1.2em; color: #334155;">
                        <?= htmlspecialchars($bourse['fuseau_horaire']) ?>
                    </div>
                </div>

                <div>
                     <div style="font-size: 0.8em; color: #94a3b8; text-transform: uppercase; font-weight: 700; margin-bottom: 6px;">Actifs</div>
                    <div style="font-weight: 700; font-size: 1.2em; color: var(--secondary-color-violet);">
                        <?= count($instruments) ?>
                    </div>
                </div>

            </div>

        </div>
    </div>

    <div class="section">
        <h3 style="max-width: 1100px; margin: 20px auto 10px auto;">Vos actifs sur <?= htmlspecialchars($bourse['nom']) ?></h3>
        
        <?php if(empty($instruments)): ?>
            <div class="card" style="text-align: center; color: #64748b; padding: 40px; max-width: 1100px; margin: 0 auto; width: 100%;">
                <p>Aucun instrument de ce portfolio n'est listé sur cette place boursière.</p>
            </div>
        <?php else: ?>
            <div class="card" style="padding: 0; overflow: hidden; margin-top: 15px; max-width: 1100px; margin: 0 auto; width: 100%;">
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
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding-left: 30px; font-weight: 600; color: #0f172a;">
                                <?= htmlspecialchars($i['nom']) ?>
                            </td>
                            <td><span style="background: #f1f5f9; padding: 4px 8px; border-radius: 6px; font-size: 0.85em; font-family: monospace; color: #475569;"><?= htmlspecialchars($i['symbole']) ?></span></td>
                            <td style="text-transform: capitalize; color: #64748b;"><?= htmlspecialchars($i['type']) ?></td>
                            <td><?= number_format($i['quantity'], 2, ',', ' ') ?></td>
                            <td style="font-weight: 700; color: #0f172a;"><?= number_format($i['val_now'], 2, ',', ' ') ?></td>
                            <td>
                                <span style="background: <?= $i['p_change'] >= 0 ? '#dcfce7' : '#fee2e2' ?>; color: <?= $i['p_change'] >= 0 ? '#166534' : '#991b1b' ?>; padding: 4px 8px; border-radius: 6px; font-size: 0.9em; font-weight: 600;">
                                    <?= $i['p_change'] > 0 ? '+' : '' ?><?= $i['p_change'] ?>%
                                </span>
                            </td>
                            <td style="padding-right: 30px;">
                                <a href="/portfolio/<?= $portfolio_id ?>/instrument/<?= $i['isin'] ?>" style="color: var(--primary-color); font-weight: 600; text-decoration: none;">Voir</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>