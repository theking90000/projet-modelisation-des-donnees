<?php
    if($_REQUEST["REQUEST_METHOD"] == "POST") {

        die();
    } 

    $callback = $_GET["callback_id"];
    
    function search_instrument() {
        $callback = $_GET["callback_id"];

        // var_dump($_GET);
        if(isset($_GET["recherche"])) {
            $recherche = strtolower($_GET["recherche"]);
            $sql_recherche = "WHERE LOWER(nom) LIKE CONCAT('%', :recherche,'%')";
        } else {
            $recherche = null;
            $sql_recherche = "";
        }

        if (isset($_GET["page"])) {
            $page = intval($_GET["page"]);
        } else {
            $page = 0;
        }
        $perPage = 10;

        $stmt = Database::instance()->prepare("SELECT Instrument_Financier.* FROM Instrument_Financier ".$sql_recherche. " LIMIT :limit OFFSET :offset");
    
        if(isset($recherche)) {
            $stmt->bindValue(":recherche", $recherche);
        }

        $stmt->bindValue(":limit", $perPage, PDO::PARAM_INT);
        $stmt->bindValue(":offset", $perPage*$page, PDO::PARAM_INT);

        $stmt->execute(); ?>
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
        </table> <?php
    }

    if (isset($_GET["ajax"])) {
        search_instrument();
        die();
    }
?>

<h3>Rechercher un Instrument Financier</h3>

<input placeholder="Rechercher" value="<?= @$_GET["recherche"] ?>" oninput="search_instrument(this, '/portfolio/<?= $portfolio_id ?>/instruments?<?php if (isset($callback)) { ?>callback_id=<?= $callback ?><?php } ?>');" />

<div class="instruments">
    <div id="search_instrument">
        <?php search_instrument() ?>
    </div>
</div>

