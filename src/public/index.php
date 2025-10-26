<?php

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/router.php';

require_once __DIR__ . '/../template/layout.php';

$router = new Router();

$router->get('/login', function() {
    render_page( "login.php", ["title"=>"Finance App - Se connecter"]);
});

$router->notFound(create_render_handle("404.php", ["title"=>"Finance App - Erreur 404"]));

// $conn = Database::create();

// $stmt = $conn->execute("SELECT email, nom FROM Utilisateur");

/*while ($row = $stmt->fetch()) {
    echo "Mail:".$row["email"]."<br>";
    echo "Nom:".$row["nom"]."<br>";
}*/


$router->dispatch(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), $_SERVER['REQUEST_METHOD']);