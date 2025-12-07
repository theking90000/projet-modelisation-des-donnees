<?php
require_once __DIR__ . "/../lib/auth.php";
require_once __DIR__ . "/../lib/db.php";

// Ensure only POST requests are processed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /portfolio/$portfolio_id/parametres");
    die();
}

$action = $_POST['action'] ?? '';
$db = Database::instance();

try {
    switch ($action) {
        // --- ADD MEMBER ---
        case 'add':
            $email = $_POST['email'];
            $niveau = intval($_POST['niveau']);

            // 1. Check if user exists
            $userExists = $db->execute("SELECT email FROM Utilisateur WHERE email = ?", [$email])->fetch();
            if (!$userExists) {
                // In a real app, you might send an invite email here. 
                // For now, we just redirect back (maybe add an error query param).
                 header("Location: /portfolio/$portfolio_id/parametres?error=UserNotFound");
                 die();
            }

            // 2. Insert (Ignore if already exists to prevent crashes)
            // Using REPLACE or Checking existence first is safer, usually INSERT IGNORE is ok for simple cases
            $db->execute("INSERT IGNORE INTO Membre_Portfolio (email, id_portfolio, niveau_acces) VALUES (?, ?, ?)", 
                [$email, $portfolio_id, $niveau]);
            break;

        // --- UPDATE ACCESS LEVEL ---
        case 'update':
            $email = $_POST['email'];
            $niveau = intval($_POST['niveau']);
            
            // Prevent changing own access via this form (security check)
            if ($email === Auth::user()) break;

            $db->execute("UPDATE Membre_Portfolio SET niveau_acces = ? WHERE email = ? AND id_portfolio = ?", 
                [$niveau, $email, $portfolio_id]);
            break;

        // --- REMOVE MEMBER ---
        case 'delete':
            $email = $_POST['email'];
            
            // Prevent deleting self (Owner must transfer ownership instead)
            if ($email === Auth::user()) break;

            $db->execute("DELETE FROM Membre_Portfolio WHERE email = ? AND id_portfolio = ?", 
                [$email, $portfolio_id]);
            break;

        // --- TRANSFER OWNERSHIP ---
        case 'transfer':
            $newOwnerEmail = $_POST['new_owner_email'];
            $myNewRole = intval($_POST['my_new_role']);
            $currentUser = Auth::user();

            $db->beginTransaction();

            // 1. Promote new owner to Level 3
            $db->execute("UPDATE Membre_Portfolio SET niveau_acces = 3 WHERE email = ? AND id_portfolio = ?", 
                [$newOwnerEmail, $portfolio_id]);

            // 2. Demote current owner (me)
            if ($myNewRole > 0) {
                $db->execute("UPDATE Membre_Portfolio SET niveau_acces = ? WHERE email = ? AND id_portfolio = ?", 
                    [$myNewRole, $currentUser, $portfolio_id]);
            } else {
                // If role is 0, user chose to leave
                $db->execute("DELETE FROM Membre_Portfolio WHERE email = ? AND id_portfolio = ?", 
                    [$currentUser, $portfolio_id]);
            }

            $db->commit();
            
            // Since we are no longer owner, we redirect to the portfolio home
            header("Location: /portfolio/$portfolio_id");
            die();
            break;
    }

} catch (Exception $e) {
    // Log error or handle it
    if ($db->inTransaction()) $db->rollBack();
}

// Default redirect back to parameters
header("Location: /portfolio/$portfolio_id/parametres");
die();