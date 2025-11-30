<?php
require_once __DIR__ . '/../lib/affichage_table.php';


class AffichageTransaction extends AffichageTable {
    public string $devisePortfolio;

    protected function parse(array $data): array {
        $out = [];

        $out = $this->check($out, $data, "quantite", function ($v) {
            if (!preg_match('/^-?\d+(\.\d+)?$/', $v)) {
                return "La quantité doit être un nombre";
            }
        });

        $out = $this->check($out, $data, "valeur_devise_portfolio", function ($v) {
            if (!preg_match('/^-?\d+(\.\d+)?$/', $v)) {
                return "La valeur doit être un nombre";
            }
        });

        $out = $this->check($out, $data, "taxes", function ($v) {
            if (!preg_match('/^-?\d+(\.\d+)?$/', $v)) {
                return "La taxe doit être un nombre";
            }
        });

        $out = $this->check($out, $data, "frais", function ($v) {
            if (!preg_match('/^-?\d+(\.\d+)?$/', $v)) {
                return "Les frais doit être un nombre";
            }
        });

        $out = $this->check_select($out, $data, "type", ["achat", "vente"], "achat");

        // $type = $out["type"]["value"];

        $out = $this->check_transform($out, $data, "instrument", function ($v) {
            if(empty($v)) {
                if (!isset($_GET['instrument'])) {
                    return ["Un instrument financier est requis.", null];
                }
                $v = $_GET['instrument'];
            }

            $stmt = Database::instance()->execute("SELECT Instrument_Financier.* FROM Instrument_Financier WHERE isin = ?", [$v]);

            $instrument = $stmt->fetch();

            if(!$instrument) {
                return ["Instrument inconnue.", null];
            } else {
                return [null, $instrument];
            }
        });
        
        $out = $this->check($out, $data, "date", function ($v) {
            if (!isset($v) || empty($v)) {
                return "La date doit être définie";
            }
        });

        $out = $this->check($out, $data, "heure", function ($v) {
            if (!isset($v) || empty($v)) {
                return "L'heure doit être définie";
            }
        });

        return $out;
    }

    private function sql_recherche(array $searchParams): array {
        $recherche = isset($searchParams['recherche']) ? $searchParams['recherche'] : null;

        if(isset($recherche)) {
            return ["WHERE LOWER(Instrument_Financier.nom) LIKE CONCAT('%', :recherche,'%')", strtolower($_GET["recherche"])];
        } 
        
        return ["", null];
    }

    protected function count(array $searchParams): int {
        $s = $this->sql_recherche($searchParams);

        $stmt = Database::instance()->prepare("SELECT COUNT(*) AS count FROM `Transaction` JOIN Instrument_Financier ON Instrument_Financier.isin = `Transaction`.isin ".$s[0]. "");
        
        if(isset($s[1])) {
            $stmt->bindValue(":recherche", $s[1]);
        }

        $stmt->execute();

        return $stmt->fetch()['count'];
    }

    protected function search(array $searchParams, int $limit, int $offset): PDOStatement {
        $s = $this->sql_recherche($searchParams);

        $stmt = Database::instance()->prepare("SELECT Transaction.* FROM `Transaction` JOIN Instrument_Financier ON Instrument_Financier.isin = `Transaction`.isin ".$s[0]. " LIMIT :limit OFFSET :offset");
        
        if(isset($s[1])) {
            $stmt->bindValue(":recherche", $s[1]);
        }

        $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindValue(":offset", $offset, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt;
    }

    protected function insert(array $data): array {
        if(!isset($this->args["portfolio_id"]))
            throw new Exception("Manque id portfolio");

        $row = [
            "id_portfolio"=>$this->args["portfolio_id"],
            "isin"=>$data["instrument"]["value"]["isin"],

            "email_utilisateur"=>Auth::user(),

            "type"=>$data["type"]["value"],
            "date"=>$data["date"]["value"],
            "heure"=>$data["heure"]["value"],

            "quantite"=>$data["quantite"]["value"],
            "valeur_devise_portfolio"=>$data["valeur_devise_portfolio"]["value"],

            "taxes"=>$data["taxes"]["value"],
            "frais"=>$data["frais"]["value"],
        ];

        // TODO: calculer "valeur_devise_instrument" avec le Yahoo finance API??

        Database::instance()->execute("INSERT INTO `Transaction` (id_portfolio, isin, email_utilisateur,`type`, `date`, heure, quantite, valeur_devise_portfolio, taxes, frais) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", array_values($row));

      //  Database::instance()->execute("INSERT INTO Entreprise (numero, code_pays, nom, secteur) VALUES (:numero, :code_pays, :nom, :secteur);",
   //             $row);
                

        return $row;
    }

    protected function render_row(array $row) {
        echo "<td>". htmlspecialchars($row["isin"]) . "</td>\n";
        echo "<td>". htmlspecialchars($row["type"]) . "</td>\n";
        echo "<td>". htmlspecialchars($row["quantite"]) . "</td>\n";
    }

    protected function row_id(array $row) {
        return $row["isin"];
    }

    protected function row_label(array $row) {
        return $row["isin"];
    }

    protected function get_names(): array {
        return [
            "Recherche une transaction",
            "Ajouter une transaction"
        ];
    }

    protected function get_url(): string {
        return "/portfolio"."/".addslashes($this->args["portfolio_id"])."/ajout-transaction";
    }

    protected function form(array $data) {
        $devise = htmlspecialchars($this->devisePortfolio);
        
        $this->print_ext_select("instrument", "Selectionner instrument", "/portfolio"."/".$this->args["portfolio_id"]."/instruments",
        function ($v) { return $v["isin"]; },
        function ($v) { return $v["nom"]; },
        $data);

        $this->print_label("type", "Type :");
        $this->print_select("type", ["achat", "vente"],["Achat", "Vente"], $data);
            
        $this->print_label("quantite", "Quantité :");
        $this->print_input("quantite", "Quantité", $data);

        $this->print_label("valeur_devise_portfolio", "Valeur (".$devise.") au moment de la transaction:");
        $this->print_input("valeur_devise_portfolio", "Valeur (".$devise.")", $data, "number");

        $this->print_label("date", "Date :");
        $this->print_input("date", "Date", $data, "date");

        $this->print_label("heure", "Heure :");
        $this->print_input("heure", "Heure", $data, "time");

        $this->print_label("taxes", "Taxes (".$devise.") :");
        $this->print_input("taxes", "Taxes (".$devise.")", $data, "number");
        $this->print_label("frais", "Frais transaction (".$devise.") :");
        $this->print_input("frais", "Frais transactions (".$devise.")", $data, "number");
    }
}

$acces = acces_portfolio($portfolio_id);

$affichage = new AffichageTransaction(["portfolio_id"=>$portfolio_id, "title"=>"Entreprises"], 10,
                                      $acces>=2, $acces>=2, $acces>=2);

$affichage->devisePortfolio = Database::instance()->execute(
    "SELECT d.symbole FROM Portfolio p JOIN Devise d ON p.code_devise = d.code WHERE p.id = ?"
, [$portfolio_id])->fetch()["symbole"];

$affichage->render();
?>
