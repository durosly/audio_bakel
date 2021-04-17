<?php

class Database {
    //connection properties
    private $host = "your host";
    private $user = "your username";
    private $password = "your password";
    private $dbname = "audio";
    private $conn = null;

    //attempt connection
    public function __construct() {
        $dsn = "mysql:host={$this->host};dbname={$this->dbname};";
        try {
            $this->conn = new PDO($dsn, $this->user, $this->password);
        } catch (PDOException $e) {
            echo "Failed to connect to database. <br/>";
        }
    }

    //get connection variable
    public function get_connection() {
        return $this->conn;
    }

    //clear connection
    public function __destruct() {
        $this->conn = null;
    }
}