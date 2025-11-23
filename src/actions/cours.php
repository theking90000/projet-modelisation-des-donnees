<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/db.php';

use GuzzleHttp\Exception\GuzzleException;
use Scheb\YahooFinanceApi\ApiClient;
use Scheb\YahooFinanceApi\ApiClientFactory;
use Scheb\YahooFinanceApi\Exception\ApiException;

function encode_data($raw_data) {

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

$database = Database::instance();

$timeRange = $_GET["range"];

if ($timeRange === null) {
    $timeRange = "-7 days";
}

if ($type === "portfolio") {
    // Cette requête permet de récupérer la somme des cours de tous les instruments d'un portfolio
    // pour chaque jour dans une période donnée.
    // Il reste une étape qu'il faudra inclure dans la requête, c'est la conversion de devise.µ
    // Comment la faire ? Très bonne question.

    // Pourquoi ne pas extraire le 'CASE' ? => Performance de la requête
    $stmt = "SELECT c.date,
        SUM(c.valeur_ouverture * CASE
            WHEN t.type = 'achat' THEN t.quantite 
            WHEN t.type = 'vente' THEN -t.quantite 
            ELSE 0
        END
        ) as valeur_ouverture,
        SUM(c.valeur_fermeture * CASE
            WHEN t.type = 'achat' THEN t.quantite 
            WHEN t.type = 'vente' THEN -t.quantite 
            ELSE 0
        END
        ) as valeur_fermeture,
        SUM(c.valeur_maximale * CASE
            WHEN t.type = 'achat' THEN t.quantite 
            WHEN t.type = 'vente' THEN -t.quantite 
            ELSE 0
        END
        ) as valeur_maximale,
        SUM(c.valeur_minimale * CASE
            WHEN t.type = 'achat' THEN t.quantite 
            WHEN t.type = 'vente' THEN -t.quantite 
            ELSE 0
        END
        ) as valeur_minimale
        FROM (
        SELECT DISTINCT(i.isin) as isin
        FROM Portfolio p 
        LEFT JOIN `Transaction` t on t.id_portfolio = p.id
        LEFT JOIN Instrument_Financier i on i.isin = t.isin
        WHERE p.id = ?
        ) as f
        LEFT JOIN Cours c on c.isin = f.isin
        LEFT JOIN `Transaction` t on t.isin = f.isin AND c.date >= t.date
        WHERE c.date >= ?
        GROUP BY
        c.date
        ORDER BY 
        c.date ASC";

    $raw_data = $database->execute($stmt, [$isin, date("Y-m-d", (new DateTime($timeRange))->getTimestamp())])->fetchAll();
    encode_data($raw_data);
} else if ($type === "cours") {
    $instrument = $database->execute("SELECT * FROM Instrument_Financier WHERE isin=?", [$isin])->fetch();

    if (!$instrument) {
        echo "Cet instrument n'existe pas";
        die;
    }

    $stmt = "SELECT * FROM Cours c WHERE c.isin=? AND c.date>=?";
    $stmt = $database->execute($stmt, [$isin, date("Y-m-d", (new DateTime($timeRange))->getTimestamp())]);

    $raw_data = $stmt->fetchAll();
    encode_data($raw_data);
}