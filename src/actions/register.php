<?php

require_once __DIR__ . "/../template/layout.php";

// Gère le POST /register avec nom/prénom/email/password/password2

$name = $_POST["name"];
$first_name = $_POST["first_name"];
$email = $_POST["email"];
$password = $_POST["password"];
$password2 = $_POST["password2"];

if(empty($email) || empty($password) || empty($name) || empty($first_name) || empty($password2)) {

        render_page("register.php", ["title"=>"Finance App - Se connecter",
        "error"=>"Veuillez entrer toutes les informations de connexion",
        "email"=>$email, "name"=>$name, "first_name"=>$first_name]);

        die();
}

if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        render_page("register.php", ["title"=>"Finance App - Se connecter",
        "error"=>"Adresse mail invalide",
        "email"=>$email, "name"=>$name, "first_name"=>$first_name]);

        die();
}

if(strlen($name) > 50) {

        render_page("register.php", ["title"=>"Finance App - Se connecter",
        "error"=>"La taille du nom doit être inférieure à 50 caractères",
        "email"=>$email, "name"=>$name, "first_name"=>$first_name]);

        die();
}

if(strlen($first_name) > 50) {

        render_page("register.php", ["title"=>"Finance App - Se connecter",
        "error"=>"La taille du prénom doit être inférieure à 50 caractères",
        "email"=>$email, "name"=>$name, "first_name"=>$first_name]);

        die();
}

if(strlen($email) > 100) {

        render_page("register.php", ["title"=>"Finance App - Se connecter",
        "error"=>"La taille du nom doit être inférieure à 50 caractères",
        "email"=>$email, "name"=>$name, "first_name"=>$first_name]);

        die();
}

if($password!=$password2) {

        render_page("register.php", ["title"=>"Finance App - Se connecter",
        "error"=>"Les mots de passes ne sont pas identiques",
        "email"=>$email, "name"=>$name, "first_name"=>$first_name]);

        die();
}

$auth = new Auth();

if($auth->exists($email)) {
    render_page("register.php", ["title"=>"Finance App - Se connecter", "error"=>"Inscription impossible - Cette adresse mail est déjà utilisée.",
    "email"=>$email, "name"=>$name, "first_name"=>$first_name]);
    die();
}

if(!$auth->register($name, $first_name, $email, $password)) {
    render_page("register.php", ["title"=>"Finance App - Se connecter", "error"=>"Inscription impossible - Une erreur est survenue.",
    "email"=>$email, "name"=>$name, "first_name"=>$first_name]);
    die();
}

header("Location: /");
die();
