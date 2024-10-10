<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Responses\TypeResponse;
use App\Models\ParaleloModel;
use App\Services\ParaleloServicio;
use App\Services\Validaciones;
use Exception;
use Illuminate\Support\Facades\Log;


class ParaleloController extends Controller
{
    public function storeParalelo(Request $request)
    {
        try {
            // Validar que el campo 'paralelo' esté presente
            $request->validate([
                'paralelo' => 'required|string|max:255', // Ajusta la validación según tus requisitos
            ]);

            // Buscar si el paralelo ya existe (sin importar el estado)
            $paraleloExistente = ParaleloModel::where('paralelo', $request->paralelo)->first();
            // Si el paralelo existe y está inactivo, lo activamos
            if ($paraleloExistente) {
                if ($paraleloExistente->estado === 'E') {
                    $paraleloExistente->update([
                        'estado' => 'A',
                        'ip_actualizacion' => $request->ip(),
                        'id_usuario_actualizo' => auth()->id() ?? 1
                    ]);
                    return response()->json([
                        "ok" => true,
                        "message" => "Paralelo actualizado con éxito"
                    ], 200);
                } else {
                    // Si ya está activo, retornamos un mensaje de error
                    return response()->json([
                        "ok" => false,
                        "message" => "El paralelo ya existe"
                    ], 400);
                }
            }
            
            // Si no existe, crear un nuevo paralelo
            $modelo = new ParaleloModel();
            $modelo->paralelo = $request->paralelo;
            $modelo->ip_creacion = $request->ip();
            $modelo->ip_actualizacion = $request->ip();
            $modelo->id_usuario_creador = auth()->id() ?? 1;
            $modelo->id_usuario_actualizo = auth()->id() ?? 1;
            $modelo->estado = "A";
            $modelo->save();
            return response()->json([
                "ok" => true,
                "message" => "Paralelo creado exitosamente."
            ], 200);
        } catch (Exception $e) {
            // Manejar cualquier excepción
            log::error(__FILE__ . " > " . __FUNCTION__);
            log::error("Mensaje: " . $e->getMessage());
            log::error("Línea: " . $e->getLine());
            return response()->json([
                "ok" => false,
                "message" => "Error interno en el servidor."
            ], 500);
        }
    }



    public function deleteParalelo(Request $request, $id)
    {
        try {
            // Buscar el paralelo por su ID
            $paralelo = ParaleloModel::find($id);
            if (!$paralelo) {
                return response()->json([
                    "ok" => false,
                    "message" => "El paralelo no existe con el id $id"
                ], 400);
            }

            // Verificar si hay distribuciones asociadas
            $distribuciones = \DB::table('distribuciones_horario_academica')
                ->where('id_paralelo', $id)
                ->exists();

            // Cambiar el estado del paralelo a "E"
            $paralelo->estado = "E";
            $paralelo->id_usuario_actualizo = auth()->id() ?? 1;
            $paralelo->ip_actualizacion = $request->ip();
            $paralelo->fecha_actualizacion = now();
            $paralelo->save();

            // Inhabilitar distribuciones asociadas si existen
            if ($distribuciones) {
                \DB::table('distribuciones_horario_academica')
                    ->where('id_paralelo', $id)
                    ->update(['estado' => "E"]);
            }

            return response()->json([
                "ok" => true,
                "message" => $distribuciones 
                    ? "Paralelo y distribuciones eliminadas con éxito" 
                    : "Paralelo eliminado con éxito"
            ], 200);

        } catch (Exception $e) {
            Log::error(__FILE__ . " > " . __FUNCTION__);
            Log::error("Mensaje: " . $e->getMessage());
            Log::error("Línea: " . $e->getLine());

            return response()->json([
                "ok" => false,
                "message" => "Error interno en el servidor"
            ], 500);
        }
    }

    public function checkDistribucionesPorParalelo($id)
    {
        try {
            $distribuciones = \DB::table('distribuciones_horario_academica')
                ->where('id_paralelo', $id)
                ->where('estado', "A")
                ->count();

            return response()->json([
                "ok" => true,
                "count" => $distribuciones,
            ], 200);
        } catch (Exception $e) {
            Log::error(__FILE__ . " > " . __FUNCTION__);
            Log::error("Mensaje: " . $e->getMessage());
            Log::error("Línea: " . $e->getLine());

            return response()->json([
                "ok" => false,
                "message" => "Error interno en el servidor"
            ], 500);
        }
    }

    public function showParalelo()
    {
        try{
            $paralelo = ParaleloModel::select("paralelo.id_paralelo","paralelo.paralelo","paralelo.estado","usuarios.usuario as usuarios_ultima_gestion","paralelo.fecha_actualizacion")
            ->join('usuarios','paralelo.id_usuario_actualizo','usuarios.id_usuario')       
            ->whereIn("paralelo.estado",["A","I"])
            ->orderBy("paralelo.paralelo")
            ->get();
            return Response()->json([
                "ok" => true,
                "data" => $paralelo
            ],200);
        }catch(Exception $e){
            log::error( __FILE__ . " > " . __FUNCTION__);
            log::error("Mensaje : " . $e->getMessage());
            log::error("Linea : " . $e->getLine());
            
            return Response()->json([
                "ok" => false,
                "message" => "Error interno en el servidor"
            ],500);
        }
    }

    public function updateParalelo(Request $request, $id)
    {
        try {
            // Buscar el paralelo por el id proporcionado
            $paralelo = ParaleloModel::find($id);


            if (!$paralelo) {
                return response()->json([
                    "ok" => false,
                    "message" => "El registro no existe con el id $id"
                ], 400);
            }

             // Actualizar los campos del paralelo con los datos proporcionados, si están presentes
             ParaleloModel::find($id)->update([
                "paralelo" => isset($request->paralelo) ? $request->paralelo : $paralelo->paralelo,
                "id_usuario_actualizo" => auth()->id() ?? 1,
                "ip_actualizacion" => $request->ip(),
                "estado" => isset($request->estado) ? $request->estado : "A"
            ]);


              // Respuesta exitosa
            return response()->json([
                "ok" => true,
                "message" => "Paralelo actualizado con éxito"
            ], 200);
        } catch (\Exception $e) {
            // Manejar cualquier excepción y registrar el error
            Log::error(__FILE__ . " > " . __FUNCTION__);
            Log::error("Mensaje: " . $e->getMessage());
            Log::error("Línea: " . $e->getLine());
            return Response()->json([
                "ok" => false,
                "message" => "Error interno en el servidor"
            ],500);
        }
    }
}