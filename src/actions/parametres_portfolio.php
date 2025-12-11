<?php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';

// Access Control is handled by Middleware (CheckPortfolioOwner)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'update';
    $db = Database::instance();

    // ... (Your existing Update Logic) ...
    if ($action === 'update') {
        // ... (Keep your update logic here) ...
    }

    // --- CASE 2: DELETE PORTFOLIO ---
    elseif ($action === 'delete_portfolio') {
        try {
            // 1. Check if Portfolio is Empty
            $holdings = $db->execute("
                SELECT SUM(CASE WHEN type = 'achat' THEN quantite WHEN type = 'vente' THEN -quantite ELSE 0 END) as total_qty
                FROM `Transaction`
                WHERE id_portfolio = ?
                GROUP BY isin
                HAVING total_qty > 0.000001
            ", [$portfolio_id])->fetchAll();

            if (!empty($holdings)) {
                // Error: Portfolio not empty -> Redirect back to params
                header("Location: /portfolio/$portfolio_id/parametres?error=not_empty");
                exit();
            }

            // 2. Start Deletion Process
            $db->beginTransaction();

            // A. Delete Transactions
            $db->execute("DELETE FROM `Transaction` WHERE id_portfolio = ?", [$portfolio_id]);

            // B. Delete Members (Foreign Key)
            $db->execute("DELETE FROM Membre_Portfolio WHERE id_portfolio = ?", [$portfolio_id]);

            // C. Delete the Portfolio itself
            $db->execute("DELETE FROM Portfolio WHERE id = ?", [$portfolio_id]);

            $db->commit();

            // 3. CRITICAL: Redirect to Home Page
            // Since the portfolio ID no longer exists, staying on this page causes a crash/404.
            header("Location: /"); 
            exit();

        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            // Fallback error
            header("Location: /portfolio/$portfolio_id/parametres?error=sql_error");
            exit();
        }
    }
} else {
    header("Location: /portfolio/$portfolio_id/parametres");
    exit();
}