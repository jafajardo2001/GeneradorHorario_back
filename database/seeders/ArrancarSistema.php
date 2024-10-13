<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use App\Models\Usuario;
use App\Models\Carrera;
use App\Models\CarreraModel;
use App\Models\NivelModel;
use App\Models\EducacionGlobal;
use App\Models\InstitutoModel;
use App\Models\PeriodoElectivoModel;
use App\Models\RolModel;
use App\Models\JobModel;
use App\Models\JornadaModel;
use App\Models\UsuarioModel;
use App\Models\TituloAcademicoModel;

class ArrancarSistema extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear Título Académico
        TituloAcademicoModel::create([
            "descripcion" => "Ingeniero en sistemas",
            "ip_creacion" => "127.0.0.1",
            "ip_actualizacion" => "127.0.0.1",
            "id_usuario_creador" => 1,
            "fecha_creacion" => Carbon::now(),
            "fecha_actualizacion" => Carbon::now(),
            "estado" => "A"
        ]);

        // Crear Nivel
        NivelModel::create([
            "numero" => "1",
            "nemonico" => "1",
            "termino" => "primero",
            "ip_creacion" => "127.0.0.1",
            "ip_actualizacion" => "127.0.0.1",
            "id_usuario_creador" => 1,
            "id_usuario_actualizo" => 1,
            "fecha_creacion" => Carbon::now(),
            "fecha_actualizacion" => Carbon::now(),
            "estado" => "A"
        ]);

        // Insertar Roles
        RolModel::insert([
            [
                "descripcion" => "sin perfil",
                "ip_creacion" => "127.0.0.1",
                "ip_actualizacion" => "127.0.0.1",
                "id_usuario_creador" => 1,
                "id_usuario_actualizo" => 1,
                "fecha_creacion" => Carbon::now(),
                "fecha_actualizacion" => Carbon::now(),
                "estado" => "E"
            ],
            [
                "descripcion" => "Administrador",
                "ip_creacion" => "127.0.0.1",
                "ip_actualizacion" => "127.0.0.1",
                "id_usuario_creador" => 1,
                "id_usuario_actualizo" => 1,
                "fecha_creacion" => Carbon::now(),
                "fecha_actualizacion" => Carbon::now(),
                "estado" => "A"
            ]
        ]);
        
        // Crear Job (Tiempo Completo)
        JobModel::create([
            "descripcion" => "Tiempo Completo",
            "ip_creacion" => "127.0.0.1",
            "ip_actualizacion" => "127.0.0.1",
            "id_usuario_creador" => 1,
            "id_usuario_actualizo" => 1,
            "fecha_creacion" => Carbon::now(),
            "fecha_actualizacion" => Carbon::now(),
            "estado" => "A"
        ]);

        // Insertar Jornada
        JornadaModel::insert([
            [
                "descripcion" => "Matutina",
                "ip_creacion" => "127.0.0.1",
                "ip_actualizacion" => "127.0.0.1",
                "id_usuario_creador" => 1,
                "id_usuario_actualizo" => 1,
                "fecha_creacion" => Carbon::now(),
                "fecha_actualizacion" => Carbon::now(),
                "estado" => "A"
            ],
        ]);

        // Crear Usuario Admin si existe el rol con ID 1
        if (RolModel::find(1)) {
            UsuarioModel::create([
                "cedula" => "0987654321",
                "nombres" => "Admin Admin",
                "apellidos" => "Administrador Administrador",
                "correo" => "admin@admin.com",
                "telefono" => "0987654321",
                "usuario" => "Admin",
                "clave" => bcrypt("_Admin#2023*"),
                "id_rol" => 2,
                "id_titulo_academico" => 1,
                "id_job" => 1,
                "ip_creacion" => "127.0.0.1",
                "ip_actualizacion" => "127.0.0.1",
                "id_usuario_creador" => 1,
                "id_usuario_actualizo" => 1,
                "fecha_creacion" => Carbon::now(),
                "fecha_actualizacion" => Carbon::now(),
                "estado" => "A"
            ]);
        }

        // Crear Periodo Electivo
        PeriodoElectivoModel::create([
            "anio" => "2024", // Corregido de 'anios' a 'anio'
            "periodo" => "Periodo 1",
            "ip_creacion" => "127.0.0.1",
            "ip_actualizacion" => "127.0.0.1",
            "id_usuario_creador" => 1,
            "id_usuario_actualizo" => 1,
            "fecha_creacion" => Carbon::now(),
            "fecha_actualizacion" => Carbon::now(),
            "estado" => "A"
        ]);
        
        // Crear Carrera
        CarreraModel::create([
            "nombre" => "Desarrollo de software",
            "ip_creacion" => "127.0.0.1",
            "ip_actualizacion" => "127.0.0.1",
            "id_usuario_creador" => 1,
            "id_jornada" => 1,
            "id_usuario_actualizo" => 1,
            "fecha_creacion" => Carbon::now(),
            "fecha_actualizacion" => Carbon::now(),
            "estado" => "A"
        ]);
        
        // Crear Instituto
        InstitutoModel::create([
            "nombre" => "Instituto tecnologico de Guayaquil",
            "codigo" => "ISTG-124",
            "ubicacion" => "",
            "descripcion" => "", // Corregido de 'Descripcion' a 'descripcion'
            "nemonico" => "ISTG",
            "nivel_educacion" => "INSTITUTO",
            "jornada" => "NOCTURNA",
            "foto_educacion" => null,
            "ip_creacion" => "127.0.0.1",
            "ip_actualizacion" => "127.0.0.1",
            "id_usuario_creador" => 1,
            "id_usuario_actualizo" => 1,
            "fecha_creacion" => Carbon::now(),
            "fecha_actualizacion" => Carbon::now(),
            "estado" => "A"
        ]);
    }
}
