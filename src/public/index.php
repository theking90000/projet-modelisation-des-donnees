<?php

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/router.php';

$router = new Router();



$router->group("", function ($router) {
  $router->get('/login', create_render_handle("login.php", ["title"=>"Finance App - Se connecter"]));

  $router->post('/login', create_handler('actions/login.php'));
}, ['WithoutAuthMiddleware']);

$router->get('/logout', create_handler('actions/logout.php'));

$router->notFound(create_render_handle("404.php", ["title"=>"Finance App - Erreur 404"]));

$router->group("", function($router) {
  $router->get("/", create_render_handle("home.php", ["title"=>"Finance App"]));
}, ['AuthMiddleware']);

$router->group("/portfolio/{portfolio_id}", function ($router) {
    $router->get("", create_render_handle("portfolio.php", ["title"=>"Mon portfolio"]));

    $router->group("", function ($router) {
        $router->get("/parametres", create_render_handle("parametres_portfolio.php", ["title"=>"Paramètres du portfolio"]));
        $router->post("/parametres", create_handler('actions/parametres_portfolio.php'));

        $router->get("/membres", create_render_handle("membres_portfolio.php", ["title"=>"Membres du portfolio"]));
        $router->post("/membres", create_handler("actions/membres_portfolio.php"));
    }, ['CheckPortfolioOwner']);
}, ['AuthMiddleware', 'CheckPortfolioAccess']);

// $conn = Database::create();

// $stmt = $conn->execute("SELECT email, nom FROM Utilisateur");

/*while ($row = $stmt->fetch()) {
    echo "Mail:".$row["email"]."<br>";
    echo "Nom:".$row["nom"]."<br>";
}*/


$router->dispatch(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), $_SERVER['REQUEST_METHOD']);