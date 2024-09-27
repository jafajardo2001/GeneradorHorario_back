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
    
    protected $fillable = [
        'nombre',
        'id_jornada'
    ];


    public function usuariosJornadas()
    {
        return $this->belongsToMany(UsuarioModel::class, 'usuario_carrera_jornada', 'id_carrera', 'id_usuario')
                    ->withPivot('id_jornada')
                    ->withTimestamps();
    }
    
}
