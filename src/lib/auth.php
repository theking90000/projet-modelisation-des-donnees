<?php

require_once __DIR__ . "/db.php";

class Auth {
    private Database $db;

    function __construct() {
        session_start();
        $this->db = Database::instance();
    }

    public function user_id() {
        return isset( $_SESSION["user_id"] ) ? $_SESSION["user_id"] : null;
    }

    public function user_authenticated() {
        return $this->user_id() !== null;
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

        $_SESSION["user_id"] = $row["email"];
        return true;
    }

    public function logout() {
        unset($_SESSION["user_id"]);
    }

    private static function hash_password ($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    private static function verify_password ($password, $hash) {
        return password_verify($password, $hash);
    }

}

class AuthMiddleware {
    protected Auth $auth;

    public function __construct() {
        $this->auth = new Auth();
    }

    public function handle() {
        if (!$this->auth->user_authenticated()) {
            http_response_code(401);
            header("Location: /login");
            die(); // on sait jamais
           // return false;
        }
        return true;
    }
}

class WithoutAuthMiddleware extends AuthMiddleware {
    public function handle() {
        if($this->auth->user_authenticated()) {
            http_response_code(401);
            header("Location: /");
            die();
        }
        return true;
    }
}
