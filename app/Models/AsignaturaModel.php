<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AsignaturaModel extends Model
{
    use HasFactory;
    protected $table = 'materias';
    protected $primaryKey = 'id_materia';
    
    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = 'fecha_actualizacion';

    // Asegúrate de que 'estado' esté en el array $fillable para permitir la actualización
    protected $fillable = ['descripcion', 'estado', 'id_usuario_creador', 'ip_actualizacion', 'fecha_actualizacion',"id_nivel"];
    public function nivel()
    {
        return $this->belongsTo(NivelModel::class, 'id_nivel', 'id_nivel');
    }
    
}
