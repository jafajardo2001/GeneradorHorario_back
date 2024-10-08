<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\JornadaModel;
use App\Services\MensajeAlertasServicio;
use Exception;
use Carbon\Carbon;
use Illuminate\Support\Facades\Request as request_ip;
use Illuminate\Support\Facades\Log;

class JornadaController extends Controller
{
    private $servicio_informe;
    public function __construct()
    {
        $this->servicio_informe = new MensajeAlertasServicio();
    }


    public function storeJornada(Request $request)
    {
        $this->servicio_informe->storeInformativoLogs(__FILE__, __FUNCTION__);
        try {
            // Verificar si el rol ya existe
            $jornadaExistente = JornadaModel::where('descripcion', ucfirst(trim($request->descripcion)))
                ->where('estado', 'A')
                ->first();

            if ($jornadaExistente) {
                return response()->json([
                    "ok" => false,
                    "message" => "La Jornada ya existe.",
                ], 400);
            }

            // Crear el nuevo rol y asignar valores
            $modelo = new JornadaModel();
            $modelo->descripcion = ucfirst(trim($request->descripcion));
            $modelo->ip_creacion = $request->ip();
            $modelo->ip_actualizacion = $request->ip();
            $modelo->id_usuario_creador = auth()->id() ?? 1;
            $modelo->id_usuario_actualizo = auth()->id() ?? 1;
            $modelo->fecha_creacion = Carbon::now();
            $modelo->fecha_actualizacion = Carbon::now();
            $modelo->estado = "A";
            $modelo->save();

            return response()->json([
                "ok" => true,
                "message" => "Jornada creada con éxito"
            ], 200);

        } catch (Exception $e) {
            Log::error(__FILE__ . " > " . __FUNCTION__);
            Log::error("Mensaje : " . $e->getMessage());
            Log::error("Linea : " . $e->getLine());
            return response()->json([
                "ok" => false,
                "message" => "Error interno en el servidor"
            ], 500);
        }
    }

    public function getJornada(Request $request)
    {
        try {
            Log::info("Petición entrante desde " . __FILE__ . " -> " . __FUNCTION__ . " IP " . request()->ip());

            // Realiza la consulta a la base de datos
            $jornada = JornadaModel::select(
                    "jornada.id_jornada",
                    "jornada.descripcion",
                    "jornada.estado",
                    "usuarios.usuario as usuarios_ultima_gestion",
                    "jornada.fecha_actualizacion"
                )
                ->join('usuarios', 'jornada.id_usuario_actualizo', '=', 'usuarios.id_usuario') // Verifica los nombres
                ->whereIn("jornada.estado", ["A", "I"])
                ->get();
            
            Log::info("Datos obtenidos: " . json_encode($jornada));

            return response()->json([
                "ok" => true,
                "data" => $jornada,
                "mensaje" => "Datos obtenidos exitosamente"
            ], 200);

        } catch (Exception $e) {
            Log::error(__FILE__ . " -> " . __FUNCTION__ . " MENSAJE => " . $e->getMessage());
            return response()->json([
                "ok" => false,
                "message" => "Error interno en el servidor"
            ], 500);
        }
    }

    public function deleteJornada(Request $request, $id)
    {
        try {
            $jornada = JornadaModel::find($id);
            
            if (!$jornada) {
                return response()->json([
                    "ok" => false,
                    "message" => "La jornada no existe con el id $id"
                ], 400);
            }
            
            // Actualizar el estado a "E" para indicar que se ha eliminado
            $jornada->update([
                "estado" => "E", // Asumiendo que "E" es el estado para eliminado
                "id_usuario_actualizo" => auth()->id() ?? 1,
                "ip_actualizo" => $request->ip(),
                "fecha_actualizacion" => now(),
            ]);
            return response()->json([
                "ok" => true,
                "message" => "Jornada eliminada con éxito"
            ], 200);
        } catch (Exception $e) {
            Log::error(__FILE__ . " > " . __FUNCTION__);
            Log::error("Mensaje : " . $e->getMessage());
            Log::error("Linea : " . $e->getLine());
            return response()->json([
                "ok" => false,
                "message" => "Error interno en el servidor"
            ], 500);
        }
    }

    public function updateJornada(Request $request, $id)
    {
        $this->servicio_informe->storeInformativoLogs(__FILE__, __FUNCTION__);
        try {
            // Buscar el rol por su ID
            $jornada = JornadaModel::find($id);

            // Verificar si el rol existe
            if (!$jornada) {
                return response()->json([
                    "ok" => false,
                    "message" => "La jornada no existe.",
                ], 404);
            }

            // Verificar si la nueva descripción ya existe en otro rol
            $jornadaExistente = JornadaModel::where('descripcion', ucfirst(trim($request->descripcion)))
                ->where('estado', 'A')
                ->where('id_jornada', '!=', $id) // Excluir el rol actual de la búsqueda
                ->first();
                Log::info('Verificación de existencia de usuario completada.', ['usuarioExistente' => $jornadaExistente]);

            if ($jornadaExistente) {
                return response()->json([
                    "ok" => false,
                    "message" => "La jornada con la descripción proporcionada ya existe.",
                ], 400);
            }

            // Actualizar los datos del rol
            $jornada->descripcion = ucfirst(trim($request->descripcion));
            $jornada->ip_actualizacion = $request->ip();
            $jornada->id_usuario_actualizo = auth()->id() ?? 1;
            $jornada->estado = "A";
            $jornada->save();
            Log::info('Verificación de existencia de usuario completada.', ['usuarioExistente' => $jornada]);

            return response()->json([
                "ok" => true,
                "message" => "Jornada actualizada con éxito",
            ], 200);

        } catch (Exception $e) {
            Log::error(__FILE__ . " > " . __FUNCTION__);
            Log::error("Mensaje : " . $e->getMessage());
            Log::error("Linea : " . $e->getLine());
            return response()->json([
                "ok" => false,
                "message" => "Error interno en el servidor",
            ], 500);
        }
    }

}

