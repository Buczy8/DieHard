<?php
namespace App\Controllers;
use App\Annotation\RequireLogin;
use App\Controllers\AppController;

class DashboardController extends AppController {
    #[RequireLogin]
    public function index()
    {
        $this->render('dashboard');
    }
}