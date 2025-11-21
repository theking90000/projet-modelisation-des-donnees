<?php
require_once __DIR__ . '/../lib/affichage_table.php';

class AffichageBourses extends AffichageTable {
    protected function parse(array $data): array {
        $out = [];

        $out = $this->check($out, $data, "id", function ($v) {
            if(empty($v)) return "L'identifiant est requis";
        });

        $out = $this->check($out, $data, "nom", function ($v) {
            if(empty($v)) return "Le nom est requis";
        });

        $out = $this->check($out, $data, "ville", function ($v) {
            if(empty($v)) return "La ville est requise";
        });

        $out = $this->check($out, $data, "fuseau_horaire", function ($v) {
            if(empty($v)) return "Le fuseau horaire est requis";
        });

        $out = $this->check($out, $data, "heure_ouverture", function ($v) {
            if(empty($v)) return "L'heure d'ouveture est requise";
        });

        $out = $this->check($out, $data, "heure_fermeture", function ($v) {
            if(empty($v)) return "L'heure de fermeture est requise";
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
        $recherche = isset($searchParams['recherche']) ? $searchParams['recherche'] : null;

        if(isset($recherche)) {
            return ["WHERE LOWER(Bourse.nom) LIKE CONCAT('%', :recherche,'%')", strtolower($_GET["recherche"])];
        } 
        
        return ["", null];
    }

    protected function count(array $searchParams): int {
        $s = $this->sql_recherche($searchParams);

        $stmt = Database::instance()->prepare("SELECT COUNT(*) AS count FROM Bourse ".$s[0]. "");
        
        if(isset($s[1])) {
            $stmt->bindValue(":recherche", $s[1]);
        }

        $stmt->execute();

        return $stmt->fetch()['count'];
    }

    protected function search(array $searchParams, int $limit, int $offset): PDOStatement {
        $s = $this->sql_recherche($searchParams);

        $stmt = Database::instance()->prepare("SELECT Bourse.*, Pays.nom AS nom_pays FROM Bourse JOIN Pays ON Pays.code = Bourse.code_pays ".$s[0]. " LIMIT :limit OFFSET :offset");
        
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
            "id"=>$data["id"]["value"], 
            "nom"=>$data["nom"]["value"], 
            "ville"=>$data["pays"]["value"]["code"], 
            "fuseau_horaire"=>$data["fuseau_horaire"]["value"],
            "heure_ouverture"=>$data["heure_ouverture"]["value"],
            "heure_fermeture"=>$data["heure_fermeture"]["value"],
            "code_pays"=>$data["pays"]["value"]["code"],
        ];

        Database::instance()->execute("INSERT INTO Bourse (id, nom, ville, fuseau_horaire, heure_ouverture, heure_fermeture, code_pays) VALUES (:id, :nom, :ville, :fuseau_horaire, :heure_ouverture, :heure_fermeture, :code_pays);",
                $row);
                

        return $row;
    }

    protected function render_row(array $row) {
        echo "<td>". htmlspecialchars($row["id"]) . "</td>\n";
        echo "<td>". htmlspecialchars($row["nom"]) . "</td>\n";
        echo "<td>". htmlspecialchars($row["ville"]) . "</td>\n";
    }

    protected function row_id(array $row) {
        return $row["id"];
    }

    protected function row_label(array $row) {
        return $row["id"]."(".$row["ville"].")";
    }

    protected function get_names(): array {
        return [
            "Recherche une bourse d'échange",
            "Ajouter une bourse d'échange"
        ];
    }

    protected function get_url(): string {
        return "/portfolio"."/".addslashes($this->args["portfolio_id"])."/bourses";
    }

    protected function form(array $data) {
        $this->print_label("id", "Identifiant : ");
        $this->print_input("id", "Identifiant", $data);

        $this->print_label("nom", "Nom : ");
        $this->print_input("nom", "Nom", $data);

        $this->print_label("ville", "Ville : ");
        $this->print_input("ville", "Ville", $data);

        $this->print_label("fuseau_horaire", "Fuseau horaire");
        $this->print_input("fuseau_horaire", "Fuseau horaire", $data);

        $this->print_label("heure_ouverture", "Heure d'ouveture :");
        $this->print_input("heure_ouverture", "Heure d'ouveture", $data);

        $this->print_label("heure_fermeture", "Heure de fermeture : ");
        $this->print_input("heure_fermeture", "Heure de fermeture", $data);

        $this->print_ext_select("pays", "Selectionner pays", "/portfolio"."/".$this->args["portfolio_id"]."/pays",
        function ($v) { return $v["code"]; },
        function ($v) { return $v["nom"]; },
        $data);
    }
}

$acces = acces_portfolio($portfolio_id);

$affichage = new AffichageBourses(["portfolio_id"=>$portfolio_id, "title"=>"Bourses"], 10,
                                      $acces>=2, $acces>=2, $acces>=2);

$affichage->render();
