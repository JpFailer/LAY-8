<?php

namespace App\Controllers;

/**
 * Controlador de compatibilidad para rutas /admin
 */
class Admin extends AdminController
{
    public function index()
    {
        return parent::index();
    }

    public function getIndex()
    {
        return parent::index();
    }
}
