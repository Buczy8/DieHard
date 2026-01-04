<?php

namespace App\Controllers;

use App\Annotation\RequireLogin;

class RulesController extends AppController
{
    #[RequireLogin]
    public function index()
    {
        $this->render('rules');
    }
}