<?php
namespace App\Models;
use CodeIgniter\Model;

class UsuarioModel extends Model
{
    protected $table      = 'usuarios';
    protected $primaryKey = 'id';
    
    protected $allowedFields = [
        'primer_nombre', 'segundo_nombre', 'apellidos', 
        'nombre_usuario', 'correo', 'telefono', 
        'contrasena', 'rol', 'estado_verificacion', 'ruta_carnet',
        'fecha_registro'
    ];
}
