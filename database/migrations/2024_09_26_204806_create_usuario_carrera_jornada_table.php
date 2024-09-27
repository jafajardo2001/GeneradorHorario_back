<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('usuario_carrera_jornada', function (Blueprint $table) {
            $table->id(); // Identificador de la tabla
            $table->unsignedBigInteger('id_usuario'); // Relación con usuarios
            $table->unsignedBigInteger('id_carrera'); // Relación con carreras
            $table->unsignedBigInteger('id_jornada'); // Relación con jornadas

            // Definir claves foráneas
            $table->foreign('id_usuario')->references('id_usuario')->on('usuarios')->onDelete('cascade');
            $table->foreign('id_carrera')->references('id_carrera')->on('carreras')->onDelete('cascade');
            $table->foreign('id_jornada')->references('id_jornada')->on('jornada')->onDelete('cascade');

            // Timestamps (opcional, si quieres seguir el rastro de cuándo fue creado/actualizado)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuario_carrera_jornada');
    }
};
