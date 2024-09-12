<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use App\Models\Usuario;
use App\Models\Carrera;
use App\Models\CarreraModel;
use App\Models\EducacionGlobal;
use App\Models\InstitutoModel;
use App\Models\PeriodoElectivoModel;
use App\Models\RolModel;
use App\Models\UsuarioModel;
use App\Models\Categoria;
use App\Models\TituloAcademicoModel;
use Carbon\CarbonConverterInterface;

class ArrancarSistema extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        TituloAcademicoModel::create([
            "descripcion" => "Ingeniero en sistemas",
            "ip_creacion" => "127.0.0.1",
            "ip_actualizacion" => "127.0.0.1",
            "id_usuario_creador" => 1,
            "fecha_creacion" => Carbon::now(),
            "fecha_actualizacion" => Carbon::now(),
            "estado" => "A"
        ]);
        RolModel::insert([
            "descripcion" => "Administrador",
            "ip_creacion" => "127.0.0.1",
            "ip_actualizacion" => "127.0.0.1",
            "id_usuario_creador" => 1,
            "id_usuario_actualizo" => 1,
            "fecha_creacion" => Carbon::now(),
            "fecha_actualizacion" => Carbon::now(),
            "estado" => "A"
        ]);
        if(RolModel::find(1)){
            UsuarioModel::create([
                "cedula" => "#########",
                "nombres" => "Admin Admin",
                "apellidos" => "Administrador Administrador",
                "correo" =>"admin@est.istg.edu.ec",
                "telefono" => "0987654321",
                "usuario" => "Admin",
                "clave" => bcrypt("_Admin#2023*"),
                "id_rol" => 1,
                "id_titulo_academico" => 1,
                "ip_creacion" => "127.0.0.1",
                "ip_actualizacion" => "127.0.0.1",
                "id_usuario_creador" => 1,
                "id_usuario_actualizo" => 1,
                "fecha_creacion" => Carbon::now(),
                "fecha_actualizacion" => Carbon::now(),
                "estado" => "A"
            ]);
        }
        PeriodoElectivoModel::create([
            "inicia" => Carbon::now(),
            "termina" => Carbon::now(),
            "ip_creacion" => "127.0.0.1",
            "ip_actualizacion" => "127.0.0.1",
            "id_usuario_creador" => 1,
            "id_usuario_actualizo" => 1,
            "fecha_creacion" => Carbon::now(),
            "fecha_actualizacion" => Carbon::now(),
            "estado" => "A"
        ]);

        CarreraModel::create([
            "nombre" => "Desarrollo de software",
            "ip_creacion" => "127.0.0.1",
            "ip_actualizacion" => "127.0.0.1",
            "id_usuario_creador" => 1,
            "id_usuario_actualizo" => 1,
            "fecha_creacion" => Carbon::now(),
            "fecha_actualizacion" => Carbon::now(),
            "estado" => "A"
        ]);

        InstitutoModel::create([
            "nombre" => "Instituto tecnologico de Guayaquil",
            "codigo" => "ISTG-124",
            "ubicacion" => "",
            "Descripcion" => "",
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
        Categoria::insert([
            ["nombre" => "Docencia"],
            ["nombre" => "Materias"],
            ["nombre" => "Investigación"],
            ["nombre" => "Prácticas Preprofesionales"],
            ["nombre" => "Gestión Administrativa"]
        ]);
    }
}
