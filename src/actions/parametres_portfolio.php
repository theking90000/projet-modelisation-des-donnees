<?php

require_once __DIR__ . "/../template/layout.php";

$nom = $_POST["nom"];

if(empty($nom)) {
        render_page("parametres_portfolio.php", ["title"=>"Paramètres du portfolio", "erreur_nom"=>"Le nom ne peut pas être vide", "portfolio_id"=>$portfolio_id]);

        die();
}

if(strlen($nom) < 3) {
        render_page("parametres_portfolio.php", ["title"=>"Paramètres du portfolio", "erreur_nom"=>"Le nom doit faire au moins 3 caractères", "nom"=>$nom, "portfolio_id"=>$portfolio_id]);

        die();
}

// TODO: requete rapport
$stmt = Database::instance()->execute("UPDATE Portfolio SET Portfolio.nom = ? WHERE Portfolio.id = ?", [$nom, $portfolio_id]);

header("Location: /portfolio/$portfolio_id/parametres");
die();