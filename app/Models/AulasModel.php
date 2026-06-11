<?php

namespace App\Models;

use CodeIgniter\Model;

class AulasModel extends Model
{
    protected $table            = 'aulas';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    
    // 🛡️ ESCUDO OBLIGATORIO: Campos que CodeIgniter tiene permitido registrar
    protected $allowedFields    = ['nombre_json', 'alias_pdf', 'coord_x', 'coord_y'];

    protected $useTimestamps    = false;
}