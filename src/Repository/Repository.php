<?php

namespace App\Repository;

use App\Database;
use Exception;
use PDO;

abstract class Repository
{
    protected $database;

    private static $instances = [];

    protected function __construct()
    {
        $this->database = Database::getInstance()->connect();
    }

    public static function getInstance(): static
    {
        $subclass = static::class;

        if (!isset(self::$instances[$subclass])) {
            self::$instances[$subclass] = new static();
        }

        return self::$instances[$subclass];
    }

    public function beginTransaction(): void
    {
        $this->database->beginTransaction();
    }

    public function commit(): void
    {
        $this->database->commit();
    }

    public function rollback(): void
    {
        $this->database->rollBack();
    }

    protected function __clone()
    {
    }

    public function __wakeup()
    {
        throw new Exception("Cannot unserialize a singleton.");
    }
}