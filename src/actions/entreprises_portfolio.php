<?php
    $callback = isset($_GET['callback_id']) ? $_GET["callback_id"] : null;

    // Si appelé sans $callback => Réafficher le layout
    if (!isset($callback) && !isset($is_template)) {
        require_once __DIR__ . "/../template/layout.php";
        
        render_page_unsafe(__FILE__, ["is_template"=>1, "portfolio_id"=>$portfolio_id]);
        die();
    }

    // Formulaire d'ajout
    if(isset($_POST["ajout_entreprise"])) {
        try {
            Database::instance()->beginTransaction();
            
            $ajout_nom = $_POST["ajout_nom"];

            if(empty($ajout_nom)) {
                $erreur_ajout_nom = "Le nom est requis";
            }

            $ajout_secteur = $_POST["ajout_secteur"];

            if(empty($ajout_secteur)) {
                $erreur_ajout_secteur = "Le secteur est requis";
            }

            $ajout_numero = $_POST["ajout_numero"];

            if(empty($ajout_numero)) {
                $erreur_ajout_numero = "Le numéro d'entreprise est requis.";
            }

            $ajout_pays = $_POST["ajout_pays"];

            if(empty($ajout_pays)) {
                $erreur_ajout_pays = "Un pays est requis.";
            } else {
                if(strlen($ajout_pays) != 2) {
                    $erreur_ajout_pays = "Code pays invalide.";
                } else {
                    $stmt = Database::instance()->execute("SELECT code, nom FROM Pays WHERE Pays.code = ?", [$ajout_pays]);

                    $pays = $stmt->fetch();

                    if(!$pays) {
                        $erreur_ajout_pays = "Pays inconnu.";
                    } else {
                        $ajout_pays_id = $pays['code'];
                        $ajout_pays_nom = $pays['nom'];
                    }
                }
            }
            

            // die();
            if(!isset($erreur_ajout_nom) &&
               !isset($erreur_ajout_numero) &&
               !isset($erreur_ajout_secteur) && 
               !isset($erreur_ajout_pays)) {
                // insérer
                $stmt = Database::instance()->execute("INSERT INTO Entreprise (numero, code_pays, nom, secteur) VALUES (?, ?, ?, ?);",
                [$ajout_numero, $ajout_pays_id, $ajout_nom, $ajout_secteur]);
                
                $entreprise_id = $ajout_numero.'_'.$ajout_pays;
                
                Database::instance()->commit();
            
                echo "<!-- CLOSE -->";
                echo json_encode([$entreprise_id, $ajout_nom]);
                die();
            }
        } catch (Exception $e) {
            $erreur_ajout = "Une erreur est survenue lors de l'ajout de l'entreprise";
            Database::instance()->rollBack();
        }
    } 

    $callback = $_GET["callback_id"];

    if (isset($_GET["page"])) {
        $page = intval($_GET["page"]);
    } else {
        $page = 0;
    }

    $recherche = $_GET["recherche"];
    
    if(!function_exists("search_entreprise")) {
        function search_entreprise($page, $recherche, $portfolio_id, $perPage=10) {
            $callback = $_GET["callback_id"];

            // var_dump($_GET);
            if(isset($recherche)) {
                $recherche = strtolower($_GET["recherche"]);
                $sql_recherche = "WHERE LOWER(nom) LIKE CONCAT('%', :recherche,'%')";
            } else {
                $recherche = null;
                $sql_recherche = "";
            }
            
            $stmt = Database::instance()->prepare("SELECT Entreprise.numero, Entreprise.code_pays, Entreprise.nom, Pays.nom AS nom_pays FROM Entreprise JOIN Pays ON Pays.code = Entreprise.code_pays ".$sql_recherche. " LIMIT :limit OFFSET :offset");
            $stmt2 = Database::instance()->prepare("SELECT COUNT(*) AS count FROM Entreprise ".$sql_recherche. "");
        
            if(isset($recherche)) {
                $stmt->bindValue(":recherche", $recherche);
                $stmt2->bindValue(":recherche", $recherche);
            }

            $stmt->bindValue(":limit", $perPage, PDO::PARAM_INT);
            $stmt->bindValue(":offset", $perPage*$page, PDO::PARAM_INT);

            $stmt->execute();
            $stmt2->execute();
            $count = $stmt2->fetch()['count'];
            $hasNextPage = ($page+1)*$perPage < $count;
            $hasPreviousPage = $page > 0; ?>
            <table>
                <tbody> 
            <?php while($entreprise = $stmt->fetch()) { ?>
                    <tr <?php if (isset($callback)) { ?>onclick="execute_callback('<?= $callback ?>', '<?= $entreprise['numero'].'_'.$entreprise['code_pays'] ?>', '<?= addslashes($entreprise['nom']) ?>')"<?php } ?> >
                        <td><?= $entreprise["nom"] ?></td>
                        <td><?= $entreprise["nom_pays"] ?></td> 
                        <td><?= $entreprise["numero"] ?> </td>
                    </tr>
            <?php } ?> 
                </tbody>
            </table> 
            <div>
                <?php if ($hasPreviousPage) { ?> <a href="#" onclick="search_ajax('#search_entreprise_value', '#search-entreprise', <?= $page-1 ?>, '/portfolio/<?= $portfolio_id ?>/entreprises?<?php if (isset($callback)) { ?>callback_id=<?= $callback ?><?php } ?>'); return false;" >Page précédente</a> <?php } ?>
                <?php if ($hasNextPage) { ?> <a href="#" onclick="search_ajax('#search_entreprise_value', '#search-entreprise', <?= $page+1 ?>, '/portfolio/<?= $portfolio_id ?>/entreprises?<?php if (isset($callback)) { ?>callback_id=<?= $callback ?><?php } ?>'); return false;">Page suivante</a> <?php } ?>
            </div><?php
        }
    }

    if (isset($_GET["ajax"])) {
        search_entreprise($page, $recherche, $portfolio_id);
        die();
    }
?>

<?php if (isset($is_template)) { ?>
<div class="center center-col h-screen">
<?php } ?>

<?php if (!isset($_POST["ajout_entreprise"])) { ?>

<h3>Rechercher une entreprise</h3>

<input placeholder="Rechercher" id="search_entreprise_value" value="<?= @$_GET["recherche"] ?>" oninput="search_ajax_debounce(this, '#search-entreprise', <?= $page ?>, '/portfolio/<?= $portfolio_id ?>/entreprises?<?php if (isset($callback)) { ?>callback_id=<?= $callback ?><?php } ?>');" />

<a href="#" data-open="#ajout-entreprise">Ajouter une entreprise</a>

<div class="search-result">
    <div id="search-entreprise">
        <?php search_entreprise($page, $recherche, $portfolio_id) ?>
    </div>
</div>


<div id="ajout-entreprise" data-portal="body" class="popup" data-popup="1" style="display: <?php if (isset($erreur_nom)) { echo "block"; } else { echo "none"; }  ?>;">
<?php } ?>

    <h3>Ajouter une entreprise</h3>

    <form action="/portfolio/<?= $portfolio_id ?>/entreprises?<?php if (isset($callback)) { ?>callback_id=<?= $callback ?><?php } ?>" method="post" class="center-col" onsubmit="submit_form(this, (html) => { if(html.includes('<!--'+' CLOSE '+'-->')) {closePopup(this.parentElement); <?php if (isset($callback)) { ?>execute_callback(<?= $callback ?>, ...JSON.parse(html.slice(14)));<?php } ?> } else { this.parentElement.innerHTML = html;} detect(); }); return false;">

        <input name="ajout_entreprise" value="1" hidden />

        <input name="ajout_nom" id="ajout_nom" placeholder="Nom entreprise" value="<?= $ajout_nom ?>" />

        <?php if(isset($erreur_ajout_nom)) { ?>
            <span style="color: red;"><?= $erreur_ajout_nom ?></span>
        <?php } ?>

        <input name="ajout_secteur" id="ajout_secteur" placeholder="Secteur" value="<?= $ajout_secteur ?>" />

        <?php if(isset($erreur_ajout_secteur)) { ?>
            <span style="color: red;"><?= $erreur_ajout_secteur ?></span>
        <?php } ?>

        <input name="ajout_numero" id="ajout_numero" placeholder="Numero d'entreprise" value="<?= $ajout_numero ?>" />

        <?php if(isset($erreur_ajout_numero)) { ?>
            <span style="color: red;"><?= $erreur_ajout_numero ?></span>
        <?php } ?>
        
        <div data-name="ajout_pays" data-value="<?= @$ajout_pays_id ?>" data-ext-select="/portfolio/<?= $portfolio_id ?>/pays">
        <?php if(isset($ajout_pays_id)) { ?> 
            <?= $ajout_pays_id ?>
        <?php } else { ?>
            Selectionner pays
        <?php } ?>
        </div>

        <?php if(isset($erreur_ajout_pays)) { ?>
            <span style="color: red;"><?= $erreur_ajout_pays ?></span>
        <?php } ?>
        
        <input type="submit" value="Enregistrer" />

        <?php if(isset($erreur_ajout)) { ?>
            <span style="color: red;"><?= $erreur_ajout ?></span>
        <?php } ?>
    </form>

<?php if (!isset($_POST["ajout_instrument"])) { ?>
</div>
<?php } ?>

<?php if (isset($is_template)) { ?>
</div>
<?php } ?>