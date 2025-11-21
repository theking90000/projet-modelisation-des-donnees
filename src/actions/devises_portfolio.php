<?php

require_once __DIR__ . '/../lib/affichage_table.php';

class AffichageDevise extends AffichageTable {
    protected function parse(array $data): array {
        $out = [];

        $out = $this->check($out, $data, "code", function ($v) {
            if(empty($v)) return "Le code est requis";
        });

        $out = $this->check($out, $data, "nom", function ($v) {
            if(empty($v)) return "Le nom est requis";
        });

        $out = $this->check($out, $data, "symbole", function ($v) {
            if(empty($v)) return "Le symbole est requis";
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

        $stmt = Database::instance()->prepare("SELECT COUNT(*) AS count FROM Devise ".$s[0]. "");
        
        if(isset($s[1])) {
            $stmt->bindValue(":recherche", $s[1]);
        }

        $stmt->execute();

        return $stmt->fetch()['count'];
    }

    protected function search(array $searchParams, int $limit, int $offset): PDOStatement {
        $s = $this->sql_recherche($searchParams);

        $stmt = Database::instance()->prepare("SELECT Devise.* FROM Devise ".$s[0]. " LIMIT :limit OFFSET :offset");
        
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
            "code"=>$data["code"]["value"],
            "nom"=>$data["nom"]["value"],
            "symbole"=>$data["symbole"]["value"],
        ];

        Database::instance()->execute("INSERT INTO Devise (code, nom, symbole) VALUES (?, ?, ?)", 
    array_values($row));

        return $row;
    }

    protected function render_row(array $row) {
        echo "<td>". htmlspecialchars($row["code"]) . "</td>";
        echo "<td>". htmlspecialchars($row["symbole"]) . "</td>"; 
    }

    protected function row_id(array $row) {
        return $row["code"];
    }

    protected function row_label(array $row) {
        return $row["code"]."(".$row["symbole"].")";
    }

    protected function get_names(): array {
        return [
            "Recherche une devise",
            "Ajouter une devise"
        ];
    }

    protected function get_url(): string {
        return "/portfolio"."/".addslashes($this->args["portfolio_id"])."/devises";
    }

    protected function form(array $data) {
        $this->print_input("code", "Code devise", $data);

        $this->print_input("nom", "Nom devise", $data);

        $this->print_input("symbole", "Symbole devise ($, €, ...)", $data);
    }
}

$acces = acces_portfolio($portfolio_id);

$affichage = new AffichageDevise(["portfolio_id"=>$portfolio_id, "title"=>"Devises"],
                                10,$acces>=2, $acces>=2, $acces>=2);

$affichage->render();
