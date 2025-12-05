<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/db.php';

use GuzzleHttp\Exception\GuzzleException;
use Scheb\YahooFinanceApi\ApiClient;
use Scheb\YahooFinanceApi\ApiClientFactory;
use Scheb\YahooFinanceApi\Exception\ApiException;

$database = Database::instance();

$timeRange = $_GET["range"];

if ($timeRange === null) {
    $timeRange = "-7 days";
}

if ($type === "portfolio") {
    // Nouveau plan:
    // Récupérer toutes les currencies différentes contenues dans le portfolio
    // Pour chaque couple portfolio_currency/instrument_currency récupérer le taux de change
    // Pour chaque couple, récupérer les données et appliquer le taux (directement dans le sql?)

    // Pourquoi ne pas extraire le 'CASE' ? => Performance de la requête

    $portfolio_currency = $database->execute("SELECT p.code_devise as devise FROM Portfolio p WHERE p.id = ?", [$isin])->fetch();

    $currencies = $database->execute("SELECT DISTINCT(i.code_devise) as devise FROM Transaction t
        LEFT JOIN Instrument_Financier i ON i.isin = t.isin
        WHERE t.id_portfolio = ?", [$isin])->fetchAll();

    $raw_data = [];

    foreach ($currencies as $currency) {
        // récupérer les données ou la currency est =
        $stmt = "SELECT c.date,
        SUM(c.valeur_fermeture * CASE
                WHEN t.type = 'achat' THEN t.quantite 
                WHEN t.type = 'vente' THEN -t.quantite 
                ELSE 0
            END
            ) as valeur_fermeture 
        FROM (
            SELECT DISTINCT(i.isin) as isin
            FROM Portfolio p 
            LEFT JOIN `Transaction` t on t.id_portfolio = p.id
            LEFT JOIN Instrument_Financier i on i.isin = t.isin AND i.code_devise = ?
            WHERE p.id = ?
            ) as f
        LEFT JOIN Cours c on c.isin = f.isin
        LEFT JOIN `Transaction` t on t.isin = f.isin AND c.date >= t.date
        WHERE c.date >= ?
        GROUP BY
        c.date
        ORDER BY 
        c.date ASC";

        $data = $database->execute($stmt, [$currency["devise"], $isin, date("Y-m-d", (new DateTime($timeRange))->getTimestamp())])->fetchAll();

        if ($currency["devise"] != $portfolio_currency["devise"]) {
            // TODO
            // récupérer la conversion
            // appliquer la conversion
        }

        foreach ($data as $day) {
            $date = $day["date"];
            if (!array_key_exists($date, $raw_data)) {
                $raw_data[$date] = 0.0;
            }
            $val = floatval($day["valeur_fermeture"]);
            $raw_data[$date] += $val;
        }
    }

    $data = [];

    foreach (array_keys($raw_data) as $jour) {
        $date = DateTime::createFromFormat("Y-m-d", $jour)->getTimestamp();

        $value = [
            "x" => $date * 1000,
            "y" => round($raw_data[$jour], 2)
        ];

        $data[] = $value;
    }

    $json = json_encode($data, JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT);

    echo $json;
} else if ($type === "cours") {
    $instrument = $database->execute("SELECT * FROM Instrument_Financier WHERE isin=?", [$isin])->fetch();

    if (!$instrument) {
        echo "Cet instrument n'existe pas";
        die;
    }

    $stmt = "SELECT * FROM Cours c WHERE c.isin=? AND c.date>=?";
    $stmt = $database->execute($stmt, [$isin, date("Y-m-d", (new DateTime($timeRange))->getTimestamp())]);

    $raw_data = $stmt->fetchAll();
    $data = [];

    foreach ($raw_data as $jour) {
        $date = DateTime::createFromFormat("Y-m-d", $jour['date'])->getTimestamp();

        $value = [
            "x" => $date * 1000,
            "o" => round(floatval($jour['valeur_ouverture']), 2),
            "h" => round(floatval($jour['valeur_maximale']), 2),
            "l" => round(floatval($jour['valeur_minimale']), 2),
            "c" => round(floatval($jour['valeur_fermeture']), 2)
        ];

        $data[] = $value;
    }

    $json = json_encode($data, JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT);

    echo $json;
}