<?php

namespace App\Controllers;
use App\Annotation\RequireLogin;
class DiceGameController extends AppController {
    #[RequireLogin]
    public function index() {
        return $this->render('dicegame');
    }
}