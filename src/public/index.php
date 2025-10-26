<?php

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../template/layout.php';


$conn = Database::create();

$stmt = $conn->execute("SELECT email, nom FROM Utilisateur");

/*while ($row = $stmt->fetch()) {
    echo "Mail:".$row["email"]."<br>";
    echo "Nom:".$row["nom"]."<br>";
}*/

render_page("login.php", ["title"=>"Finance App - Se connecter"]);