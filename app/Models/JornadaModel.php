<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JornadaModel extends Model
{
    use HasFactory;
    protected $table = 'jornada';
    protected $primaryKey = 'id_jornada';
    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = 'fecha_actualizacion';
    protected $fillable = [
        'descripcion',
        'ip_creacion',
        'ip_actualizacion',
        'id_usuario_creador',
        'id_usuario_actualizo',
        'fecha_creacion',
        'fecha_actualizacion',
        'estado'
    ];
    public function usuariosCarreras()
    {
        return $this->belongsToMany(UsuarioModel::class, 'usuario_carrera_jornada', 'id_jornada', 'id_usuario')
                    ->withPivot('id_carrera')
                    ->withTimestamps();
    }
}
