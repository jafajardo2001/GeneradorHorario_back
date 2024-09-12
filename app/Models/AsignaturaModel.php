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
    // Definir la relación con el modelo CategoriaModel
    

    // Asegúrate de que 'estado' esté en el array $fillable para permitir la actualización
    protected $fillable = ['descripcion', 'id_categoria', 'ip_creacion', 'ip_actualizacion', 'id_usuario_creador', 'id_usuario_actualizo', 'estado'];
    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'id_categoria', 'id_categoria');
    }
}
