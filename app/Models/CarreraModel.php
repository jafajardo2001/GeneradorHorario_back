<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarreraModel extends Model
{
    use HasFactory;
    protected $table = "carreras";
    protected $primaryKey = "id_carrera";
    
    const CREATED_AT = "fecha_creacion";
    const UPDATED_AT = "fecha_actualizacion";
    
    protected $fillable = ['nombre',
    'id_jornada','ip_creacion',
        'ip_actualizacion',
        'id_usuario_creador',
        'id_usuario_actualizo',
        'fecha_creacion',
        'fecha_actualizacion',];

    public function jornada()
    {
        return $this->belongsTo(JornadaModel::class, 'id_jornada', 'id_jornada');
    }

    public function usuarios()
    {
        return $this->belongsToMany(UsuarioModel::class, 'usuario_carrera_jornada', 'id_carrera', 'id_usuario')
                    ->withPivot('id_jornada') // Incluye id_jornada desde la tabla pivote
                    ; // Si añadiste timestamps en la tabla pivote
    }
    
}
