<?php
require_once __DIR__ . '/../lib/affichage_table.php';

class AffichageTransaction extends AffichageTable {
    protected function parse(array $data): array {
        $out = [];

        $out = $this->check($out, $data, "quantite", function ($v) {
            if (!preg_match('/^-?\d+(\.\d+)?$/', $v)) {
                return "La quantité doit être un nombre";
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
                return ["Un instrument financier est requis.", null];
            }

            $stmt = Database::instance()->execute("SELECT Instrument_Financier.* FROM Instrument_Financier WHERE isin = ?", [$v]);

            $instrument = $stmt->fetch();

            if(!$instrument) {
                return ["Instrument inconnue.", null];
            } else {
                return [null, $instrument];
            }
        });
        
        return $out;
    }

    private function sql_recherche(array $searchParams): array {
        $recherche = $searchParams['recherche'];

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
        /*$row = [
            "numero"=>$data["numero"]["value"], 
            "code_pays"=>$data["pays"]["value"]["code"], 
            "nom"=>$data["nom"]["value"],
            "secteur"=>$data["secteur"]["value"]
        ];*/

        throw new Exception("Pas implementé");

      //  Database::instance()->execute("INSERT INTO Entreprise (numero, code_pays, nom, secteur) VALUES (:numero, :code_pays, :nom, :secteur);",
   //             $row);
                

      //  return $row;
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
        $this->print_ext_select("instrument", "Selectionner instrument", "/portfolio"."/".$this->args["portfolio_id"]."/instruments",
        function ($v) { return $v["isin"]; },
        function ($v) { return $v["nom"]; },
        $data);
            
        $this->print_input("quantite", "Quantité", $data);

        $this->print_select("type", ["achat", "vente"],["Achat", "Vente"], $data);

        $this->print_input("taxes", "Taxes", $data);
        $this->print_input("frais", "Frais transactions", $data);
    }
}

$acces = acces_portfolio($portfolio_id);

$affichage = new AffichageTransaction(["portfolio_id"=>$portfolio_id, "title"=>"Entreprises"], 10,
                                      $acces>=2, $acces>=2, $acces>=2);

$affichage->render();
?>
