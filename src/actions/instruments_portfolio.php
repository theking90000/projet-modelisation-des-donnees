<?php
    if($_REQUEST["REQUEST_METHOD"] == "POST") {

        die();
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

<h3>Rechercher un Instrument Financier</h3>

<input placeholder="Rechercher" id="search_instrument_value" value="<?= @$_GET["recherche"] ?>" oninput="search_instrument_debounce(this, <?= $page ?>, '/portfolio/<?= $portfolio_id ?>/instruments?<?php if (isset($callback)) { ?>callback_id=<?= $callback ?><?php } ?>');" />

<div class="instruments">
    <div id="search_instrument">
        <?php search_instrument($page, $recherche, $portfolio_id) ?>
    </div>
</div>

