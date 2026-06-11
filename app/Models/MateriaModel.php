<?php 
namespace App\Models;

use CodeIgniter\Model;

class MateriaModel extends Model{
    protected $table      = 'materias';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType     = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = ['codigo_materia', 'nombre','unidades_credito',"semestre_trayecto"];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

}