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
        'estado',
        'id_rol',
        'id_titulo_academico'
    ];
    public function creador()
    {
        return $this->belongsTo(UsuarioModel::class, 'id_usuario_creador');
    }
}
