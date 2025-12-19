<?php

namespace App\Controllers;
use App\Annotation\RequireLogin;
class DiceGameController extends AppController {
    #[RequireLogin]
    public function game() {
        $this->render('dicegame');
    }
}