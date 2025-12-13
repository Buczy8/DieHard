<?php

namespace App\Repository;

use App\Database;

abstract class Repository {
    protected $database;

    public function __construct() {
        $this->database = Database::getInstance()->connect();
    }
}