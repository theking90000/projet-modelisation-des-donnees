<?php
require_once __DIR__ . '/../lib/affichage_table.php';

class AffichageInstruments extends AffichageTable {
    protected function parse(array $data): array {
        $out = [];

        $out = $this->check($out, $data, "nom", function ($v) {
            if(empty($v)) return "Le nom est requis";
        });

        $out = $this->check_select($out, $data, "type", ["action", "etf", "obligation", "devise"], "action");

        $type = $out["type"]["value"];

        if($type === "action") {
            $out = $this->check_transform($out, $data, "entreprise", function ($v) {
                if(empty($v)) {
                    return ["Une entreprise est requise.", null];
                }

                if(strlen($v) < 3) {
                    return ["ID entreprise invalide.", null];
                }

                $code_pays = substr($v, 0, 2);
                $numero = substr($v, 2);

                $stmt = Database::instance()->execute("SELECT nom, code_pays, numero FROM Entreprise WHERE code_pays = ? AND numero = ?", [$code_pays, $numero]);

                $entreprise = $stmt->fetch();

                if(!$entreprise) {
                    return ["Entreprise inconnue.", null];
                } else {
                    return [null, $entreprise];
                }
            });
        }
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

        $stmt = Database::instance()->prepare("SELECT COUNT(*) AS count FROM Instrument_Financier ".$s[0]. "");
        
        if(isset($s[1])) {
            $stmt->bindValue(":recherche", $s[1]);
        }

        $stmt->execute();

        return $stmt->fetch()['count'];
    }

    protected function search(array $searchParams, int $limit, int $offset): PDOStatement {
        $s = $this->sql_recherche($searchParams);

        $stmt = Database::instance()->prepare("SELECT Instrument_Financier.* FROM Instrument_Financier ".$s[0]. " LIMIT :limit OFFSET :offset");
        
        if(isset($s[1])) {
            $stmt->bindValue(":recherche", $s[1]);
        }

        $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindValue(":offset", $offset, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt;
    }

    protected function insert(array $data): array {
        $row = [
            "numero"=>$data["numero"]["value"], 
            "code_pays"=>$data["pays"]["value"]["code"], 
            "nom"=>$data["nom"]["value"],
            "secteur"=>$data["secteur"]["value"]
        ];

        Database::instance()->execute("INSERT INTO Entreprise (numero, code_pays, nom, secteur) VALUES (:numero, :code_pays, :nom, :secteur);",
                $row);
                

        return $row;
    }

    protected function render_row(array $row) {
        echo "<td>". htmlspecialchars($row["symbole"]) . "</td>\n";
        echo "<td>". htmlspecialchars($row["nom"]) . "</td>\n";
        echo "<td>". htmlspecialchars($row["isin"]) . "</td>\n";
    }

    protected function row_id(array $row) {
        return $row["isin"];
    }

    protected function row_label(array $row) {
        return $row["nom"];
    }

    protected function get_names(): array {
        return [
            "Recherche un instrument financier",
            "Ajouter un instrument financier"
        ];
    }

    protected function get_url(): string {
        return "/portfolio"."/".addslashes($this->args["portfolio_id"])."/instruments";
    }

    protected function form(array $data) {
        $this->print_input("nom", "Nom", $data);

        $this->print_select("type", ["action", "etf", "obligation", "devise"],["Action", "ETF", "Obligation", "Devise"], $data);

        $this->print_if("type", ["action"], $data, function () use ($data) {
            $this->print_ext_select("entreprise", "Selectionner entreprise", "/portfolio"."/".$this->args["portfolio_id"]."/entreprises",
            function ($v) { return $v["code_pays"].$v["numero"]; },
            function ($v) { return $v["nom"]; },
            $data);
        });
    }
}

$affichage = new AffichageInstruments(["portfolio_id"=>$portfolio_id, "title"=>"Entreprises"]);

$affichage->render();
?>
