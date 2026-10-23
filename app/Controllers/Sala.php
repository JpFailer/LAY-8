<?php

namespace App\Controllers;

class Sala extends BaseController
{
    public function crear()
    {
        return view('Templates/header')
            . '<main class="dashboard-contenedor" style="padding: 40px; text-align: center;">'
            . '<h2>Crear Sala Virtual de Aprendizaje</h2>'
            . '<p>Módulo para profesores aprobados. Próximamente podrás programar tus clases y salas aquí.</p>'
            . '<br><a href="' . base_url('dashboard') . '" class="btn-primary" style="display:inline-block; padding: 10px 20px; text-decoration:none; color:white; background:#1e3a8a; border-radius:6px;">Volver al Dashboard</a>'
            . '</main>'
            . view('Templates/footer');
    }
}
