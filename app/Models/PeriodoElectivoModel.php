<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeriodoElectivoModel extends Model
{
    use HasFactory;

    protected $table = 'periodo_electivo';
    protected $primaryKey = 'id_periodo';

    // Campos que pueden ser llenados en el modelo
    protected $fillable = [
        'anio',  // Corregido de 'año' a 'anio'
        'periodo', 
        'ip_creacion', 
        'ip_actualizacion', 
        'id_usuario_creador', 
        'id_usuario_actualizo', 
        'fecha_creacion', 
        'fecha_actualizacion', 
        'estado'
    ];

    // Constantes para manejar los timestamps personalizados
    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = 'fecha_actualizacion';
}
