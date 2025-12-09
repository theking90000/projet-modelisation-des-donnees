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

// $type correspond à l'attribut html "type" sur le canvas correspondant au graphique.
// 2 options sont prévues : portfolio et cours.
if ($type === "portfolio") {
    // Dans le cas d'un graphique sur le portfolio,
    // il y a plusieurs étapes à exécuter pour obtenir les données.

    // La première étape est de récupérer la devise du portfolio,
    // cela nous permet après de faire des conversions monétaires si nécessaire.
    $portfolio_currency = $database->execute("SELECT p.code_devise as devise FROM Portfolio p WHERE p.id = ?", [$isin])->fetch();

    // Si la devise n'existe pas, soit le portfolio n'existe pas ou alors la devise
    // est mal configurée (ce qui ne devrait à priori pas arriver).
    if (!$portfolio_currency) {
        echo "Ce portfolio n'existe pas.";
        die;
    }

    // L'étape d'après est de récupérer les devises de tous les instruments présents dans le portfolio.
    $currencies = $database->execute("SELECT DISTINCT(i.code_devise) as devise FROM Transaction t
        LEFT JOIN Instrument_Financier i ON i.isin = t.isin
        WHERE t.id_portfolio = ?", [$isin])->fetchAll();

    $raw_data = [];

    foreach ($currencies as $currency) {
        // Pour chaque devise, on récupère tous les cours cumulés des instruments de cette devise.
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
        LEFT JOIN `Transaction` t on t.isin = f.isin AND t.date <= c.date AND t.id_portfolio = ?
        WHERE c.date >= ?
        GROUP BY
        c.date
        ORDER BY 
        c.date ASC";

        $data = $database->execute($stmt, [$currency["devise"], $isin, $isin, date("Y-m-d", (new DateTime($timeRange))->getTimestamp())])->fetchAll();

        if ($currency["devise"] != $portfolio_currency["devise"]) {
            // Si la devise est différente de celle du portfolio, récupérer le cours d'échange monétaire
            // et appliquer la conversion aux données.

            // Problème, si le cours n'existe pas en base de données, comment gérer le cas ?
            // - Récupérer dynamiquement les données avec l'api ?
            // - Ajouter le cours dans la base de donnée et récupérer les données ?
            // - ... ?

            // TODO
        }

        foreach ($data as $day) {
            // Finalement, on cumule les données qui sont alors toutes dans la devise du portfolio.
            $date = $day["date"];
            if (!array_key_exists($date, $raw_data)) {
                $raw_data[$date] = 0.0;
            }
            $val = floatval($day["valeur_fermeture"]);
            $raw_data[$date] += $val;
        }
    }

    $data = [];

    // Transformation des données en json pour le graphique.
    ksort($raw_data);
    foreach (array_keys($raw_data) as $jour) {
        $date = DateTime::createFromFormat("Y-m-d", $jour, new DateTimeZone("UTC"))->setTime(0,0)->getTimestamp();

        $value = [
            "x" => $date * 1000,
            "y" => round($raw_data[$jour], 2)
        ];

        $data[] = $value;
    }

    $json = json_encode(["type" => "line", "data" => $data], JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT);

    echo $json;
} else if ($type === "cours") {
    // Dans le cas d'un graphique sur un instrument,
    // on récupère directement son cours via son isin.
    $stmt = "SELECT * FROM Cours c WHERE c.isin=? AND c.date>=?";
    $stmt = $database->execute($stmt, [$isin, date("Y-m-d", (new DateTime($timeRange))->getTimestamp())]);

    $raw_data = $stmt->fetchAll();
    // S'il n'y a aucune donnée, soit l'instrument n'existe pas, soit aucune donnée n'as encore été récupérée.
    if (!$raw_data) {
        echo json_encode([]);
        die;
    }

    $data = [];
    $type_instrument = $database->execute("SELECT i.type FROM Instrument_Financier i WHERE i.isin = ?", [$isin])->fetch()["type"];

    if ($type_instrument == "devise") {
        $type = "line";
    } else {
        $type = "candlestick";
    }

    // Transformation des données en json pour le graphique.
    foreach ($raw_data as $jour) {
        $date = DateTime::createFromFormat("Y-m-d", $jour['date'])->setTime(0,0)->getTimestamp();

        $o = round(floatval($jour['valeur_ouverture']), 2);
        $h = round(floatval($jour['valeur_maximale']), 2);
        $l = round(floatval($jour['valeur_minimale']), 2);
        $c = round(floatval($jour['valeur_fermeture']), 2);

        if ($type == "line") {
            $value = [
                "x" => $date * 1000,
                "y" => $c
            ];
        } else {
            $value = [
                "x" => $date * 1000,
                "o" => $o,
                "h" => $h,
                "l" => $l,
                "c" => $c
            ];
        }

        $data[] = $value;
    }

    $json = json_encode(["type" => $type, "data" => $data], JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT);

    echo $json;
}