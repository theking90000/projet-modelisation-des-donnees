<?php
require_once __DIR__ . "/../lib/auth.php";
require_once __DIR__ . "/../lib/db.php";

// 1. Validation
$nom = $_POST['nom'] ?? null;
$devise = $_POST['devise'] ?? null;
$description = $_POST['description'] ?? '';

if (!$nom || !$devise) {
    // Redirect back to home with error (simplified)
    header("Location: /");
    die();
}

try {
    $db = Database::instance();
    $db->beginTransaction();

    // 2. Insert the Portfolio
    // Note: 'date_creation' in your schema might default to NOW(), otherwise handle it here
    $stmt = $db->prepare("INSERT INTO Portfolio (nom, description, code_devise, date_creation) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$nom, $description, $devise]);
    
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
    header("Location: /");
    die();
}