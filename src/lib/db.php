<?php

require_once __DIR__ . "/db_password.php";

class Database extends PDO
{
    function __construct()
    {
        parent::__construct('mysql:dbname='.DB_NAME.';host='.DB_HOST.';port='.DB_PORT, DB_USER, DB_PASSWORD);
        $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        //$this->setAttribute(PDO::ATTR_STATEMENT_CLASS, array('DBStatement', array($this)));
    }

    static function create(): Database
    {
        try {
            return new Database();
        } catch (PDOException $e) {
            echo "Connection failed". $e->getMessage();
            die(500);
        }
    }

    function execute(string $sql, array $params = []): PDOStatement {
        try {
            $stmt = $this->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            echo "Request failed". $e->getMessage();
            die(500);
        }
    }
}