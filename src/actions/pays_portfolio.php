<?php
    $callback = isset($_GET['callback_id']) ? $_GET["callback_id"] : null;

    // Si appelé sans $callback => Réafficher le layout
    if (!isset($callback) && !isset($is_template) && !isset($_POST["ajout_pays"])) {
        require_once __DIR__ . "/../template/layout.php";
        
        render_page_unsafe(__FILE__, ["is_template"=>1, "portfolio_id"=>$portfolio_id]);
        die();
    }

    // Formulaire d'ajout
    if(isset($_POST["ajout_pays"])) {
        try {
            Database::instance()->beginTransaction();
            
            $ajout_nom = $_POST["ajout_nom"];

            if(empty($ajout_nom)) {
                $erreur_ajout_nom = "Le nom est requis";
            }

            $ajout_code = $_POST["ajout_code"];

            if(empty($ajout_code)) {
                $erreur_ajout_code = "Le code du pays est requis";
            } else if (strlen($ajout_code)!=2) {
                $erreur_ajout_code = "Le code du pays doit faire exactement 2 caractères";
            }
            

            // die();
            if(!isset($erreur_ajout_nom) &&
               !isset($erreur_ajout_code)) {
                // insérer
                $stmt = Database::instance()->execute("INSERT INTO Pays (code, nom) VALUES (?, ?)",
                [$ajout_code, $ajout_nom]);
                                
                Database::instance()->commit();
            
                echo "<!-- CLOSE -->";
                echo json_encode([$ajout_code, $ajout_nom]);
                die();
            }
        } catch (Exception $e) {
            $erreur_ajout = "Une erreur est survenue lors de l'ajout du pays";
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
    
    if(!function_exists("search_pays")) {
        function search_pays($page, $recherche, $portfolio_id, $perPage=10) {
            $callback = $_GET["callback_id"];

            // var_dump($_GET);
            if(isset($recherche)) {
                $recherche = strtolower($_GET["recherche"]);
                $sql_recherche = "WHERE LOWER(nom) LIKE CONCAT('%', :recherche,'%')";
            } else {
                $recherche = null;
                $sql_recherche = "";
            }
            
            $stmt = Database::instance()->prepare("SELECT Pays.code, Pays.nom FROM Pays ".$sql_recherche. " LIMIT :limit OFFSET :offset");
            $stmt2 = Database::instance()->prepare("SELECT COUNT(*) AS count FROM Pays ".$sql_recherche. "");
        
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
            <?php while($pays = $stmt->fetch()) { ?>
                    <tr <?php if (isset($callback)) { ?>onclick="execute_callback('<?= $callback ?>', '<?= $pays['code'] ?>', '<?= addslashes($pays['nom']) ?>')"<?php } ?> >
                        <td><?= $pays["code"] ?></td>
                        <td><?= $pays["nom"] ?></td> 
                    </tr>
            <?php } ?> 
                </tbody>
            </table> 
            <div>
                <?php if ($hasPreviousPage) { ?> <a href="#" onclick="search_ajax('#search_pays_value', '#search-pays', <?= $page-1 ?>, '/portfolio/<?= $portfolio_id ?>/pays?<?php if (isset($callback)) { ?>callback_id=<?= $callback ?><?php } ?>'); return false;" >Page précédente</a> <?php } ?>
                <?php if ($hasNextPage) { ?> <a href="#" onclick="search_ajax('#search_pays_value', '#search-pays', <?= $page+1 ?>, '/portfolio/<?= $portfolio_id ?>/pays?<?php if (isset($callback)) { ?>callback_id=<?= $callback ?><?php } ?>'); return false;">Page suivante</a> <?php } ?>
            </div><?php
        }
    }

    if (isset($_GET["ajax"])) {
        search_pays($page, $recherche, $portfolio_id);
        die();
    }
?>

<?php if (isset($is_template)) { ?>
<div class="center center-col h-screen">
<?php } ?>

<?php if (!isset($_POST["ajout_pays"])) { ?>

<h3>Rechercher un pays</h3>

<input placeholder="Rechercher" id="search_pays_value" value="<?= @$_GET["recherche"] ?>" oninput="search_ajax_debounce(this, '#search-pays', <?= $page ?>, '/portfolio/<?= $portfolio_id ?>/pays?<?php if (isset($callback)) { ?>callback_id=<?= $callback ?><?php } ?>');" />

<a href="#" data-open="#ajout-pays">Ajouter un pays</a>

<div class="search-result">
    <div id="search-pays">
        <?php search_pays($page, $recherche, $portfolio_id) ?>
    </div>
</div>


<div id="ajout-pays" data-portal="body" class="popup" data-popup="1" style="display: <?php if (isset($erreur_nom)) { echo "block"; } else { echo "none"; }  ?>;">
<?php } ?>

    <h3>Ajouter un pays</h3>

    <form action="/portfolio/<?= $portfolio_id ?>/pays?<?php if (isset($callback)) { ?>callback_id=<?= $callback ?><?php } ?>" method="post" class="center-col" onsubmit="submit_form(this, (html) => { if(html.includes('<!--'+' CLOSE '+'-->')) {closePopup(this.parentElement); <?php if (isset($callback)) { ?>execute_callback(<?= $callback ?>, ...JSON.parse(html.slice(14)));<?php } ?> } else { this.parentElement.innerHTML = html;} detect(); }); return false;">

        <input name="ajout_pays" value="1" hidden />

        <input name="ajout_code" id="ajout_code" placeholder="Code pays" value="<?= $ajout_code ?>" />

        <?php if(isset($erreur_ajout_code)) { ?>
            <span style="color: red;"><?= $erreurajout_code ?></span>
        <?php } ?>

        <input name="ajout_nom" id="ajout_nom" placeholder="Nom pays" value="<?= $ajout_nom ?>" />

        <?php if(isset($erreur_ajout_nom)) { ?>
            <span style="color: red;"><?= $erreur_ajout_nom ?></span>
        <?php } ?>

        <input type="submit" value="Enregistrer" />

        <?php if(isset($erreur_ajout)) { ?>
            <span style="color: red;"><?= $erreur_ajout ?></span>
        <?php } ?>
    </form>

<?php if (!isset($_POST["ajout_pays"])) { ?>
</div>
<?php } ?>

<?php if (isset($is_template)) { ?>
</div>
<a href="/portfolio/<?= $portfolio_id ?>">Retour</a>
<?php } ?>