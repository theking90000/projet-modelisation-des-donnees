<?php

require_once __DIR__ . '/../lib/affichage_table.php';

class AffichagePays extends AffichageTable {
    protected function parse(array $data): array {
        $out = [];

        $out = $this->check($out, $data, "nom", function ($v) {
            if(empty($v)) return "Le nom est requis";
        });

        $out = $this->check($out, $data, "code", function ($v) {
            if(empty($v)) return "Le code du pays est requis";
            if(strlen($v) !== 2) return  "Le code du pays doit faire exactement 2 caractères";
        });

        return $out;
    }

    private function sql_recherche(array $searchParams): array {
        $recherche = isset($searchParams['recherche']) ? $searchParams['recherche'] : null;

        if(isset($recherche)) {
            return ["WHERE LOWER(nom) LIKE CONCAT('%', :recherche,'%')", strtolower($_GET["recherche"])];
        } 
        
        return ["", null];
    }

    protected function count(array $searchParams): int {
        $s = $this->sql_recherche($searchParams);

        $stmt = Database::instance()->prepare("SELECT COUNT(*) AS count FROM Pays ".$s[0]. "");
        
        if(isset($s[1])) {
            $stmt->bindValue(":recherche", $s[1]);
        }

        $stmt->execute();

        return $stmt->fetch()['count'];
    }

    protected function search(array $searchParams, int $limit, int $offset): PDOStatement {
        $s = $this->sql_recherche($searchParams);

        $stmt = Database::instance()->prepare("SELECT Pays.code, Pays.nom FROM Pays ".$s[0]. " LIMIT :limit OFFSET :offset");
        
        if(isset($s[1])) {
            $stmt->bindValue(":recherche", $s[1]);
        }

        $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindValue(":offset", $offset, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt;
    }

    protected function insert(array $data): array {
        Database::instance()->execute("INSERT INTO Pays (code, nom) VALUES (?, ?)", [$data["code"]["value"], $data["nom"]["value"]]);

        return [
            "code"=>$data["code"]["value"],
            "nom"=>$data["nom"]["value"]
        ];
    }

    protected function render_row(array $row) {
        echo "<td>". htmlspecialchars($row["code"]) . "</td>";
        echo "<td>". htmlspecialchars($row["nom"]) . "</td>"; 
    }

    protected function row_id(array $row) {
        return $row["code"];
    }

    protected function row_label(array $row) {
        return $row["nom"];
    }

    protected function get_names(): array {
        return [
            "Recherche un pays",
            "Ajouter un pays"
        ];
    }

    protected function get_url(): string {
        return "/portfolio"."/".addslashes($this->args["portfolio_id"])."/pays";
    }

    protected function form(array $data) {
        $this->print_input("code", "Code pays", $data);

        $this->print_input("nom", "Nom pays", $data);
    }
}

$acces = acces_portfolio($portfolio_id);

$affichage = new AffichagePays(["portfolio_id"=>$portfolio_id, "title"=>"Pays"],
                                10,$acces>=2, $acces>=2, $acces>=2);

$affichage->render();
