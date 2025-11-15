<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/db.php';

use GuzzleHttp\Exception\GuzzleException;
use Scheb\YahooFinanceApi\ApiClient;
use Scheb\YahooFinanceApi\ApiClientFactory;
use Scheb\YahooFinanceApi\Exception\ApiException;


$client = ApiClientFactory::createApiClient();

$database = Database::instance();

$stmt = "SELECT DISTINCT isin, symbole, nom FROM Instrument_Financier";
$stmt = $database->execute($stmt);

$instruments = $stmt->fetchAll();

foreach ($instruments as $instrument) {
    $isin = $instrument['isin'];
    $symbole = $instrument['symbole'];
    $nom = $instrument['nom'];

    try {

        // Cette librairie a besoin d'avoir le symbole pour pouvoir consulter l'historique de l'instrument.
        // En plus de cela, Yahoo Finance peut ne pas trouver un instrument depuis son isin. Mais il peut le trouver en
        // utilisant son nom.

        // On effectue alors une recherche sur le code isin, le symbole et le nom.
        // Le premier qui offre un résultat est conservé et ce résultat de recherche est utilisé après.

        // Nous pourrions éviter cette recherche en effectuant la recherche au moment où l'utilisateur encode un nouvel
        // instrument. L'utilisateur fait alors une "recherche" pour ajouter son instrument, qu'il peut ensuite
        // sélectionner dans une liste. De cette manière, les données encodées sont forcément cohérentes. Cette étape
        // ne serait alors plus nécéssaire.

        foreach ([$isin, $symbole, $nom] as $search) {
            if ($search === null) {
                continue;
            }

            $result = $client->search($search, 1);

            // Le premier résultat de recherche est celui sélectionné.
            if (!empty($result)) {
                break;
            }
        }

        if (empty($result)) {
            error_log("Aucun résultat pour: " . $isin);
            continue;
        }

        // Récupération, chaque jour, des données boursières de la veille.
        $history = $client->getHistoricalQuoteData($result[0]->getSymbol(), ApiClient::INTERVAL_1_DAY, new DateTime('-1 day'), new DateTime('-1 day'));

        if (empty($history)) {
            error_log("Aucun historique pour: " . $isin);
            continue;
        }

        $quote = $history[0];

        $stmt = "INSERT INTO Cours (isin, date, heure, valeur_ouverture, valeur_fermeture, valeur_maximale, valeur_minimale, volume) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $values = [$isin,
            $quote->getDate()->format('Y-m-d'),
            // Nous n'avons pas besoin de l'heure, la retirer de la base de donnée?
            $quote->getDate()->format('H:i:s'),
            $quote->getOpen(),
            $quote->getClose(),
            $quote->getHigh(),
            $quote->getLow(),
            $quote->getVolume()];

        $stmt = $database->execute($stmt, $values);

    } catch (GuzzleException|ApiException|PDOException $e) {
        error_log("Erreur: " . $e->getMessage());
    }
}
