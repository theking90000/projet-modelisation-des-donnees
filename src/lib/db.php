<?php

require_once __DIR__ . "/db_password.php";

class Database extends PDO
{
    private static $instance = null;

    function __construct()
    {
        parent::__construct('mysql:dbname='.DB_NAME.';host='.DB_HOST.';port='.DB_PORT, DB_USER, DB_PASSWORD);
        $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->setAttribute( PDO::ATTR_EMULATE_PREPARES, false);
        //$this->setAttribute(PDO::ATTR_STATEMENT_CLASS, array('DBStatement', array($this)));
    }

    public static function instance(): Database
    {
        try {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        } catch (PDOException $e) {
            // Log qqpart?
            // echo "Connection failed". $e->getMessage();
            // die(500);
            throw $e;
        }
    }

    function execute(string $sql, array $params = []): PDOStatement {
        try {
            $stmt = $this->prepare($sql);
            $params = array_values(array_filter($params, function($value) {
                return !is_null($value);
            }));
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            //echo "Request failed". $e->getMessage();
            // die(500);
            throw $e;
        }
    }
}