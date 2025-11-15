<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/db.php';

use GuzzleHttp\Exception\GuzzleException;
use Scheb\YahooFinanceApi\ApiClient;
use Scheb\YahooFinanceApi\ApiClientFactory;
use Scheb\YahooFinanceApi\Exception\ApiException;

$database = Database::instance();

$stmt = "SELECT * FROM Instrument_Financier WHERE isin=:isin";

$stmt = $database->execute($stmt, ["isin" => $isin]);

$instrument = $stmt->fetch();

if (!$instrument) {
    echo "Cet instrument n'existe pas";
    die;
}

$stmt = "SELECT * FROM Cours WHERE isin=:isin";
$stmt = $database->execute($stmt, ["isin" => $isin]);

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
?>
<script src="https://cdn.jsdelivr.net/npm/luxon@3.4.4"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-luxon@1.3.1"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-chart-financial"></script>

<div style="height:100vh; display: flex; align-items: center; justify-content: center">
    <canvas id="chart" width="600" height="300" style="max-width: 600px; max-height:300px;"></canvas>
</div>


<script>
    const ctx = document.getElementById("chart").getContext("2d");

    new Chart(ctx, {
        type: "candlestick",
        data: {
            datasets: [{
                label: "<?= $instrument['nom'] ?>",
                data: <?= $json ?>,
            }]
        },
        options: {
            parsing: false,
            scales: {
                x: {type: "time", time: {unit: "day"}},
                y: {
                    ticks: {
                        callback: value => value.toFixed(2) + " €"
                    }
                },
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: ctx => {
                            const ohlc = ctx.raw;
                            return [
                                `Ouverture: ${ohlc.o} €`,
                                `Maximum: ${ohlc.h} €`,
                                `Minimum: ${ohlc.l} €`,
                                `Fermeture: ${ohlc.c} €`,
                            ];
                        }
                    }
                }
            }
        }
    });
</script>