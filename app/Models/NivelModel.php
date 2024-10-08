<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NivelModel extends Model
{
    use HasFactory;
    protected $table = 'nivel';
    protected $primaryKey = 'id_nivel';
    protected $fillable = ['numero','nemonico','termino','fecha_actualizacion','estado'];
    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = 'fecha_actualizacion';
    
}
