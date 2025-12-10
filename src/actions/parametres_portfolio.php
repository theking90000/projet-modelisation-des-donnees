<?php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';

// Access Control is handled by Middleware in index.php (CheckPortfolioOwner),
// so we are guaranteed to be the owner here.

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($nom)) {
        $erreur_nom = "Le nom ne peut pas être vide.";
        // Re-render the page with the error
        require __DIR__ . '/../pages/parametres_portfolio.php'; 
        return;
    }

    try {
        // UPDATE both Name and Description
        Database::instance()->execute(
            "UPDATE Portfolio SET nom = ?, description = ? WHERE id = ?", 
            [$nom, $description, $portfolio_id]
        );

        // Redirect back with a success flag
        header("Location: /portfolio/$portfolio_id/parametres?success=1");
        exit();

    } catch (Exception $e) {
        // In case of SQL error
        $erreur_nom = "Erreur lors de la mise à jour : " . $e->getMessage();
        require __DIR__ . '/../pages/parametres_portfolio.php';
    }
} else {
    // If someone tries to access this action via GET, redirect them to the view
    header("Location: /portfolio/$portfolio_id/parametres");
    exit();
}