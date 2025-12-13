<?php

namespace App\Controllers;
class DiceGameController extends AppController {
    public function index() {
        return $this->render('dicegame');
    }
}