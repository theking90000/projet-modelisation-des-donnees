<?php

require_once __DIR__ . "/db.php";

class Auth {
    private Database $db;
    private string $session_key;

    function __construct($session_key = "user_id") {
        $this->db = Database::instance();
        $this->session_key = $session_key;
    }

    private function session_check() {
        if(session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function get_user() {
        $this->session_check();

        if(!isset($_SESSION[$this->session_key]))
            return null;

        return $_SESSION[$this->session_key];
    }
    
    public static function user() {
        return (new Auth())->get_user();
    }

    public static function authenticated() {
        return self::user() !== null;
    }

    public function login ($email, $password) {
        $stmt = $this->db->prepare("SELECT email, mot_de_passe FROM Utilisateur WHERE email = :email");
        $stmt->execute(["email"=>$email]);
        
        $row = $stmt->fetch();

        if (!$row) {
            return false;
        }

        if(!$this->verify_password($password, $row["mot_de_passe"])) {
            return false;
        }

        $_SESSION[$this->session_key] = $row["email"];
        return true;
    }

    public function logout() {
        $this->session_check();

        unset($_SESSION[$this->session_key]);
    }

    private static function hash_password ($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    private static function verify_password ($password, $hash) {
        return password_verify($password, $hash);
    }

}

class AuthMiddleware {
    public function handle($params) {
        if (!Auth::authenticated()) {
            http_response_code(401);
            header("Location: /login");
            die(); // on sait jamais
           // return false;
        }
        return true;
    }
}

class WithoutAuthMiddleware extends AuthMiddleware {
    public function handle($params) {
        if(Auth::authenticated()) {
            http_response_code(401);
            header("Location: /");
            die();
        }
        return true;
    }
}

class CheckPortfolioAccess {
    public function handle($params) {
        $portfolio_id = intval($params["portfolio_id"]);

        $stmt = Database::instance()->execute("SELECT 1 FROM Portfolio JOIN Membre_Portfolio ON Portfolio.id = Membre_Portfolio.id_portfolio JOIN Utilisateur ON Utilisateur.email = Membre_Portfolio.email WHERE Utilisateur.email = ? AND Portfolio.id = ?", [Auth::user(), $portfolio_id]);

        $access = $stmt->rowCount() > 0;

        if(!$access) {
            http_response_code(401);
            header("Location: /");
            die();
        }

        return true;
    }
}

class CheckPortfolioOwner {
    public function handle($params) {
        $portfolio_id = intval($params["portfolio_id"]);

        $stmt = Database::instance()->execute("SELECT 1 FROM Portfolio JOIN Membre_Portfolio ON Portfolio.id = Membre_Portfolio.id_portfolio JOIN Utilisateur ON Utilisateur.email = Membre_Portfolio.email WHERE Utilisateur.email = ? AND Portfolio.id = ? And Membre_Portfolio.niveau_acces >= 3", [Auth::user(), $portfolio_id]);

        $access = $stmt->rowCount() > 0;

        if(!$access) {
            http_response_code(401);
            header("Location: /");
            die();
        }

        return true;
    }
}
