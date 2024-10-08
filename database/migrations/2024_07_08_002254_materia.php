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
        Schema::create('materias', function (Blueprint $table) {
            $table->id('id_materia'); // Bigint primary key
            $table->string('descripcion', 255);
            $table->unsignedBigInteger('id_nivel');

            $table->string('ip_creacion', 45)->nullable();
            $table->string('ip_actualizacion', 45);
            $table->integer('id_usuario_creador');
            $table->integer('id_usuario_actualizo');
            $table->date('fecha_creacion');
            $table->date('fecha_actualizacion');
            $table->enum('estado', ['A', 'E', 'I'])->default('A');

            // Foreign key constraint
            $table->foreign('id_nivel')
                ->references('id_nivel')
                ->on('nivel')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
