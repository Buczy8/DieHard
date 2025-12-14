<?php

namespace App\Repository;

use App\Database;
use Exception;
use PDO;

abstract class Repository
{
    /** @var PDO */
    protected $database;

    /** @var array*/
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

    protected function __clone()
    {
    }

    public function __wakeup()
    {
        throw new Exception("Cannot unserialize a singleton.");
    }
}