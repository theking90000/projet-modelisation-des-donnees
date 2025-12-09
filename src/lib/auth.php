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

    public function exists ($email) {
        $req = $this->db->prepare("SELECT 1 FROM Utilisateur WHERE email = ?");
        $req->execute([$email]);

        $row = $req->fetch();

        var_dump($row, !!$row);

        return !!$row;
    }

    public function register ($name, $first_name, $email, $password) {
        try {
            $password = $this->hash_password($password);

            $stmt = $this->db->prepare("INSERT INTO Utilisateur (email, nom, prenom, mot_de_passe, date_creation) VALUES (?, ?, ?, ?, CURRENT_DATE())");
            $stmt->execute([$email, $name, $first_name, $password]);

            $_SESSION[$this->session_key] = $email;
            return true;
        }
        catch (PDOException $e) {
            return false;
        }
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

/**
 * Retourne le niveau d'accès au portfolio ou 0 si pas d'accès
 * Remarque : est appelée souvent par requête:
 * on pourrait rajouter un cache.
 */
function acces_portfolio($portfolio_id) : int {
   $stmt = Database::instance()->execute("SELECT Membre_Portfolio.niveau_acces FROM Portfolio JOIN Membre_Portfolio ON Portfolio.id = Membre_Portfolio.id_portfolio JOIN Utilisateur ON Utilisateur.email = Membre_Portfolio.email WHERE Utilisateur.email = ? AND Portfolio.id = ?", [Auth::user(), $portfolio_id]);


    if($stmt->rowCount() == 0)
        return 0;

    return $stmt->fetch()["niveau_acces"];
}

class CheckPortfolioAccess {
    private int $niveau;

    function __construct(int $niveau = 1) {
        $this->niveau = $niveau;
    }

    public function handle($params) {
        $portfolio_id = intval($params["portfolio_id"]);

        if(acces_portfolio($portfolio_id) < $this->niveau) {
            http_response_code(401);
            header("Location: /");
            die();
        }

        return true;
    }
}

class CheckPortfolioWrite extends CheckPortfolioAccess {
    function __construct () {
        parent::__construct(2);
    }
}

class CheckPortfolioOwner extends CheckPortfolioAccess {
    function __construct () {
        parent::__construct(3);
    }
}
