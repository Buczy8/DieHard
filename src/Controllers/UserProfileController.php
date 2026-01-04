<?php

namespace App\Controllers;

use App\Annotation\RequireLogin;

class UserProfileController extends AppController
{
    #[RequireLogin]
    public function index()
    {
        $this->render('profile');
    }
}