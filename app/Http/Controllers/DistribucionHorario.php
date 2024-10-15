<?php

namespace App\Http\Controllers;

use App\Http\Responses\TypeResponse;
use App\Models\DistribucionHorario as ModelsDistribucionHorario;
use App\Models\UsuarioModel;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DistribucionHorario extends Controller
{
    //


    public function storeHorario(Request $request)
{
    $response = new TypeResponse();
    try {
        DB::beginTransaction();
        $detalles = $request->input("data");
        $idUsuario = $request->input("id_usuario"); 
        $idPeriodoElectivo = $request->input("id_periodo");
        $idEducacionGlobal = 1;

        // Loguear información inicial
        Log::info('Detalles del horario a insertar', ['detalles' => $detalles]);
        Log::info('ID Usuario', ['id_usuario' => $idUsuario]);
        Log::info('ID idPeriodoElectivo', ['idPeriodoElectivo' => $idPeriodoElectivo]);

        Log::info('ID Educación Global', ['id_educacion_global' => $idEducacionGlobal]);

        // Obtener el tipo de trabajo del docente
        $job = DB::table('usuarios')
            ->join('job', 'usuarios.id_job', '=', 'job.id_job')
            ->where('usuarios.id_usuario', $idUsuario)
            ->select('job.descripcion as job_descripcion')
            ->first();

        if (!$job) {
            throw new Exception("No se pudo determinar el tipo de trabajo del docente.");
        }

        Log::info('Tipo de trabajo del docente', ['job_descripcion' => $job->job_descripcion]);

        // Definir los límites según el tipo de trabajo
        $horasPorSemanaLimite = $job->job_descripcion === 'Tiempo Completo' ? 40 : 20;
        $horasPorDiaLimite = $job->job_descripcion === 'Tiempo Completo' ? 8 : 4;

        // Obtener los datos del periodo (anio y periodo)
        $periodoData = DB::table('periodo_electivo')
            ->where('id_periodo', $idPeriodoElectivo)
            ->select('anio', 'periodo')
            ->first();

        if (!$periodoData) {
            throw new Exception("No se pudo determinar el año y periodo del periodo electivo.");
        }

        $anio = $periodoData->anio;
        $periodo = $periodoData->periodo;

        Log::info('Año y periodo del periodo electivo', ['anio' => $anio, 'periodo' => $periodo]);

        // Limitar horas por semana y por día según el año y período (S1 o S2)
        $horasExistentesPorSemana = ModelsDistribucionHorario::where("id_usuario", $idUsuario)
            ->where("id_periodo_academico", $idPeriodoElectivo)
            ->where("id_educacion_global", $idEducacionGlobal)
            ->whereBetween("fecha_creacion", [now()->startOfWeek(), now()->endOfWeek()])
            ->where("estado", "A")
            ->get()
            ->sum(function($materia) {
                return strtotime($materia->hora_termina) - strtotime($materia->hora_inicio);
            });

        // Calcular horas a ingresar en la semana
        $horasAIngresarPorSemana = collect($detalles)->sum(function ($values) {
            return strtotime($values['hora_termina']) - strtotime($values['hora_inicio']);
        });

        // Convertir de segundos a horas
        $horasExistentesPorSemana = $horasExistentesPorSemana / 3600;
        $horasAIngresarPorSemana = $horasAIngresarPorSemana / 3600;

        Log::info('Horas existentes por semana', ['horas_existentes' => $horasExistentesPorSemana]);
        Log::info('Horas a ingresar por semana', ['horas_a_ingresar' => $horasAIngresarPorSemana]);

        if ($horasExistentesPorSemana + $horasAIngresarPorSemana > $horasPorSemanaLimite) {
            throw new Exception("No se puede asignar más de {$horasPorSemanaLimite} horas por semana.");
        }

        // Validación por día
        $materiasPorDia = collect($detalles)->groupBy('dia')->map(function($materiasDelDia, $dia) use ($idUsuario, $idPeriodoElectivo, $idEducacionGlobal, $horasPorDiaLimite) {
            $horasExistentesPorDia = ModelsDistribucionHorario::where("id_usuario", $idUsuario)
                ->where("id_periodo_academico", $idPeriodoElectivo)
                ->where("id_educacion_global", $idEducacionGlobal)
                ->where("dia", $dia)
                ->where("estado", "A")
                ->get()
                ->sum(function($materia) {
                    return strtotime($materia->hora_termina) - strtotime($materia->hora_inicio);
                });

            $horasAIngresarPorDia = collect($materiasDelDia)->sum(function ($values) {
                return strtotime($values['hora_termina']) - strtotime($values['hora_inicio']);
            });

            $horasExistentesPorDia = $horasExistentesPorDia / 3600;
            $horasAIngresarPorDia = $horasAIngresarPorDia / 3600;

            Log::info("Horas existentes por día ($dia)", ['horas_existentes' => $horasExistentesPorDia]);
            Log::info("Horas a ingresar por día ($dia)", ['horas_a_ingresar' => $horasAIngresarPorDia]);

            if ($horasExistentesPorDia + $horasAIngresarPorDia > $horasPorDiaLimite) {
                throw new Exception("No se puede asignar más de {$horasPorDiaLimite} horas para el día " . $dia);
            }
        });

        // Proceso de inserción
        $insert_data = collect($detalles)->map(function ($values) use ($request, $idUsuario, $idEducacionGlobal) {
            $values = (object)$values;

            // Validar solapamiento de horarios
            $consulta = ModelsDistribucionHorario::where("id_usuario", $idUsuario)
                ->where("id_periodo_academico", $values->id_periodo)
                ->where("id_educacion_global", $idEducacionGlobal)
                ->where("id_carrera", $values->id_carrera)
                ->where("id_materia", $values->id_materia)
                ->where("id_nivel", $values->id_curso)
                ->where("id_paralelo", $values->id_paralelo)
                ->where("dia", $values->dia)
                ->where("hora_inicio", $values->hora_inicio)
                ->where("hora_termina", $values->hora_termina)
                ->where("estado", "A")
                ->get();

            if ($consulta->count() > 0) {
                throw new Exception("Ya existe una hora en el rango de " . $values->hora_inicio . " y " . $values->hora_termina);
            }

            Log::info('Datos de materia a insertar', (array)$values);

            return [
                "id_usuario" => $idUsuario,
                "id_periodo_academico" =>$values->id_periodo,
                "id_educacion_global" => $idEducacionGlobal,
                "id_carrera" => $values->id_carrera,
                "id_materia" => $values->id_materia,
                "id_nivel" => $values->id_curso,
                "id_paralelo" => $values->id_paralelo,
                "dia" => $values->dia,
                "hora_inicio" => $values->hora_inicio,
                "hora_termina" => $values->hora_termina,
                "ip_creacion" => $request->ip(),
                "ip_actualizacion" => $request->ip(),
                "id_usuario_creador" => $idUsuario,
                "id_usuario_actualizo" => $idUsuario,
                "fecha_creacion" => now(),
                "fecha_actualizacion" => now(),
                "estado" => 'A'
            ];
        });

        // Loguear datos finales a insertar
        Log::info('Datos finales de horarios a insertar', ['insert_data' => $insert_data]);

        ModelsDistribucionHorario::insert(array_values($insert_data->toArray()));
        DB::commit();
        return response()->json([
            "ok" => true,
            "mensaje" => "Horario creado con éxito."
        ]);
    } catch (Exception $e) {
        DB::rollBack();
        Log::alert("Ha ocurrido un error");
        Log::alert("Mensaje => " . $e->getMessage());
        Log::alert("Línea => " . $e->getLine());
        $response->setok(false);
        $response->setmensagge($e->getMessage());
        return response()->json([
            "ok" => false,
            "informacion" => "",
            "mensaje_error" => $e->getMessage()
        ]);
    }
}








public function showDistribucion(Request $request)
{
    try {
        $data = ModelsDistribucionHorario::select(
            "educacion_global.nombre as educacion_global_nombre",
            "carreras.nombre as nombre_carrera",
            "materias.descripcion as materia",
            "nivel.nemonico as nivel",
            "nivel.termino as termino_nivel",
            "id_distribucion as id_distribucion",
            "usuarios.id_usuario as id_usuario",
            "nivel.id_nivel as idnivel",
            
            "materias.id_materia as idmateria",
            "paralelo.id_paralelo as idparalelo",
            "carreras.id_carrera as idcarrera",
            "paralelo.paralelo",
            "distribuciones_horario_academica.dia",
            "distribuciones_horario_academica.hora_inicio",
            "distribuciones_horario_academica.hora_termina",
            "distribuciones_horario_academica.fecha_actualizacion",
            "periodo_electivo.estado as estado_periodo", 
            DB::raw("CONCAT(usuarios.nombres, ' ', usuarios.apellidos) as nombre_docente"),
            "usuarios.cedula as cedula_docente",
            "usuarios.correo as correo_docente",
            "usuarios.telefono as telefono_docente",
            "titulo_academico.descripcion as titulo_academico_docente",
            "distribuciones_horario_academica.id_periodo_academico as idperiodo",
            "periodo_electivo.anio",
            "periodo_electivo.periodo",

            "job.id_job",
            "job.descripcion as job_descripcion",
            "jornada.descripcion as jornada_descripcion" // Nueva columna seleccionada
        )
        ->join("educacion_global", function($join) {
            $join->on("distribuciones_horario_academica.id_educacion_global", "=", "educacion_global.id_educacion_global")
                ->where("educacion_global.estado", "=", "A"); // Filtrar por estado 'A'
        })
        ->join("carreras", function($join) {
            $join->on("distribuciones_horario_academica.id_carrera", "=", "carreras.id_carrera")
                ->where("carreras.estado", "=", "A"); // Filtrar por estado 'A'
        })
        ->join("materias", function($join) {
            $join->on("distribuciones_horario_academica.id_materia", "=", "materias.id_materia")
                ->where("materias.estado", "=", "A"); // Filtrar por estado 'A'
        })
        ->join("nivel", function($join) {
            $join->on("distribuciones_horario_academica.id_nivel", "=", "nivel.id_nivel")
                ->where("nivel.estado", "=", "A"); // Filtrar por estado 'A'
        })
        ->join("paralelo", function($join) {
            $join->on("distribuciones_horario_academica.id_paralelo", "=", "paralelo.id_paralelo")
                ->where("paralelo.estado", "=", "A"); // Filtrar por estado 'A'
        })
        ->join("periodo_electivo", function($join) {
            $join->on("distribuciones_horario_academica.id_periodo_academico", "=", "periodo_electivo.id_periodo")
                ->where("periodo_electivo.estado", "=", "A"); // Filtrar por estado 'A'
        })
        ->join("usuarios", function($join) {
            $join->on("distribuciones_horario_academica.id_usuario", "=", "usuarios.id_usuario")
                ->where("usuarios.estado", "=", "A"); // Filtrar por estado 'A'
        })
        ->join("rol", function($join) {
            $join->on("usuarios.id_rol", "=", "rol.id_rol")
                ->where("rol.estado", "=", "A"); // Filtrar por estado 'A'
        })
        ->join("titulo_academico", function($join) {
            $join->on("usuarios.id_titulo_academico", "=", "titulo_academico.id_titulo_academico")
                ->where("titulo_academico.estado", "=", "A"); // Filtrar por estado 'A'
        })
        ->leftJoin("job", function($join) {
            $join->on("usuarios.id_job", "=", "job.id_job")
                ->where("job.estado", "=", "A"); // Filtrar por estado 'A'
        })
        ->join("jornada", function($join) {
            $join->on("carreras.id_jornada", "=", "jornada.id_jornada")
                ->where("jornada.estado", "=", "A"); // Filtrar por estado 'A'
        })
        ->where("rol.descripcion", "=", "Docente")
        ->where("distribuciones_horario_academica.estado", "=", "A") // Filtro principal
        ->orderBy("distribuciones_horario_academica.dia")
        ->get();
         
        
        return response()->json([
            "ok" => true,
            "data" => $data
        ], 200);
    } catch (Exception $e) {
        // Registro de logs de error
        Log::error(__FILE__ . " > " . __FUNCTION__);
        Log::error("Mensaje : " . $e->getMessage());
        Log::error("Línea : " . $e->getLine());

        // Respuesta JSON en caso de error
        return response()->json([
            "ok" => false,
            "message" => "Error interno en el servidor"
        ], 500);
    }
}



// App/Http/Controllers/DistribucionHorarioController.php



public function updateDistribucion(Request $request, $id)
{
    try {
        // Buscar la distribución por ID
        $distribucion = ModelsDistribucionHorario::find($id);

        if (!$distribucion) {
            return response()->json([
                "ok" => false,
                "mensaje" => "El registro no existe con el ID $id."
            ], 400);
        }

        // Obtener los datos de la solicitud
        $data = $request->only([
            'id_docente',
            'id_periodo',
            'id_educacion_global',
            'id_materia',
            'id_carrera',
            'id_nivel',
            'id_paralelo',
            'dia',
            'hora_inicio',
            'hora_termina',
            'estado'
        ]);

        // Obtener el tipo de trabajo del docente
        $job = DB::table('usuarios')
            ->join('job', 'usuarios.id_job', '=', 'job.id_job')
            ->where('usuarios.id_usuario', $data['id_docente'] ?? $distribucion->id_docente)
            ->select('job.descripcion as job_descripcion')
            ->first();

        if (!$job) {
            return response()->json([
                "ok" => false,
                "mensaje" => "No se pudo determinar el tipo de trabajo del docente."
            ], 400);
        }

        // Definir los límites según el tipo de trabajo
        $horasPorDiaLimite = $job->job_descripcion === 'Tiempo Completo' ? 8 : 4;
        $horasPorSemanaLimite = $job->job_descripcion === 'Tiempo Completo' ? 40 : 20;

        // Validar límite diario de horas, excluyendo la distribución actual
        $horasExistentesPorDia = ModelsDistribucionHorario::where("id_usuario", $data['id_docente'] ?? $distribucion->id_docente)
            ->where("dia", $data['dia'] ?? $distribucion->dia)
            ->where("estado", "A")
            ->where("id_distribucion", "<>", $id) // Excluir la distribución actual
            ->get()
            ->sum(function($distrib) {
                return strtotime($distrib->hora_termina) - strtotime($distrib->hora_inicio);
            });

        $horasAIngresarPorDia = strtotime($data['hora_termina'] ?? $distribucion->hora_termina) - strtotime($data['hora_inicio'] ?? $distribucion->hora_inicio);
        $horasAIngresarPorDia = $horasAIngresarPorDia / 3600; // Convertir de segundos a horas
        $horasExistentesPorDia = $horasExistentesPorDia / 3600; // Convertir de segundos a horas

        if ($horasExistentesPorDia + $horasAIngresarPorDia > $horasPorDiaLimite) {
            return response()->json([
                "ok" => false,
                "mensaje" => "El día " . ($data['dia'] ?? $distribucion->dia) . " ya tiene el límite de {$horasPorDiaLimite} horas para un docente de " . $job->job_descripcion
            ], 400);
        }

        // Validar límite semanal de horas, excluyendo la distribución actual
        $horasExistentesPorSemana = ModelsDistribucionHorario::where("id_usuario", $data['id_docente'] ?? $distribucion->id_docente)
            ->whereBetween("fecha_creacion", [now()->startOfWeek(), now()->endOfWeek()])
            ->where("estado", "A")
            ->where("id_distribucion", "<>", $id) // Excluir la distribución actual
            ->get()
            ->sum(function($distrib) {
                return strtotime($distrib->hora_termina) - strtotime($distrib->hora_inicio);
            });

        $horasAIngresarPorSemana = strtotime($data['hora_termina'] ?? $distribucion->hora_termina) - strtotime($data['hora_inicio'] ?? $distribucion->hora_inicio);
        $horasAIngresarPorSemana = $horasAIngresarPorSemana / 3600; // Convertir de segundos a horas
        $horasExistentesPorSemana = $horasExistentesPorSemana / 3600; // Convertir de segundos a horas

        if ($horasExistentesPorSemana + $horasAIngresarPorSemana > $horasPorSemanaLimite) {
            return response()->json([
                "ok" => false,
                "mensaje" => "Ya se alcanzó el límite de {$horasPorSemanaLimite} horas para la semana de un docente de " . $job->job_descripcion
            ], 400);
        }

        // Validar solapamiento de horarios
        $exists = ModelsDistribucionHorario::where("id_usuario", $data['id_docente'] ?? $distribucion->id_docente)
            ->where("dia", $data['dia'] ?? $distribucion->dia)
            ->where("hora_inicio", $data['hora_inicio'] ?? $distribucion->hora_inicio)
            ->where("hora_termina", $data['hora_termina'] ?? $distribucion->hora_termina)
            ->where("estado", "A")
            ->where("id_distribucion", "<>", $id)
            ->exists();

        if ($exists) {
            return response()->json([
                "ok" => false,
                "mensaje" => "Ya existe una distribución con el mismo horario para el día " . ($data['dia'] ?? $distribucion->dia) . "."
            ], 400);
        }
        Log::info('Datos finales insertar', ['insert_data' => $distribucion]);

        // Actualizar los campos condicionalmente
        $distribucion->update([
            "id_usuario" => $data['id_docente'] ?? $distribucion->id_docente,
            "id_periodo_academico" => $data['id_periodo'] ?? $distribucion->id_periodo,
            "id_educacion_global" => $data['id_educacion_global'] ?? $distribucion->id_educacion_global,
            "id_materia" => $data['id_materia'] ?? $distribucion->id_materia,
            "id_carrera" => $data['id_carrera'] ?? $distribucion->id_carrera,
            "id_nivel" => $data['id_nivel'] ?? $distribucion->id_nivel,
            "id_paralelo" => $data['id_paralelo'] ?? $distribucion->id_paralelo,
            "dia" => $data['dia'] ?? $distribucion->dia,
            "hora_inicio" => $data['hora_inicio'] ?? $distribucion->hora_inicio,
            "hora_termina" => $data['hora_termina'] ?? $distribucion->hora_termina,
            "fecha_actualizacion" => now(),
            "id_usuario_actualizo" => auth()->id() ?? 1,
            "ip_actualizo" => $request->ip(),
            "estado" => $data['estado'] ?? $distribucion->estado
        ]);

        return response()->json([
            "ok" => true,
            "mensaje" => "Distribución actualizada con éxito."
        ], 200);

    } catch (Exception $e) {
        Log::error("Error: " . $e->getMessage() . " en la línea " . $e->getLine());
        return response()->json([
            "ok" => false,
            "mensaje" => "Error interno en el servidor: " . $e->getMessage()
        ], 500);
    }
}








    public function deleteDistribucion(Request $request, $id)
    {
        try {
            // Verificar el parámetro del ID
            if (!$id) {
                return response()->json([
                    "ok" => false,
                    "mensaje" => "Error: Falta el parámetro del ID."
                ], 400); // Código 400 para solicitud incorrecta
            }

            // Buscar la distribución por ID
            $distribucion = ModelsDistribucionHorario::find($id);

            if (!$distribucion) {
                return response()->json([
                    "ok" => false,
                    "mensaje" => "Error: El registro no existe."
                ], 404);
            }

            // Actualizar el estado a eliminado y los datos de actualización
            $distribucion->update([
                "estado" => "E",
                "id_usuario_actualizo" => auth()->id() ?? 1,
                "fecha_actualizacion" => Carbon::now()
            ]);

            return response()->json([
                "ok" => true
            ], 202); // Código 202 para aceptación de la solicitud
        } catch (Exception $e) {
            return response()->json([
                "ok" => false,
                "mensaje" => "Error interno en el servidor."
            ], 500);
        }
    }


}
