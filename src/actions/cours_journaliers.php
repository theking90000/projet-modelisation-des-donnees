<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/db.php';

use GuzzleHttp\Exception\GuzzleException;
use Scheb\YahooFinanceApi\ApiClient;
use Scheb\YahooFinanceApi\ApiClientFactory;
use Scheb\YahooFinanceApi\Exception\ApiException;


$client = ApiClientFactory::createApiClient();

$database = Database::instance();

// Table servant comme Cache pour l'api YahooFinance.
// Si l'api ne peut pas trouver un des instruments financier, on sauvegarde ce fait
// par l'ajout d'une ligne liant l'isin avec un symbole 'MISS' pour éviter de refaire
// la rechercher sur un instrument en sachant que la recherche est déterministe.
// Si l'api retourne un résultat, sauvegarder le symbole qui sera alors utilisé pour
// faire les requêtes api pour récupérer l'historique.
$database->execute("CREATE TABLE IF NOT EXISTS YahooFinanceCache (isin CHAR(12) PRIMARY KEY REFERENCES Instrument_Financier.isin, symbol VARCHAR(50))");

// On regarde ici plusieurs choses:
// - Si l'instrument financier a déjà eu un fetch d'historique
//      Si non, 'date' sera NULL
//      Si oui, 'date' sera la dernière date de l'historique présente dans la table Cours
// - Si la recherche api a déjà été effectuée pour l'instrument.
//      Si oui on aura comme expliqué dessus 'MISS' ou le symbole.
//      Sinon, on a NULL.
$stmt = "SELECT DISTINCT Instrument_Financier.isin, symbole, nom, MAX(Cours.date) as date, YahooFinanceCache.symbol as yahoo_symbol FROM Instrument_Financier LEFT JOIN YahooFinanceCache ON YahooFinanceCache.isin = Instrument_Financier.isin LEFT JOIN Cours ON Cours.isin=Instrument_Financier.isin GROUP BY Instrument_Financier.isin";

$stmt = $database->execute($stmt);

$instruments = $stmt->fetchAll();

foreach ($instruments as $instrument) {
    $isin = $instrument['isin'];
    $symbole = $instrument['symbole'];
    $nom = $instrument['nom'];
    $date = $instrument['date'];

    $today = date("Y-m-d", (new DateTime("-1 day"))->getTimestamp());

    if (!$date) {
        $date = date("Y-m-d", (new DateTime($today." - 365 days"))->getTimestamp());
    }

    if ($today <= $date) {
        continue;
    }

    if (!$instrument['yahoo_symbol']) {
        // On effectue la recherche de telle manière que si:
        // - l'isin ne retourne pas de résultat on recherche avec le symbole
        // - le symbole ne retourne pas de résultat on recherche comme dernière
        //   mesure le nom
        // Si on a aucun résultat, le symbole devient 'MISS'
        // Dans tout les cas, on sauvegarde ça dans la table YahooFinanceCache
        foreach ([$isin, $symbole, $nom] as $search) {
            if ($search === null) {
                continue;
            }

            $result = $client->search($search, limit: 1);

            // Le premier résultat de recherche est celui sélectionné.
            if (!empty($result)) {
                break;
            }
        }
        if (empty($result)) {
            $instrument['yahoo_symbol'] = "MISS";
        } else {
            $instrument['yahoo_symbol'] = $result[0]->getSymbol();
        }
        $database->execute("INSERT INTO YahooFinanceCache (isin, symbol) VALUES (?, ?)", [$isin, $instrument['yahoo_symbol']]);
    }

    if ($instrument['yahoo_symbol'] == "MISS") {
        continue;
    }

    try {
        $history = $client->getHistoricalQuoteData($instrument['yahoo_symbol'], ApiClient::INTERVAL_1_DAY, new DateTime($date. " +1 day"), new DateTime('-1 day'));

        // Ne devrait théoriquement pas arriver
        if (empty($history)) {
            error_log("Aucun historique pour: " . $isin);
            continue;
        }

        // "Pour chaque jour de l'historique, on l'insère dans BDD"
        foreach ($history as $quote) {

            $stmt = "INSERT INTO Cours (isin, date, valeur_ouverture, valeur_fermeture, valeur_maximale, valeur_minimale, volume) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $values = [$isin,
                $quote->getDate()->format('Y-m-d'),
                $quote->getOpen(),
                $quote->getClose(),
                $quote->getHigh(),
                $quote->getLow(),
                $quote->getVolume()];

            $stmt = $database->execute($stmt, $values);
        }

    } catch (GuzzleException|ApiException|PDOException $e) {
        error_log("Erreur: " . $e->getMessage());
    }
}
