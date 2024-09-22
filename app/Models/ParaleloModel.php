<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParaleloModel extends Model
{
    use HasFactory;
    protected $table = 'paralelo';
    protected $primaryKey = 'id_paralelo';
    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = 'fecha_actualizacion';

    protected $fillable = ['paralelo', 'estado', 'id_usuario_creador', 'ip_actualizacion', 'fecha_actualizacion'];

}
