<?php 
namespace App\Models;

use CodeIgniter\Model;

class SeccionModel extends Model{
    protected $table      = 'secciones';
    
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType     = 'array';
    
    protected $useSoftDeletes = false;

    protected $allowedFields = ['materia_id', 'profesor_id','codigo_seccion',"periodo_academico"];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

}