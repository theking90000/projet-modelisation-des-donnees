<?php

require_once __DIR__ . "/../template/layout.php";

abstract class AffichageTable {
    private string $render_id;

    private int $perPage;

    public array $args;

    private string|null $callback;
    private bool $onlyResults, $onlyForm;
    private array|null $data;

    private array|null $added = null;

    private int $page;
    private string|null $addError = null;
    
    function __construct(array $args, int $perPage = 10) {
        $this->args = $args;
        $this->perPage = $perPage;
        $this->render_id = isset($_GET["rid"]) ? htmlspecialchars(addslashes($_GET['rid'])) :  uniqid();
        $this->callback = isset($_GET["callback_id"]) ? $_GET["callback_id"] : null;
        // Flag "ajax" pour retourner uniquement les lignes.
        $this->onlyResults = isset($_GET["ajax"]);
        // Flag "ajout" pour retourner uniquement le formulaire
        $this->onlyForm = isset($_POST["ajout"]) && isset($_POST["callback"]);

        $this->page = isset($_GET['page']) ? intval($_GET['page']) : 0;

        if ($this->page < 0) $this->page = 0;

        if ($_SERVER['REQUEST_METHOD'] === "POST") {
            $this->handlePost();
        } else {
            $this->data = [];
        }
    }

    public function render() {
        // Si callback est défini, alors la page est intégrée sur une autre
        // via javascript, ne pas retourner le template
        // et quand on sélectionne une ligne, appeler execute_callback() en JS.
        if($this->onlyResults) {
            $this->printResults();
            return;
        }

        if (isset($this->callback)) {
            if ($this->added) {
                echo "<!-- CLOSE -->";
                echo json_encode([$this->row_id($this->added), $this->row_label($this->added)]);
            } else {
                $this->renderContent(false);
            }
        } else {
            $callback = function($arg) {
                return $this->renderContent(true);
            };

            render_page_fn($callback, $this->args);
        }
    }

    private function renderContent(bool $with_template) {
        if ($with_template) {
            echo "<div class=\"center center-col h-screen\">";
        }

        if ($this->onlyForm) {
            $this->printForm();
        } else {
            $this->printHead();
            $this->printForm();
        }

        if($with_template) {
            echo "</div>\n";
            //echo "<a href=\"/portfolio/$portfolio_id\">Retour</a>\n";
        }
    }

    
 
    private function handlePost() {
        try {
            Database::instance()->beginTransaction();

            $this->data = $this->parse($_POST);

            if(!$this->has_errors($this->data)) {
                $this->added = $this->insert($this->data);
                $this->data = [];
            }

            Database::instance()->commit();
        } catch (Exception $e) {
            $this->addError = "Une erreur est survenue";//.$e->getMessage();
            Database::instance()->rollBack();
        }
    }

    private function has_errors(array $data) : bool {
        foreach ($data as $field) {
            if (isset($field['error'])) {
                return true;
            }
        }
        return !!$this->addError;
    }

    private function current_url(): string {
        $url = $this->get_url().'?rid='.$this->render_id.'&';
        
        if(isset($this->callback)) {
            $url = $url. 'callback_id='.htmlspecialchars($this->callback);
        }

        return $url;
    }

    public function check($out, $data, $name, callable $fn) : array {
        $out[$name] = [
            "value" => $data[$name],
            "error" => $fn($data[$name])
        ];
        return $out;
    }

    // Afficher une erreur pour ["value"=>, "error"=>""|null]
    protected function print_error($data) {
        if (isset($data["error"])) {
            echo "<span style=\"color: red;\">";
            echo $data["error"];
            echo "</span>\n";
        }
    }

    protected function print_input($name, $placeholder, $data) {
        if (!isset($data[$name])||!isset($data[$name]["value"])) {
            $value = '';
        } else {
            $value = $data[$name]["value"];
        }
        

        echo "<input name=\"$name\" id=\"$name\" placeholder=\"$placeholder\" value=\"";
        echo htmlspecialchars(addslashes($value));
        echo "\" >\n";

        if (isset($data[$name])) {
            $this->print_error($data[$name]);
        }
    }

    /**
     * Récupérer les données de formulaire (ajout ligne) de $data ($_POST)
     * Et retourner un tableau avec pour chaque champ.
     * "nom_champ" => ["value" => "", "error" => "" ou pas set]
     * Pour une erreur globale utiliser le champ "error".
     */
    protected abstract function parse(array $data): array;


    /**
     * Retourne le nombre de lignes qui satisfont la requête
     */
    protected abstract function count(array $searchParams): int;

    /**
     * Executer une recherche sur la table avec les paramètres
     * contenus dans $searchParams ($_GET)
     * Retourne la requete SQL Executée
     */
    protected abstract function search(array $searchParams, int $limit, int $offset): PDOStatement;

    /**
     * Insérer une nouvelle ligne $data = résultat de parse()
     * Retourne la ligne nouvelle (row_id() et row_label() vont être appelés dessus)
     */
    protected abstract function insert(array $data): array;

    /**
     * Afficher une ligne récupérée depuis 'search()'
     * sortie dans l'affichage stdout.
     */
    protected abstract function render_row(array $row);

    /**
     * Retourne l'identifiant d'une ligne (pour execute_callback)
     */
    protected abstract function row_id(array $row);

    /**
     * Retourne le label d'une ligne (pour execute_callback)
     */
    protected abstract function row_label(array $row);

    /**
     * Afficher le formulaire avec les données
     * "nom_champ" => ["value" => "", "error" => "" ou pas set]
     */
    protected abstract function form(array $data);

    /**
     * Retourne une liste avec les noms à afficher sur la page
     * - "Rechercher XX"
     * - "Ajout XX"
     */
    protected abstract function get_names(): array;

    /**
     * Obtenir l'URL de la page actuelle.
     * Sans '?' à la fin
     */
    protected abstract function get_url() : string;

    private function printResults() {
        $count = $this->count($_GET);
        $stmt = $this->search($_GET, $this->perPage, $this->perPage*$this->page);

        $hasNextPage = ($this->page+1)*$this->perPage < $count;
        $hasPreviousPage = $this->page > 0;

        echo "<table><tbody>\n";
        while($row = $stmt->fetch()) {
            echo "<tr ";

            if (isset($this->callback)) {
                echo "onclick=\"execute_callback('";

                echo addslashes($this->callback);
                echo "', '";

                echo addslashes($this->row_id($row));
                echo "', '";

                echo addslashes($this->row_label($row));

                echo "');\"";
            }

            echo " >\n";
            $this->render_row($row);
            echo "\n<tr>";
        }
        echo "\n</tbody></table>\n";

        $nav = function ($page, $text) {
            echo "<a href=\"#\" onclick=\"search_ajax('#filter-";
            
            echo $this->render_id;
            echo "', '#results-";

            echo $this->render_id;
            echo "', ";
            
            echo $page;
            echo ", '";
            
            echo addslashes($this->current_url());
            echo "'); return false;\" >";

            echo $text;

            echo "</a>\n";
        };

        if ($hasPreviousPage) $nav($this->page-1, "Page précédente");
        if ($hasNextPage) $nav($this->page + 1, "Page suivante");
    }

    private function printForm() {
        echo "<div id=\"ajout-";
        echo $this->render_id;
        echo "\" data-portal=\"body\" class=\"popup\" data-popup=\"1\" style=\"display: ";

        if ($this->has_errors($this->data)) {
            echo "block";
        } else {
            echo "none";
        }

        echo ";\" >\n";

        $names = $this->get_names();

        echo "<h3>";
        echo htmlspecialchars($names[1]);
        echo "</h3>\n";

        echo "<form action=\"";
        echo addslashes($this->current_url());
        echo "\" method=\"post\" class=\"center-col\" ";

        if (isset($this->callback)) {
            echo "onsubmit=\"submit_form(this,";
            echo "(html) => { if(html.includes('<!--'+' CLOSE '+'-->')) {";
            echo "closePopup(this.parentElement); execute_callback(";
            echo $this->callback;
            echo ", ...JSON.parse(html.slice(14))); }";
            echo "else { this.parentElement.innerHTML = html;} detect(); }); return false;\" ";
        }

        echo ">\n";

        echo "<input hidden name=\"ajout\" value=\"1\" >\n";

        $this->form($this->data);

        echo "\n<input type=\"submit\" value=\"Enregistrer\" />\n";
       
        $this->print_error($this->data);
        
        if($this->addError) {
            $this->print_error(["error"=>$this->addError]);
        }

        echo "</div>\n";
    }

    private function printHead () {
        $names = $this->get_names();

        echo "<h3>". $names[0] . "</h3>\n";

        // TODO: autoriser plus de customisation via une méthode abstract;
        echo "<input placeholder=\"Rechercher\" id=\"filter-";
        echo $this->render_id;
        echo "\" value=\"";
        if (isset($_GET["recherche"])) {
            echo htmlspecialchars(addslashes($_GET["recherche"]));
        }
        echo "\" oninput=\"search_ajax_debounce(this, '";
        echo "#results-";
        echo $this->render_id;
        echo "', ";
        echo $this->page; 
        echo ", '";
        echo addslashes($this->current_url());
        echo "');\" />\n";

        echo "<a href=\"#\" data-open=\"#ajout-";
        echo $this->render_id;
        echo "\">";
        echo $names[1];
        echo "</a>\n";

        echo "<div class=\"search-result\">\n";
        echo "<div id=\"results-";
        echo $this->render_id;
        echo "\">\n";

        $this->printResults();

        echo "</div>\n</div>\n";
    }
}