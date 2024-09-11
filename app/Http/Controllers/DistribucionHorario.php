<?php

namespace App\Http\Controllers;

use App\Http\Responses\TypeResponse;
use App\Models\DistribucionHorario as ModelsDistribucionHorario;
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
            $idPeriodoElectivo = 1;
            $idEducacionGlobal = 1;

            // Validación: Limitar 8 materias por día
            $materiasPorDia = ModelsDistribucionHorario::where("id_usuario", $idUsuario)
                ->where("id_periodo_academico", $idPeriodoElectivo)
                ->where("id_educacion_global", $idEducacionGlobal)
                ->where("dia", $detalles[0]['dia']) // Asumimos que todas las materias del array son para el mismo día
                ->where("estado", "A")
                ->count();

            if ($materiasPorDia + count($detalles) > 8) {
                throw new Exception("No se puede asignar más de 8 materias para el día " . $detalles[0]['dia']);
            }

            $insert_data = collect($detalles)->map(function ($values) use ($request, $idUsuario, $idPeriodoElectivo, $idEducacionGlobal){
                $values = (object)$values;
                
                // Validar solapamiento de horarios
                $consulta = ModelsDistribucionHorario::where("id_usuario", $idUsuario)
                    ->where("id_periodo_academico", $idPeriodoElectivo)
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

                return [
                    "id_usuario" => $idUsuario,
                    "id_periodo_academico" => $idPeriodoElectivo,
                    "id_educacion_global" => $idEducacionGlobal,
                    "id_carrera" => $values->id_carrera,
                    "id_materia" => $values->id_materia,
                    "id_nivel" => $values->id_curso,
                    "id_paralelo" => $values->id_paralelo,
                    "dia" => $values->dia,
                    "hora_inicio" => $values->hora_inicio,
                    "hora_termina" => $values->hora_termina,
                    "ip_creacion" => $request->ip(),
                    "ip_actualizacion"=> $request->ip(),
                    "id_usuario_creador" => $idUsuario,
                    "id_usuario_actualizo" => $idUsuario,
                    "fecha_creacion" => now(),
                    "fecha_actualizacion" => now(),
                    "estado" => 'A'
                ];
            });

            ModelsDistribucionHorario::insert(array_values($insert_data->toArray()));
            DB::commit();
            return Response()->json([
                "ok"=>true,
                "mensaje"=> "Horario creado con éxito."
            ]);
        } catch(Exception $e) {
            DB::rollBack();
            log::alert("Ha ocurrido un error");
            log::alert("Mensaje => " . $e->getMessage());
            log::alert("Línea => " . $e->getLine());
            $response->setok(false);
            $response->setmensagge($e->getMessage());
            return Response()->json([
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
                "nivel.termino as nivel",
                "id_distribucion as id_distribucion",
                "paralelo.paralelo",
                "distribuciones_horario_academica.dia",
                "distribuciones_horario_academica.hora_inicio",
                "distribuciones_horario_academica.hora_termina",
                "distribuciones_horario_academica.fecha_actualizacion",
                DB::raw("CONCAT(usuarios.nombres, ' ', usuarios.apellidos) as nombre_docente"), // Combina nombre y apellido
                "usuarios.cedula as cedula_docente",
                "usuarios.correo as correo_docente",
                "usuarios.telefono as telefono_docente",
                "titulo_academico.descripcion as titulo_academico_docente" // Obtiene la descripción del título académico
            )
            ->join("educacion_global", "distribuciones_horario_academica.id_educacion_global", "=", "educacion_global.id_educacion_global")
            ->join("carreras", "distribuciones_horario_academica.id_carrera", "=", "carreras.id_carrera")
            ->join("materias", "distribuciones_horario_academica.id_materia", "=", "materias.id_materia")
            ->join("nivel", "distribuciones_horario_academica.id_nivel", "=", "nivel.id_nivel")
            ->join("paralelo", "distribuciones_horario_academica.id_paralelo", "=", "paralelo.id_paralelo")
            ->join("usuarios", "distribuciones_horario_academica.id_usuario", "=", "usuarios.id_usuario")
            ->join("rol", "usuarios.id_rol", "=", "rol.id_rol")
            ->join("titulo_academico", "usuarios.id_titulo_academico", "=", "titulo_academico.id_titulo_academico") // Join para obtener el título académico
            ->where("rol.descripcion", "=", "Docente")
            ->where("distribuciones_horario_academica.estado", "=", "A") // Filtro para estado "A"
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
                'id_usuario',
                'id_periodo_academico',
                'id_educacion_global',
                'id_materia',
                'id_nivel',
                'id_paralelo',
                'dia',
                'hora_inicio',
                'hora_termina',
                'estado'
            ]);

            // Validar que los datos no se dupliquen
            $exists = ModelsDistribucionHorario::where("id_usuario", $data['id_usuario'] ?? $distribucion->id_usuario)
                ->where("id_periodo_academico", $data['id_periodo_academico'] ?? $distribucion->id_periodo_academico)
                ->where("id_educacion_global", $data['id_educacion_global'] ?? $distribucion->id_educacion_global)
                ->where("id_materia", $data['id_materia'] ?? $distribucion->id_materia)
                ->where("id_nivel", $data['id_nivel'] ?? $distribucion->id_nivel)
                ->where("id_paralelo", $data['id_paralelo'] ?? $distribucion->id_paralelo)
                ->where("dia", $data['dia'] ?? $distribucion->dia)
                ->where("hora_inicio", $data['hora_inicio'] ?? $distribucion->hora_inicio)
                ->where("hora_termina", $data['hora_termina'] ?? $distribucion->hora_termina)
                ->where("estado", $data['estado'] ?? $distribucion->estado)
                ->where("id_distribucion", "<>", $id) // Usa el nombre correcto de la columna
                ->exists();

            if ($exists) {
                return response()->json([
                    "ok" => false,
                    "mensaje" => "Ya existe una distribución con los mismos parámetros."
                ], 400);
            }

            // Actualizar los campos condicionalmente
            $distribucion->update([
                "id_usuario" => $data['id_usuario'] ?? $distribucion->id_usuario,
                "id_periodo_academico" => $data['id_periodo_academico'] ?? $distribucion->id_periodo_academico,
                "id_educacion_global" => $data['id_educacion_global'] ?? $distribucion->id_educacion_global,
                "id_materia" => $data['id_materia'] ?? $distribucion->id_materia,
                "id_nivel" => $data['id_nivel'] ?? $distribucion->id_nivel,
                "id_paralelo" => $data['id_paralelo'] ?? $distribucion->id_paralelo,
                "dia" => $data['dia'] ?? $distribucion->dia,
                "hora_inicio" => $data['hora_inicio'] ?? $distribucion->hora_inicio,
                "hora_termina" => $data['hora_termina'] ?? $distribucion->hora_termina,
                "fecha_actualizacion" => Carbon::now(),
                "id_usuario_actualizo" => auth()->id() ?? 1, // Valor predeterminado
                "ip_actualizo" => $request->ip(),
                "estado" => $data['estado'] ?? $distribucion->estado
            ]);

            return response()->json([
                "ok" => true,
                "mensaje" => "Distribución actualizada con éxito."
            ], 200);

        } catch (Exception $e) {
            // Registro de errores
            Log::error(__FILE__ . " > " . __FUNCTION__);
            Log::error("Mensaje: " . $e->getMessage());
            Log::error("Línea: " . $e->getLine());

            return response()->json([
                "ok" => false,
                "mensaje" => "Error interno en el servidor."
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
