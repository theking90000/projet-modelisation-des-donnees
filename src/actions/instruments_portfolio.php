<?php
    if($_SERVER["REQUEST_METHOD"] == "POST") {
        try {
            Database::instance()->beginTransaction();
            
            $ajout_nom = $_POST["ajout_nom"];

            if(empty($ajout_nom)) {
                $erreur_ajout_nom = "Le nom est requis";
            }

            $ajout_type = $_POST["ajout_type"];
        
            if($ajout_type !== "action" && $ajout_type !=="etf" && $ajout_type !== "obligation" && $ajout_type !== "devise") {
                $ajout_type = "action";
            }

            if ($ajout_type==="action"||$ajout_type==="obligation") {
                $ajout_entreprise = $_POST["ajout_entreprise"];

                if(empty($ajout_entreprise)) {
                    $erreur_ajout_entreprise = "Une entreprise est requise pour une action.";
                } else {
                    $aj = explode("_", $ajout_entreprise);
                    if(count($aj) != 2) {
                        $erreur_ajout_entreprise = "Identifiant entreprise invalide.";
                    } else {
                        $stmt = Database::instance()->execute("SELECT * FROM Entreprise WHERE Entreprise.numero = ? AND Entreprise.code_pays = ?", [$aj[0], $aj[1]]);

                        $entr = $stmt->fetch();

                        if(!$etnr) {
                            $erreur_ajout_entreprise = "Entreprise inconnue.";
                        } else {
                            $ajout_entreprise_id = $entr['numero'].'_'.$entr['code_pays'];
                            $ajout_entreprise_nom = $entr['nom'];
                        }
                    }
                }
            }

            // die();
            if(!isset($erreur_ajout_nom)) {
            
                // Vérifier les dépendances FK avant
                // insérer
                $instrument = ["isin"=>"ISIN_TEST", "nom"=>"Instrument test"];
                
                Database::instance()->commit();
            
                echo "<!-- CLOSE -->";
                echo json_encode([$instrument["isin"], $instrument["nom"]]);
                die();
            }
        } catch (Exception $e) {
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
    
    function search_instrument($page, $recherche, $portfolio_id, $perPage=10) {
        $callback = $_GET["callback_id"];

        // var_dump($_GET);
        if(isset($recherche)) {
            $recherche = strtolower($_GET["recherche"]);
            $sql_recherche = "WHERE LOWER(nom) LIKE CONCAT('%', :recherche,'%')";
        } else {
            $recherche = null;
            $sql_recherche = "";
        }
        
        $stmt = Database::instance()->prepare("SELECT Instrument_Financier.* FROM Instrument_Financier ".$sql_recherche. " LIMIT :limit OFFSET :offset");
        $stmt2 = Database::instance()->prepare("SELECT COUNT(*) AS count FROM Instrument_Financier ".$sql_recherche. "");
    
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
        <?php while($instrument = $stmt->fetch()) { ?>
                <tr <?php if (isset($callback)) { ?>onclick="execute_callback('<?= $callback ?>', '<?= $instrument['isin'] ?>', '<?= $instrument['isin'] ?>')"<?php } ?> >
                    <td><?= $instrument["symbole"] ?></td>
                    <td><?= $instrument["nom"] ?></td> 
                    <td><?= $instrument["isin"] ?> </td>
                </tr>
        <?php } ?> 
            </tbody>
        </table> 
        <div>
            <?php if ($hasPreviousPage) { ?> <a href="#" onclick="search_instrument(document.querySelector('#search_instrument_value'), <?= $page-1 ?>, '/portfolio/<?= $portfolio_id ?>/instruments?<?php if (isset($callback)) { ?>callback_id=<?= $callback ?><?php } ?>'); return false;" >Page précédente</a> <?php } ?>
            <?php if ($hasNextPage) { ?> <a href="#" onclick="search_instrument(document.querySelector('#search_instrument_value'), <?= $page+1 ?>, '/portfolio/<?= $portfolio_id ?>/instruments?<?php if (isset($callback)) { ?>callback_id=<?= $callback ?><?php } ?>'); return false;">Page suivante</a> <?php } ?>
        </div><?php
    }

    if (isset($_GET["ajax"])) {
        search_instrument($page, $recherche, $portfolio_id);
        die();
    }
?>

<?php if (!isset($_POST["ajout_instrument"])) { ?>

<h3>Rechercher un Instrument Financier</h3>

<input placeholder="Rechercher" id="search_instrument_value" value="<?= @$_GET["recherche"] ?>" oninput="search_instrument_debounce(this, <?= $page ?>, '/portfolio/<?= $portfolio_id ?>/instruments?<?php if (isset($callback)) { ?>callback_id=<?= $callback ?><?php } ?>');" />

<a href="#" data-open="#ajout-instrument">Ajouter un instrument financier</a>

<div class="instruments">
    <div id="search_instrument">
        <?php search_instrument($page, $recherche, $portfolio_id) ?>
    </div>
</div>


<div id="ajout-instrument" data-portal="body" class="popup" data-popup="1" style="display: <?php if (isset($erreur_nom)) { echo "block"; } else { echo "none"; }  ?>;">
<?php } ?>

    <h3>Ajouter un instrument financier</h3>

    <form action="/portfolio/<?= $portfolio_id ?>/instruments?<?php if (isset($callback)) { ?>callback_id=<?= $callback ?><?php } ?>" method="post" class="center-col" onsubmit="submit_form(this, (html) => { if(html.includes('<!--'+' CLOSE '+'-->')) {closePopup(this.parentElement); <?php if (isset($callback)) { ?>execute_callback(<?= $callback ?>, ...JSON.parse(html.slice(14)));<?php } ?> } else { this.parentElement.innerHTML = html;} detect(); }); return false;">

        <input name="ajout_instrument" value="1" hidden />

        <input name="ajout_nom" id="ajout_nom" placeholder="Nom instrument" value="<?= $ajout_nom ?>" />

        <?php if(isset($erreur_ajout_nom)) { ?>
            <span style="color: red;"><?= $erreur_ajout_nom ?></span>
        <?php } ?>
        
        <select id="ajout_type" name="ajout_type">
            <option <?= $ajout_type === "action" ? "selected" : "" ?> value="action">Action</option>
            <option <?= $ajout_type === "etf" ? "selected" : "" ?> value="etf">ETF</option>
            <option <?= $ajout_type === "obligation" ? "selected" : "" ?> value="obligation">Obligation</option>
            <option <?= $ajout_type === "devise" ? "selected" : "" ?> value="devise">Devise</option>
        </select>

        <div data-name="ajout_entreprise" data-value="<?= @$ajout_entreprise_id ?>" data-ext-select="/portfolio/<?= $portfolio_id ?>/entreprises">
        <?php if(isset($ajout_entreprise_id)) { ?> 
            <?= $ajout_entreprise_nom ?>
        <?php } else { ?>
            Selectionner entreprise
        <?php } ?>
        </div>

        <?php if(isset($erreur_ajout_entreprise)) { ?>
            <span style="color: red;"><?= $erreur_ajout_entreprise ?></span>
        <?php } ?>
        
        <input type="submit" value="Enregistrer" />
    </form>

<?php if (!isset($_POST["ajout_instrument"])) { ?>
</div>
<?php } ?>