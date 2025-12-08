<?php
require_once __DIR__ . "/../lib/auth.php";
require_once __DIR__ . "/../lib/db.php";
require_once __DIR__ . "/../template/layout.php";

// 1. Validation
$nom = $_POST['nom'] ?? null;
$devise = $_POST['devise'] ?? null;
$description = $_POST['description'] ?? '';

$errors = [];
$value = [
    "nom" => $nom,
    "description" => $description,
    "devise" => $devise
];

if (!$nom) {
    $errors["nom"] = "Le nom du portfolio est requis.";
} else if (strlen($nom) < 3) {
    $errors["nom"] = "Le nom du portfolio doit faire au moins 3 caractères.";
}

if (!$devise) {
    $errors["devise"] = "La devise est requise.";
}

$devise = Database::instance()->execute("SELECT code, symbole FROM Devise WHERE code = ?", [$devise])->fetch();

if (!$devise) {
    $errors["devise"] = "La devise sélectionnée est invalide.";
}

if ($devise) {
    $value["nom_devise"] = $devise['code'] . "(" . $devise["symbole"] . ")"  ?? null;
}

if (!empty($errors)) {
    // Redirect back to home with errors (simplified)
    render_page("home.php", ["errors" => $errors, "title" => "Finance App", "value" => $value]);
    die();
}

try {
    $db = Database::instance();
    $db->beginTransaction();

    // 2. Insert the Portfolio
    // Note: 'date_creation' in your schema might default to NOW(), otherwise handle it here
    $stmt = $db->prepare("INSERT INTO Portfolio (nom, description, code_devise, date_creation) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$nom, $description, $devise['code']]);
    
    $portfolio_id = $db->lastInsertId();

    // 3. Link the User to the Portfolio (Owner Access = 3)
    $stmt = $db->prepare("INSERT INTO Membre_Portfolio (email, id_portfolio, niveau_acces) VALUES (?, ?, 3)");
    $stmt->execute([Auth::user(), $portfolio_id]);

    $db->commit();

    // 4. Redirect to the new portfolio
    header("Location: /portfolio/$portfolio_id");
    die();

} catch (Exception $e) {
    $db->rollBack();
    // In a real app, you would flash an error message to the session here
    $errors["general"] = "Une erreur est survenue lors de la création du portfolio." . $e->getMessage();
    render_page("home.php", ["errors" => $errors, "title" => "Finance App", "value" => $value  ]);
    die();
}