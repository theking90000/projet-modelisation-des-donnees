<?php
require_once __DIR__ . '/../lib/affichage_table.php';

class AffichageEntreprises extends AffichageTable {
    protected function parse(array $data): array {
        $out = [];

        $out = $this->check($out, $data, "nom", function ($v) {
            if(empty($v)) return "Le nom est requis";
        });

        $out = $this->check($out, $data, "secteur", function ($v) {
            if(empty($v)) return "Le secteur d'activité de l'entreprise est requis";
        });

        
        $out = $this->check($out, $data, "numero", function ($v) {
            if(empty($v)) return "Le numéro d'entreprise est requis";
        });

        $out = $this->check_transform($out, $data, "pays", function ($v) {
            if(empty($v)) {
                return ["Un pays est requis.", null];
            }

            if(strlen($v) != 2) {
                return ["Code pays invalide.", null];
            } 

            $stmt = Database::instance()->execute("SELECT code, nom FROM Pays WHERE Pays.code = ?", [$v]);

            $pays = $stmt->fetch();

            if(!$pays) {
                return ["Pays inconnu.", null];
            } else {
                return [null, $pays];
            }
        });

        return $out;
    }

    private function sql_recherche(array $searchParams): array {
        $recherche = $searchParams['recherche'];

        if(isset($recherche)) {
            return ["WHERE LOWER(Entreprise.nom) LIKE CONCAT('%', :recherche,'%')", strtolower($_GET["recherche"])];
        } 
        
        return ["", null];
    }

    protected function count(array $searchParams): int {
        $s = $this->sql_recherche($searchParams);

        $stmt = Database::instance()->prepare("SELECT COUNT(*) AS count FROM Entreprise ".$s[0]. "");
        
        if(isset($s[1])) {
            $stmt->bindValue(":recherche", $s[1]);
        }

        $stmt->execute();

        return $stmt->fetch()['count'];
    }

    protected function search(array $searchParams, int $limit, int $offset): PDOStatement {
        $s = $this->sql_recherche($searchParams);

        $stmt = Database::instance()->prepare("SELECT Entreprise.numero, Entreprise.code_pays, Entreprise.nom, Pays.nom AS nom_pays FROM Entreprise JOIN Pays ON Pays.code = Entreprise.code_pays ".$s[0]. " LIMIT :limit OFFSET :offset");
        
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
        echo "<td>". htmlspecialchars($row["nom"]) . "</td>\n";
        echo "<td>". htmlspecialchars($row["nom_pays"]) . "</td>\n";
        echo "<td>". htmlspecialchars($row["numero"]) . "</td>\n";
    }

    protected function row_id(array $row) {
        return $row["code_pays"].$row["numero"];
    }

    protected function row_label(array $row) {
        return $row["nom"];
    }

    protected function get_names(): array {
        return [
            "Recherche une entreprise",
            "Ajouter une entreprise"
        ];
    }

    protected function get_url(): string {
        return "/portfolio"."/".addslashes($this->args["portfolio_id"])."/entreprises";
    }

    protected function form(array $data) {
        $this->print_input("nom", "Nom", $data);

        $this->print_input("secteur", "Secteur", $data);

        $this->print_input("numero", "Numero", $data);

        $this->print_ext_select("pays", "Selectionner pays", "/portfolio"."/".$this->args["portfolio_id"]."/pays",
        function ($v) { return $v["code"]; },
        function ($v) { return $v["nom"]; },
        $data);
    }
}

$affichage = new AffichageEntreprises(["portfolio_id"=>$portfolio_id, "title"=>"Entreprises"]);

$affichage->render();
