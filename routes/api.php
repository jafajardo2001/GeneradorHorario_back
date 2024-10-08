<?php

use App\Http\Controllers\AsignaturaController;
use App\Http\Controllers\AutenticacionController;
use App\Http\Controllers\CarreraController;
use App\Http\Controllers\InstitutoController;
use App\Http\Controllers\TituloAcademicoController;
use App\Http\Controllers\NivelController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\DiasController;
use App\Http\Controllers\DistribucionHorario;
use App\Http\Controllers\ParaleloController;
use App\Http\Controllers\PlanificacionAcademica;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\JornadaController;
use App\Http\Middleware\AutenticacionSistema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/



Route::post('autenticar_sistema_istg',[AutenticacionController::class,'autenticacion']);
//middleware('auth.sanctum')
Route::prefix('istg')->group(
    function () {
        //INSTITUTO
        Route::get("show_data_instituto/", [InstitutoController::class, 'showInstituto']);
        Route::put("update_instituto/", [InstitutoController::class, 'updateInstituto']);
        Route::post("create_instituto/", [InstitutoController::class, 'storeInstituto']);
        Route::delete("delete_instituto/", [InstitutoController::class, 'deleteInstituto']);
        //ASIGNATURA
        Route::get("show_data_asignatura/", [AsignaturaController::class, 'showAsignatura']);
        Route::get("obtener_materias_por_nivel/{idNivel}", [AsignaturaController::class, 'obtenerMateriasPorNivel']);
        Route::put("update_asignatura/{id}", [AsignaturaController::class, 'updateAsignatura']);
        Route::post("create_asignatura/", [AsignaturaController::class, 'storeAsignatura']);
        Route::put("delete_asignatura/{id}", [AsignaturaController::class, 'deleteAsignatura']);
        Route::put("update_estado_asignatura/{id}", [AsignaturaController::class, 'updateEstadoAsignatura']);

        //CARRERA
        Route::get("show_carrera/", [CarreraController::class, 'showCarrera']);
        Route::put("update_carrera/{id}", [CarreraController::class, 'updateCarrera']);
        Route::post("create_carrera/", [CarreraController::class, 'storeCarrera']);
        Route::delete("delete_carrera/{id}", [CarreraController::class, 'deleteCarrera']);
        //NIVEL
        Route::get("show_nivel/", [NivelController::class, 'showNivel']);
        Route::put("update_nivel/{id}", [NivelController::class, 'updateNivel']);
        Route::post("create_nivel/", [NivelController::class, 'storeNivelCarrera']);
        Route::put("delete_nivel/{id}", [NivelController::class, 'deleteNivel']);
        //PARALELO
        Route::get("showParalelo/", [ParaleloController::class, 'showParalelo']);
        Route::put("update_paralelo/{id}", [ParaleloController::class, 'updateParalelo']);
        Route::post("create_paralelo/", [ParaleloController::class, 'storeParalelo']);
        Route::put("delete_paralelo/{id}", [ParaleloController::class, 'deleteParalelo']);
        //USUARIO
        Route::post("create_usuario/", [UsuarioController::class, 'storeUsuarios']);
        Route::put("updateUsuario/{id}", [UsuarioController::class, 'updateUsuarios']);
        // Ruta para eliminar una carrera del usuario
        Route::put('eliminarCarrera/{id}', [UsuarioController::class, 'eliminarCarrera']);
        Route::put("delete_usuario/{userToDelete}", [UsuarioController::class, 'deleteUsuario']);
        Route::get("show_docentes/", [UsuarioController::class, 'showDocentes']);
        Route::get("show_coordinadorc/", [UsuarioController::class, 'showCoordinadorC']);
        Route::get("show_coordinador_a/", [UsuarioController::class, 'showCoordinadorA']);
        Route::get("obtener_docentes_por_carrera/{idCarrera}", [UsuarioController::class, 'obtenerDocentesPorCarrera']);
        Route::get("show_usuario/", [UsuarioController::class, 'showUsuarios']);
        Route::get('/usuarios/{id}', [UsuarioController::class, 'show']);
        //Auth-LoginxUsuario
        Route::post("auth_login/", [UsuarioController::class, 'login']);
        // //Roles
        Route::get("show_roles/", [RolController::class, 'getRoles']);
        Route::post("create_rol/", [RolController::class, 'storeRol']);
        Route::put("delete_rol/{id}", [RolController::class, 'deleteRol']);
        Route::put("update_rol/{id}", [RolController::class, 'updateRol']);

        // //Tiempo
        Route::get("show_jobs/", [JobController::class, 'getJobs']);
        Route::post("create_job/", [JobController::class, 'storeJob']);
        Route::put("update_job/{id}", [JobController::class, 'updateJob']);
        Route::put("delete_job/{id}", [JobController::class, 'deleteJob']);

        // //Jornada   
        Route::get("show_jornada/", [JornadaController::class, 'getJornada']);
        Route::post("create_jornada/", [JornadaController::class, 'storeJornada']);
        Route::put("delete_jornada/{id}", [JornadaController::class, 'deleteJornada']);
        Route::put("update_jornada/{id}", [JornadaController::class, 'updateJornada']);

        // //TITUTLOS ACADEMICO
        Route::post("create_titulo_academico/", [TituloAcademicoController::class, 'storeTituloAcademico']);
        Route::put("update_titulo_academico/{id}", [TituloAcademicoController::class, 'updateTituloAcademico']);
        Route::put("delete_titulo_academico/{id}", [TituloAcademicoController::class, 'deleteTituloAcademico']);
        Route::get("show_data_titulo_academico/", [TituloAcademicoController::class, 'getTituloAcademico']);
        
        // distribucion
        Route::group(
            [
                "prefix" => "horario/",
            ],
            function () {
                Route::post("create_horario/", [DistribucionHorario::class, 'storeHorario']);
                Route::get("show_dist_horarios/", [DistribucionHorario::class, 'showDistribucion']);
                Route::put("update_distribucion/{id}", [DistribucionHorario::class, 'updatedistribucion']);
                Route::put("delete_distribucion/{id}", [DistribucionHorario::class, 'deleteDistribucion']);
            }
        );
        Route::group(
            [
                "prefix" => "Planificaciones/"
            ],
            function (){
                Route::post("createPlanificacionAcademico",[PlanificacionAcademica::class,'store']);
                Route::get("getPlanificacionAcademicas",[PlanificacionAcademica::class,'getPlanificacionAcademica']);
            }
        );
    }
);
