<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class UsuarioModel extends Model
{
    use HasFactory,Notifiable,HasApiTokens;

    protected $table = 'usuarios';
    protected $primaryKey = 'id_usuario';

    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = 'fecha_actualizacion';

    protected $fillable = [
        'cedula',
        'nombres',
        'apellidos',
        "id_usuario_creador",
        "ip_actualizacion",
        "fecha_actualizacion",
        'correo',
        'telefono',
        'id_rol',
        'id_job',
        'estado',
        'id_titulo_academico'
        

    ];
    public function tituloAcademico()
    {
        return $this->belongsTo(TituloAcademicoModel::class, 'id_titulo_academico');
        
    }
    public function job()
    {
        return $this->belongsTo(JobModel::class, 'id_job', 'id_job');
    }
    public function carrera()
    {
        return $this->belongsTo(CarreraModel::class, 'id_carrera', 'id_carrera');
    }
    public function carreras()
    {
        return $this->belongsToMany(CarreraModel::class, 'usuario_carrera_jornada', 'id_usuario', 'id_carrera',)
                    ->withPivot('id_jornada') // Incluye id_jornada desde la tabla pivote
                    ; // Si añadiste timestamps en la tabla pivote
    }
    public function jornada()
    {
        return $this->belongsTo(JornadaModel::class, 'id_jornada', 'id_jornada');
    }
}
