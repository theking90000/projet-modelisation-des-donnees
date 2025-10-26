<?php

require_once __DIR__ . "/../template/layout.php";

// Gère le POST /login avec email/password

$email = $_POST["email"];
$password = $_POST["password"];

if(empty($email) || empty($password)
    || !filter_var($email, FILTER_VALIDATE_EMAIL)) {

        render_page("login.php", ["title"=>"Finance App - Se connecter",
        "loginError"=>"Veuillez entrer une adresse email valide et un mot de passe"]);

        die();
}

$auth = new Auth();

if(!$auth->login($email, $password)) {
    render_page("login.php", ["title"=>"Finance App - Se connecter", "loginError"=>"Connexion impossible - Mauvais email ou mauvais mot de passe."]);
}

header("Location: /");
die();
