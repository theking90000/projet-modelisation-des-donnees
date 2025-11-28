<?php

require_once __DIR__ . "/../template/layout.php";

abstract class AffichageTable {
    private string $render_id;

    private int $perPage;
    private bool $allowUpdate, $allowCreate, $allowDelete;
    public array $args;

    private string|null $callback;
    private bool $onlyResults, $onlyForm, $noPopup;
    private array|null $data;
    private string|null $update_id;

    // for update
    private array|null $entity = null;
    private array|null $added = null;

    private int $page;
    private string|null $addError = null;
    
    function __construct(array $args, int $perPage = 10, bool $allowUpdate=false, bool $allowCreate=false, bool $allowDelete = false) {
        $this->args = $args;
        $this->perPage = $perPage;
        $this->allowCreate = $allowCreate;
        $this->allowUpdate = $allowUpdate;
        $this->allowDelete = $allowDelete;

        $this->render_id = isset($_GET["rid"]) ? htmlspecialchars(addslashes($_GET['rid'])) :  uniqid();
        $this->callback = isset($_GET["callback_id"]) ? $_GET["callback_id"] : null;
        // Flag "ajax" pour retourner uniquement les lignes.
        $this->onlyResults = isset($_GET["ajax"]);
        // Flag "ajout" pour retourner uniquement le formulaire
        $this->onlyForm = (isset($_POST["ajout"]) && isset($_GET["callback_id"])) || (isset($_GET["form"]));
        $this->noPopup = isset($_GET["nopopup"]);

        $this->update_id = isset($_GET["update"]) && $this->allowUpdate ? $_GET["update"] : null;

        $this->page = isset($_GET['page']) ? intval($_GET['page']) : 0;

        if ($this->page < 0) $this->page = 0;

        if ($_SERVER['REQUEST_METHOD'] === "POST") {
            $this->handlePost();
        } else if ($this->update_id) {
            $this->handleUpdate();
        } else {
            //$this->data = [];
            $this->data = $this->parse([]);
            $this->data = $this->remove_errors($this->data);
        }
    }

    public function render() {
        if($this->update_id && $this->added) {
            if(isset($this->callback)) {
                echo "<!-- CLOSE -->";
                echo json_encode([$this->row_id($this->added), $this->row_label($this->added)]);
            } else {
            // Redirect? si succès;
                header("Location: ". $this->current_url(true));
            }
            die();
        }

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

        if ($this->onlyForm || $this->update_id) {
            if ($this->update_id) {
                $this->printForm(true);
            } else if ($this->allowCreate) {
                $this->printForm();
            }
        } else {
            $this->printHead();
            if ($this->allowCreate) {
                $this->printForm();
            }
        }

        if($with_template) {
            if(isset($this->args["portfolio_id"])) {
                echo "<a href=\"/portfolio/".$this->args["portfolio_id"]."\">Retour</a>\n";
            }
            echo "</div>\n";
            //echo "<a href=\"/portfolio/$portfolio_id\">Retour</a>\n";
        }
    }

    private function handleUpdate() {
        if($this->allowUpdate) {
            $this->entity = $this->get($this->update_id);
            if(!$this->entity) {
                // Afficher une page d'erreur
                throw new Exception("Identifiant inconnu");
            }

            $this->data = $this->parse($this->entity);
        }
    }
 
    private function handlePost() {
        if($this->allowCreate && $_POST["ajout"]) {
            try {
                Database::instance()->beginTransaction();

                if($this->allowUpdate && $this->update_id) {
                    $this->entity = $this->get($this->update_id);
                    if(!$this->entity) {
                        throw new Exception("Identifiant inconnu");
                    }
                }

                $this->data = $this->parse($_POST);

                if(!$this->has_errors($this->data)) {
                    if($this->entity) {
                        $this->update($this->update_id, $this->data);
                        $this->entity = $this->added = $this->get($this->update_id);
                    } else {
                        $this->added = $this->insert($this->data);
                        $this->data = [];
                    }
                }

                Database::instance()->commit();
            } catch (Exception $e) {
                $this->addError = "Une erreur est survenue : ".$e->getMessage();
                Database::instance()->rollBack();
            }
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

    private function remove_errors(array $data) {
        foreach ($data as $key => &$field) {
            if (is_array($field) && isset($field['error'])) {
                unset($field['error']);
            }
        }
        return $data;
    }

    private function current_url(bool $noUpdate = false): string {
        $url = $this->get_url().'?';
        
        if(isset($this->callback)) {
            $url = $url. 'callback_id='.urlencode($this->callback);
        }

        if($this->noPopup) {
            $url = $url.'&nopopup=1';
        }

        if(isset($_GET["form"])) {
            $url=$url.'&form=1';
        }

        if($this->update_id && !$noUpdate) {
            $url=$url.'&update='.urlencode($this->update_id);
        }

        return $url;
    }

    public function check($out, $data, $name, callable $fn) : array {
        return $this->check_transform($out, $data, $name, function ($v) use ($fn) {
            return [$fn($v), $v];
        });
    }

    public function check_select($out, $data, $name, $in, $default) : array {
        return $this->check_transform($out, $data, $name, function ($v) use ($in, $default) {
            if(in_array($v, $in)) {
                return [null, $v];
            }
            
            return [null, $default];
        });
    }

    public function check_transform($out, $data, $name, callable $fn) : array {
        $res = $fn($data[$name]);
        $out[$name] = [
            "value" => $res[1],
            "error" => $res[0],
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

    protected function print_input_fn($name, $data, callable $fn) {
        if (!isset($data[$name])||!isset($data[$name]["value"])) {
            $value = '';
        } else {
            $value = $data[$name]["value"];
        }

        $fn($value);

        if (isset($data[$name])) {
            $this->print_error($data[$name]);
        }
    }

    protected function print_label($name, $label) {
        echo "<label for=\"".$name."-".$this->render_id."\">";;
        echo $label;
        echo "</label>\n";
    }

    protected function print_input($name, $placeholder, $data, $allowUpdate=true) {
        $this->print_input_fn($name, $data, function ($value) use ($name, $placeholder, $allowUpdate) {
            echo "<input name=\"$name\" id=\"$name-$this->render_id\" placeholder=\"$placeholder\" value=\"";
            echo htmlspecialchars($value);
            echo "\" ";
            if ($this->update_id && !$allowUpdate) {
                echo "readonly ";
            }
            echo ">\n";
        });
    }

    protected function print_select($name, $in, $display, $data) {
        $this->print_input_fn($name, $data, function ($value) use ($name, $in, $display) {
            echo "<select id=\"$name-$this->render_id\" name=\"$name\">\n";
            for ($i = 0; $i < count($in); $i++) {
                $option = $in[$i];
                $displayText = $display[$i];
                echo "<option value=\"";
                echo addslashes($option);
                echo "\" ";

                if($value === $option) {
                    echo "selected";
                }

                echo " >";
                echo htmlspecialchars($displayText);
                echo "</option>\n";
            }
            echo "\n</select>\n";
        });
    }

    protected function print_if($name, array $value, $data, callable $fn) {
        echo "<div data-if=\"#";
        echo addslashes($name);
        echo '-'.$this->render_id;
        echo "\" data-if-value=\"";
        echo addslashes(implode("|", $value));
        echo "\" style=\"display: ";
        // TODO: more way of comparison
        if (isset($data[$name]) && in_array($data[$name]['value'], $value)) {
            echo "block";
        } else {
            echo "none";
        }
        echo ";\">\n";

        $fn();

        echo "\n</div>\n";
    }

    protected function print_ext_select($name, $placeholder, $select, callable $row_id, callable $row_label, $data) {
        $this->print_input_fn($name, $data, function ($value) use ($name, $placeholder, $select, $row_id, $row_label) {
            $id = !is_string($value) ? $row_id($value) : '';
            $label = !is_string($value) ? $row_label($value) : $placeholder;
            
            echo "<button type=\"button\" data-name=\"$name\" data-value=\"";
            echo addslashes($id);
            echo "\" placeholder=\"$placeholder\" value=\"";
            echo htmlspecialchars(addslashes($id));
            echo "\" data-ext-select=\"";
            echo addslashes($select);
            echo "\" >";

            echo htmlspecialchars($label);

            echo "</button>\n";
        });
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

    /**
     * Récuperer une entité par son id
     * retourne NULL si n'existe pas
     */
    protected function get(string $id): array {
        throw new Exception("get(): n'est pas implémenté");
    }

    /**
     * Modifier une entité
     */
    protected function update(string $id, array $data) {
        throw new Exception("update() n'est pas implémenté");
    }

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

            if(!isset($this->callback) && $this->allowUpdate) {
                echo "<td><a href=\"";
                echo $this->current_url(true).'&update='. urlencode($this->row_id($row));
                echo "\">";
                
                echo "Modifier";

                echo "</a></td>\n";
            }

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
            
            echo addslashes($this->current_url().'&rid='.$this->render_id.'&');
            echo "'); return false;\" >";

            echo $text;

            echo "</a>\n";
        };

        if ($hasPreviousPage) $nav($this->page-1, "Page précédente");
        if ($hasNextPage) $nav($this->page + 1, "Page suivante");
    }

    private function printForm(bool $update = false) {
        if(!$this->noPopup) {
            echo "<div id=\"ajout-";
            echo $this->render_id;
            echo "\" data-portal=\"body\" class=\"popup\" data-popup=\"1\" style=\"display: ";

            if ($this->has_errors($this->data) || $update) {
                echo "block";
            } else {
                echo "none";
            }

            echo ";\" ";

            if($update){
                echo "data-redirect-on-close=\"";
                echo $this->current_url(true);
                echo "\"";
            }

            echo ">\n";
        }

        $names = $this->get_names();

        echo "<h3>";
        if ($update) {
            echo "Modifier " . htmlspecialchars($this->row_label($this->entity));
        } else {
            echo htmlspecialchars($names[1]);
        }
        echo "</h3>\n";

        echo "<form action=\"";
        echo addslashes($this->current_url());
        echo "\" method=\"post\" class=\"center-col\" ";

        if (isset($this->callback)) {
            echo "onsubmit=\"submit_form(this,";
            echo "(html) => { if(html.includes('<!--'+' CLOSE '+'-->')) {";
            if (!$this->noPopup) {
                echo "closePopup(this.parentElement);";
            }
            echo "execute_callback('";
            echo addslashes($this->callback);
            echo "', ...JSON.parse(html.slice(14))); }";
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

        echo "</form>\n";
        if(!$this->noPopup) {
            echo "</div>\n";
        }
    }

    private function printHead () {
        $names = $this->get_names();

        echo "<h3>". $names[0] . "</h3>\n";

        // TODO: autoriser plus de customisation via une méthode abstract;
        echo "<input placeholder=\"Rechercher\" type=\"search\" id=\"filter-";
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

        if ($this->allowCreate) {
            echo "<a href=\"#\" data-open=\"#ajout-";
            echo $this->render_id;
            echo "\">";
            echo $names[1];
            echo "</a>\n";
        }

        echo "<div class=\"search-result\">\n";
        echo "<div id=\"results-";
        echo $this->render_id;
        echo "\">\n";

        $this->printResults();

        echo "</div>\n</div>\n";
    }
}