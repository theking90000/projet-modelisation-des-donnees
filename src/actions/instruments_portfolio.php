<?php
require_once __DIR__ . '/../lib/affichage_table.php';

class AffichageInstruments extends AffichageTable {
    protected function parse(array $data): array {
        $out = [];

        $out = $this->check($out, $data, "isin", function ($v) {
            if(empty($v)) return "Le ISIN est requis";
            if(strlen($v) !== 12) return "Le isin doit faire 12 caractères";
        });

        $out = $this->check($out, $data, "nom", function ($v) {
            if(empty($v)) return "Le nom est requis";
        });

        $out = $this->check($out, $data, "symbole", function ($v) {
            if(empty($v)) return "Le symbole est requis";
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

            $out = $this->check_transform($out, $data, "bourse", function ($v) {
                if(empty($v)) {
                    return ["Une bourse d'échange est requise.", null];
                }

                $stmt = Database::instance()->execute("SELECT id, ville FROM Bourse WHERE id = ?", [$v]);

                $bourse = $stmt->fetch();

                if(!$bourse) {
                    return ["Bourse inconnue.", null];
                } else {
                    return [null, $bourse];
                }
            });
        }

        $out = $this->check_transform($out, $data, "devise_echange", function ($v) {
                if(empty($v)) {
                    return ["Une devise d'échange est requise.", null];
                }

                $stmt = Database::instance()->execute("SELECT code, nom, symbole FROM Devise WHERE code = ?", [$v]);

                $devise = $stmt->fetch();

                if(!$devise) {
                    return ["Devise inconnue.", null];
                } else {
                    return [null, $devise];
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

    private function to_row(array $data): array {
        return [
            "isin"=>$data["isin"]["value"], 
            "symbole"=>$data["symbole"]["value"],
            "nom"=>$data["nom"]["value"],
            "type"=>$data["type"]["value"],

            "numero_entreprise"=>$data["entreprise"]["value"]["numero"] ?? null,
            "pays_entreprise"=>$data["entreprise"]["value"]["code_pays"] ?? null,
            "id_bourse"=>$data["bourse"]["value"]["id"] ?? null,
            "code_devise"=>$data["devise_echange"]["value"]["code"] ?? null
        ];
    }

    protected function insert(array $data): array {
        $row = $this->to_row($data);

        $stmt = Database::instance()->prepare("INSERT INTO Instrument_Financier (isin, symbole, nom, type, numero_entreprise, pays_entreprise, id_bourse, code_devise) VALUES (?, ?, ?, ?, ?, ?, ?, ?);");

        $stmt->execute(array_values($row));
                

        return $row;
    }

    protected function render_row(array $row) {
        echo "<td>". htmlspecialchars($row["symbole"]) . "</td>\n";
        echo "<td>". htmlspecialchars($row["nom"]) . "</td>\n";
        echo "<td>". htmlspecialchars($row["isin"]) . "</td>\n";
        echo "<td><a href='/cours/". $row["isin"] ."'>Voir le Cours</a></td>";
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
        $this->print_input("isin", "ISIN", $data);

        $this->print_input("symbole", "Symbole", $data);

        $this->print_input("nom", "Nom", $data);

        $this->print_select("type", ["action", "etf", "obligation", "devise"],["Action", "ETF", "Obligation", "Devise"], $data);

        $this->print_if("type", ["action"], $data, function () use ($data) {
            $this->print_ext_select("entreprise", "Selectionner entreprise", "/portfolio"."/".$this->args["portfolio_id"]."/entreprises",
            function ($v) { return $v["code_pays"].$v["numero"]; },
            function ($v) { return $v["nom"]; },
            $data);

            $this->print_ext_select("bourse", "Selectionner une bourse", "/portfolio"."/".$this->args["portfolio_id"]."/bourses",
            function ($v) { return $v["id"]; },
            function ($v) { return $v["id"]."(".$v["ville"].")"; },
            $data);
        });

        $this->print_ext_select("devise_echange", "Devise d'échange", "/portfolio"."/".$this->args["portfolio_id"]."/devises",
            function ($v) { return $v["code"]; },
            function ($v) { return $v["code"].'('.$v["symbole"].')'; },
            $data);
    }

    protected function get(string $id): array {
        return Database::instance()
            ->execute("SELECT isin, symbole, nom, type, CONCAT(pays_entreprise, numero_entreprise) as entreprise, id_bourse AS bourse, code_devise AS devise_echange FROM Instrument_Financier WHERE isin = ?", [$id])
            ->fetch();
    }
    
    protected function update(string $id, array $data) {
        $row = $this->to_row($data);

        $row["isin"] = $id;

        return Database::instance()
            ->execute("UPDATE Instrument_Financier SET symbole = :symbole, nom = :nom, `type` = :type, numero_entreprise = :numero_entreprise, pays_entreprise = :pays_entreprise, id_bourse =:id_bourse, code_devise = :code_devise WHERE isin = :isin", $row);
    }
}

$acces = acces_portfolio($portfolio_id);

$affichage = new AffichageInstruments(["portfolio_id"=>$portfolio_id, "title"=>"Entreprises"], 10,
                                      $acces>=2, $acces>=2, $acces>=2);

$affichage->render();
?>
