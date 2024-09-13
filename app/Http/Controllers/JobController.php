<?php

namespace App\Http\Controllers;

use App\Models\JobModel;
use App\Services\MensajeAlertasServicio;
use Exception;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Request as request_ip;
use Illuminate\Support\Facades\Log;


class JobController extends Controller
{
    private $servicio_informe;
    public function __construct()
    {
        $this->servicio_informe = new MensajeAlertasServicio();
    }
    public function storeJob(Request $request)
    {
        $this->servicio_informe->storeInformativoLogs(__FILE__, __FUNCTION__);
        try {
            // Verificar si el rol ya existe
            $jobExistente = JobModel::where('descripcion', ucfirst(trim($request->descripcion)))
                ->where('estado', 'A')
                ->first();

            if ($jobExistente) {
                return response()->json([
                    "ok" => false,
                    "message" => "El tiempo de dedicacion ya existe.",
                ], 400);
            }

            // Crear el nuevo rol y asignar valores
            $modelo = new JobModel();
            $modelo->descripcion = ucfirst(trim($request->descripcion));
            $modelo->ip_creacion = $request->ip();
            $modelo->ip_actualizacion = $request->ip();
            $modelo->id_usuario_creador = auth()->id() ?? 1;
            $modelo->id_usuario_actualizo = auth()->id() ?? 1;
            $modelo->fecha_creacion = Carbon::now();
            $modelo->fecha_actualizacion = Carbon::now();
            $modelo->estado = "A";
            $modelo->save();

            return Response()->json([
                "ok" => true,
                "message" => "Tiempo de dedicacion creado con éxito"
            ], 200);

        } catch (Exception $e) {
            Log::error(__FILE__ . " > " . __FUNCTION__);
            Log::error("Mensaje : " . $e->getMessage());
            Log::error("Linea : " . $e->getLine());
            return Response()->json([
                "ok" => false,
                "message" => "Error interno en el servidor"
            ], 500);
        }
    }

    public function getJobs(Request $request)
    {
        try {
            Log::info("Petición entrante " . __FILE__ . " -> " . __FUNCTION__ . " IP " . request_ip::ip());

            // Asegúrate de que los nombres de las tablas y campos coincidan con los reales en la base de datos
            $job = JobModel::select("id_job", "descripcion", "estado", "usuarios.usuario as usuarios_ultima_gestion", "fecha_actualizacion")
                ->whereIn("estado", ["A", "I"])
                ->join('usuarios', 'id_usuario_actualizo', '=', 'usuarios.id_usuario') // Asegúrate de que estos nombres de campo son correctos
                ->get();

            // Registra la respuesta para depuración
            Log::info("Datos obtenidos: " . $job);

        } catch (Exception $e) {
            Log::error(__FILE__ . " -> " . __FUNCTION__ . " MENSAJE => " . $e->getMessage());
            return response()->json([
                "ok" => false,
                "message" => "Error interno en el servidor"
            ], 500);
        }

        return response()->json([
            "ok" => true,
            "data" => $job,
            "mensaje" => "Datos obtenidos exitosamente"
        ], 200);
    }
}
