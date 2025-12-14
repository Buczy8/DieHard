<?php

namespace App\Controllers;
class SecurityController extends AppController
{
    public function login()
    {
        return $this->render('login');
    }

    public function register()
    {
        return $this->render('register');
    }
}