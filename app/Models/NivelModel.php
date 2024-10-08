<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NivelModel extends Model
{
    use HasFactory;
    protected $table = 'nivel';
    protected $primaryKey = 'id_nivel';
    protected $fillable = ['numero','nemonico','termino','fecha_actualizacion','estado',"ip_creacion","ip_actualizacion", "id_usuario_creador","fecha_creacion","id_usuario_actualizo"];
    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = 'fecha_actualizacion';
    
}
