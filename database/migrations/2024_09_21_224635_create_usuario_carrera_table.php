<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuario_carrera_jornada', function (Blueprint $table) {
            $table->id(); // O puedes especificar un nombre si es necesario
            $table->unsignedBigInteger('id_usuario'); // Columna para la clave foránea
            $table->unsignedBigInteger('id_carrera'); // Columna para la clave foránea
            $table->unsignedBigInteger('id_jornada'); // Columna para la clave foránea

            // Definir las claves foráneas
            $table->foreign('id_usuario')->references('id_usuario')->on('usuarios')->onDelete('cascade');
            $table->foreign('id_jornada')->references('id_jornada')->on('jornada')->onDelete('cascade');

            
            $table->foreign('id_carrera')->references('id_carrera')->on('carreras')->onDelete('cascade');
            
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('usuario_carrera_jornada');
    }
};


