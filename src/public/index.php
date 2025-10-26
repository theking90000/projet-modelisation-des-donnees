<?php

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/router.php';

$router = new Router();

$router->get('/login', 
    create_render_handle("login.php", ["title"=>"Finance App - Se connecter"]));

$router->post('/login', create_handler('actions/login.php'));

$router->notFound(create_render_handle("404.php", ["title"=>"Finance App - Erreur 404"]));

$router->group("", function($router) {
  $router->get("/", function() {
    echo"ok";
    echo (new Auth())->user_id();
  });
}, ['AuthMiddleware']);

// $conn = Database::create();

// $stmt = $conn->execute("SELECT email, nom FROM Utilisateur");

/*while ($row = $stmt->fetch()) {
    echo "Mail:".$row["email"]."<br>";
    echo "Nom:".$row["nom"]."<br>";
}*/


$router->dispatch(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), $_SERVER['REQUEST_METHOD']);