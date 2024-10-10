<?php

namespace App\Http\Controllers;

use App\Models\JobModel;
use App\Models\UsuarioModel;
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
        $request->validate([
            'descripcion' => 'required|string|max:255',
        ]);

        // Verificar si el job ya existe
        $jobExistente = JobModel::where('descripcion', ucfirst(trim($request->descripcion)))
            ->first();

        if ($jobExistente) {
            if ($jobExistente->estado === 'E') {
                // Si el job está eliminado, cambiar su estado a "A"
                $jobExistente->estado = 'A'; // Cambia el estado a Activo
                $jobExistente->ip_actualizacion = $request->ip(); // Actualiza la IP
                $jobExistente->id_usuario_actualizo = auth()->id() ?? 1; // Actualiza el usuario que hace el cambio
                $jobExistente->fecha_actualizacion = Carbon::now(); // Actualiza la fecha
                $jobExistente->save(); // Guarda los cambios

                return response()->json([
                    "ok" => true,
                    "message" => "Tiempo de dedicación activado con éxito."
                ], 200);
            } else {
                // Si ya está activo, retornamos un mensaje de error
                return response()->json([
                    "ok" => false,
                    "message" => "El tiempo de dedicación ya existe."
                ], 400);
            }
        }

        // Crear el nuevo Job y asignar valores
        $modelo = new JobModel();
        $modelo->descripcion = ucfirst(trim($request->descripcion));
        $modelo->ip_creacion = $request->ip();
        $modelo->ip_actualizacion = $request->ip();
        $modelo->id_usuario_creador = auth()->id() ?? 1;
        $modelo->id_usuario_actualizo = auth()->id() ?? 1;
        $modelo->fecha_creacion = Carbon::now();
        $modelo->fecha_actualizacion = Carbon::now();
        $modelo->estado = "A"; // Estado activo
        $modelo->save();

        return response()->json([
            "ok" => true,
            "message" => "Tiempo de dedicación creado con éxito."
        ], 200);

    } catch (Exception $e) {
        Log::error(__FILE__ . " > " . __FUNCTION__);
        Log::error("Mensaje : " . $e->getMessage());
        Log::error("Linea : " . $e->getLine());
        return response()->json([
            "ok" => false,
            "message" => "Error interno en el servidor."
        ], 500);
    }
}


    public function getJobs(Request $request)
    {
        try {
            Log::info("Petición entrante " . __FILE__ . " -> " . __FUNCTION__ . " IP " . request()->ip());

            // Realiza la consulta a la base de datos
            $job = JobModel::select("job.id_job", "job.descripcion", "job.estado", "usuarios.usuario as usuarios_ultima_gestion", "job.fecha_actualizacion")
                ->join('usuarios', 'job.id_usuario_actualizo', '=', 'usuarios.id_usuario') // Verifica los nombres
                ->whereIn("job.estado", ["A", "I"])
                ->get();
                
            Log::info("Datos obtenidos: " . json_encode($job));

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

    public function updateJob(Request $request, $id)
    {
        Log::info("Petición entrante " . __FILE__ . " -> " . __FUNCTION__ . " IP " . request()->ip());

        try {
            // Buscar el trabajo por el id proporcionado
            $job = JobModel::find($id);

            if (!$job) {
                return response()->json([
                    "ok" => false,
                    "message" => "El registro no existe con el id $id"
                ], 400);
            }

            // Actualizar los campos del trabajo con los datos proporcionados, si están presentes
            JobModel::find($id)->update([
                "descripcion" => isset($request->descripcion) ? $request->descripcion : $job->descripcion,
                "estado" => isset($request->estado) ? $request->estado : $job->estado,
                "id_usuario_actualizo" => auth()->id() ?? 1,
                "ip_actualizacion" => $request->ip()
            ]);

            // Respuesta exitosa
            return response()->json([
                "ok" => true,
                "message" => "Trabajo actualizado con éxito"
            ], 200);
        } catch (\Exception $e) {
            // Manejar cualquier excepción y registrar el error
            Log::error(__FILE__ . " > " . __FUNCTION__);
            Log::error("Mensaje: " . $e->getMessage());
            Log::error("Línea: " . $e->getLine());
            return response()->json([
                "ok" => false,
                "message" => "Error interno en el servidor"
            ], 500);
        }
    }



    public function deleteJob(Request $request, $id)
{
    Log::info("Petición entrante " . __FILE__ . " -> " . __FUNCTION__ . " IP " . request()->ip());

    try {
        // Buscar el trabajo por el id proporcionado
        $job = JobModel::find($id);

        if (!$job) {
            return response()->json([
                "ok" => false,
                "message" => "El trabajo no existe con el id $id"
            ], 400);
        }

        // Buscar los usuarios que tienen este id_job asignado
        $usuariosConJob = UsuarioModel::where('id_job', $id)->get();
        Log::info('Verificación de existencia de usuario completada.', ['usuariosConJob' => $usuariosConJob]);
    // Definir el id del rol por defecto
    $rolDefectoId = 1;
        if ($usuariosConJob->isNotEmpty()) {
            // Quitar el id_job de todos los usuarios que lo tienen asignado
            UsuarioModel::where('usuarios.id_job', $id)
                ->update([
                    'usuarios.id_job' => $rolDefectoId, // Desvincular el trabajo
                    'usuarios.id_usuario_actualizo' => auth()->id() ?? 1, // ID del usuario que realiza la actualización
                    'usuarios.ip_actualizacion' => $request->ip(), // IP del usuario
                    'usuarios.fecha_actualizacion' => now(), // Fecha y hora actual
                ]);
        }

        // Marcar el trabajo como eliminado (cambiando su estado)
        $job->update([
            "estado" => "E", // Estado para indicar que está eliminado
            "id_usuario_actualizo" => auth()->id() ?? 1, // ID del usuario que realiza la actualización
            "ip_actualizacion" => $request->ip(), // IP del usuario
            "fecha_actualizacion" => now(), // Fecha y hora actual
        ]);

        return response()->json([
            "ok" => true,
            "message" => "Trabajo eliminado con éxito y usuarios actualizados"
        ], 200);
    } catch (\Exception $e) {
        // Manejar cualquier excepción y registrar el error
        Log::error(__FILE__ . " > " . __FUNCTION__);
        Log::error("Mensaje: " . $e->getMessage());
        Log::error("Línea: " . $e->getLine());

        return response()->json([
            "ok" => false,
            "message" => "Error interno en el servidor"
        ], 500);
    }
}



}
