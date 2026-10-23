<?php
namespace App\Controllers;

class Home extends BaseController
{
    public function index()
    {
        return view('Templates/header', ['css_extra' => 'dashboard.css'])
            . view('Dashboard/menuPrincipal')
            . view('Templates/footer');
    }
}
