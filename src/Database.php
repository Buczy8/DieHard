<?php

namespace App;

use AllowDynamicProperties;
use PDO;
use PDOException;

#[AllowDynamicProperties]
class Database
{
    private $username;
    private $password;
    private $host;
    private $database;
    private $port;
    private $conn;

    private static $instance = null;

    private function __construct()
    {
        $this->username = $_ENV['DB_USER'];
        $this->password = $_ENV['DB_PASSWORD'];
        $this->host = $_ENV['DB_HOST'];
        $this->database = $_ENV['DB_NAME'];
        $this->port = $_ENV['DB_PORT'];

    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }

        return self::$instance;
    }

    public function connect()
    {
        if ($this->conn !== null) {
            return $this->conn;
        }

        try {
            $this->conn = new PDO(
                "pgsql:host=$this->host;port=$this->port;dbname=$this->database",
                $this->username,
                $this->password,
                ["sslmode" => "prefer"]
            );

            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $this->conn;
        } catch (PDOException $e) {
            throw new PDOException("Connection failed: " . $e->getMessage());
        }
    }

    public function disconnect(): void
    {
        $this->conn = null;
    }

    private function __clone()
    {
    }

    public function __wakeup()
    {
    }
}