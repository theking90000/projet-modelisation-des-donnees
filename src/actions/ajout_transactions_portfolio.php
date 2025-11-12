<?php
    if($_SERVER["REQUEST_METHOD"] == "POST") {
        $quantite = $_POST["quantite"];
        if (!preg_match('/^-?\d+(\.\d+)?$/', $quantite)) {
            $erreur_quantite = "La quantité doit être un nombre";
        }

        $taxes = $_POST["taxes"];
        if (!empty($taxes) && !preg_match('/^-?\d+(\.\d+)?$/', $taxes)) {
            $erreur_taxes = "La taxe doit être un nombre";
        }

        $frais = $_POST["frais"];
        if (!empty($frais) && !preg_match('/^-?\d+(\.\d+)?$/', $frais)) {
            $erreur_frais = "Les frais doit être un nombre";
        }

        $instrument = $_POST["instrument"];
        
        if(empty($instrument)) {
            $erreur_instrument = "Aucun instrument financier sélectionné";
        }

        $type = $_POST["type"];
        
        if($type !== "achat" && $type !=="vente") {
            $type = "achat";
        }


        Database::instance()->beginTransaction();

        try {
            $stmt = Database::instance()->execute("SELECT Instrument_Financier.* FROM Instrument_Financier WHERE isin = ?", [$instrument]);

            $instr = $stmt->fetch();

            if(!isset($instr)) {
                $erreur_instrument = "Instrument inconnu";
            } else {
                $instrument_id = $instr['isin'];
            }

            if(!isset($erreur_instrument) && !isset($erreur_quantite) && !isset($erreur_frais) && !isset($erreur_taxes)) {
                // TODO: Récuperer les informations sur
                // 1) Valeur devises
                // 2) Valeur titre à l'instant T
                // Database::instance()->execute("INSERT INTO Instrument_Financier (id_portfolio, isin, email_utilisateur, type, date, heure, quantite, valeur_devise_portfolio, valeur_devise_instrument, frais, taxes) VALUES (?)");
            
                Database::instance()->commit();
            
                echo "<!-- CLOSE -->";
                die();
            }
        } catch (Exception $e) {
            Database::instance()->rollBack();
        }
    }
?>

<h3>Ajouter une transaction</h3>

<form action="/portfolio/<?= $portfolio_id ?>/ajout-transaction" method="post" class="center-col" onsubmit="submit_form(this, (html) => {if(html.includes('<!--'+' CLOSE '+'-->')) {closePopup(this.parentElement); } else { this.parentElement.innerHTML = html;} detect();}); return false;">
    <div data-name="instrument" data-value="<?= @$instrument_id ?>" data-ext-select="/portfolio/<?= $portfolio_id ?>/instruments">
        <?php if(isset($instrument_id)) { ?> 
            <?= $instrument_id ?>
        <?php } else { ?>
            Selectionner instrument
        <?php } ?>
    </div>

    <?php if(isset($erreur_instrument)) { ?>
        <span style="color: red;"><?= $erreur_instrument ?></span>
    <?php } ?>

    <input name="quantite" id="quantite" placeholder="Quantité" value="<?= @$quantite ?>" />

    <?php if(isset($erreur_quantite)) { ?>
        <span style="color: red;"><?= $erreur_quantite ?></span>
    <?php } ?>

    <select id="type" name="type">
        <option <?= $type === "achat" ? "selected" : "" ?> value="achat">Achat</option>
        <option <?= $type === "vente" ? "selected" : "" ?> value="vente">Vente</option>
    </select>

    <input name="taxes" id="taxes" placeholder="Taxes" value="<?= @$taxes ?>" />

    <?php if(isset($erreur_taxes)) { ?>
        <span style="color: red;"><?= $erreur_taxes ?></span>
    <?php } ?>

    <input name="frais" id="frais" placeholder="Frais courtage" value="<?= @$frais ?>" />

    <?php if(isset($erreur_frais)) { ?>
        <span style="color: red;"><?= $erreur_frais ?></span>
    <?php } ?>
    
    <input type="submit" value="Ajouter" />
</form>