<?php
namespace App\Controllers;

class Home extends BaseController
{
    public function index()
    {
        return view('Dashboard/feed');
    }

    public function ajustes()
    {
        return view('Dashboard/ajustes_view');
    }

    public function terminos()
    {
        return view('Home/terminos_view');
    }
}
