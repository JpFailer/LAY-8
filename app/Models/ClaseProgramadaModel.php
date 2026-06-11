<?php 
namespace App\Models;

use CodeIgniter\Model;

class ClaseProgramadaModel extends Model{
    protected $table      = 'clases_programadas';

    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType     = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = ['seccion_id', 'aula', 'dia_semana', 'hora_inicio', 'hora_fin'];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;
}