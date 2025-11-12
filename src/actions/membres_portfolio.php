<?php

require_once __DIR__ . "/../template/layout.php";

// Formulaire pour ajouter membre
if(isset($_POST["ajout_membre"])) {
        $email = $_POST["ajout_membre_email"];
        $acces = intval($_POST["ajout_membre_acces"]);

        if(empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            render_page("membres_portfolio.php", ["title"=>"Membres du portfolio", "erreur_ajout_membre_email"=>"Adresse email invalide", "portfolio_id"=>$portfolio_id, "ajout_membre_email"=>$email]);
            die();
        }

        // Transaction!!
        // 1) Vérifier si l'utilisateur existe en BDD.
        // 2) Mettre une erreur si existe pas? Envoyer une invitation par mail ?
        // 3) Ajouter l'enregistrement dans Membre_Portfolio

        header("Location: /portfolio/$portfolio_id/membres");
        die();
}

// Formulaire pour transférer propriété
if (isset($_POST["transfer"])) {
    $email = $_POST["transfer_email"];
    $garder_acces = isset($_POST["transfer_garder_acces"]);
    $acces = intval($_POST["transfer_garder_acces_niveau"]);

    if(empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        render_page("membres_portfolio.php", ["title"=>"Membres du portfolio", "erreur_transfer_email"=>"Adresse email invalide", "portfolio_id"=>$portfolio_id, "transfer_email"=>$email]);
        die();
    }

    // Transaction!!
    // 1) Vérifier si l'utilisateur existe en BDD.
    // 2) Mettre une erreur si existe pas? Envoyer une invitation par mail ?
    // 3) Ajouter l'enregistrement dans Membre_Portfolio
    // 4) Supprimer OU modifier Auth::user() dans Membre_Portfolio (en fonction de garder_acces ou pas)

    header("Location: /portfolio/$portfolio_id/membres");
    die();
}

// Une erreur est survenue qqpart donc on retourne silencieusement
header("Location: /portfolio/$portfolio_id/membres");
die();