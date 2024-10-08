<?php

namespace App\Http\Controllers;

use App\Models\RolModel;
use App\Services\MensajeAlertasServicio;
use Exception;
use App\Models\UsuarioModel;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Request as request_ip;
use Illuminate\Support\Facades\Log;

class RolController extends Controller
{
    private $servicio_informe;
    public function __construct()
    {
        $this->servicio_informe = new MensajeAlertasServicio();
    }
    public function storeRol(Request $request)
    {
        $this->servicio_informe->storeInformativoLogs(__FILE__, __FUNCTION__);
        try {
            // Verificar si el rol ya existe
            $rolExistente = RolModel::where('descripcion', ucfirst(trim($request->descripcion)))
                ->where('estado', 'A')
                ->first();

            if ($rolExistente) {
                return response()->json([
                    "ok" => false,
                    "message" => "El rol ya existe.",
                ], 400);
            }

            // Crear el nuevo rol y asignar valores
            $modelo = new RolModel();
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
                "message" => "Rol creado con éxito"
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

    public function deleteRol(Request $request, $id)
{
    try {
        // Buscar el rol por el id proporcionado
        $rol = RolModel::find($id);

        if (!$rol) {
            return response()->json([
                "ok" => false,
                "message" => "El perfil no existe con el id $id"
            ], 404);
        }

        // Definir el id del rol por defecto
        $rolDefectoId = 1; // Asegúrate de que este rol exista

        // Verificar que el rol por defecto exista
        $rolDefecto = RolModel::find($rolDefectoId);
        if (!$rolDefecto) {
            return response()->json([
                "ok" => false,
                "message" => "El rol por defecto no existe."
            ], 500);
        }

        // Buscar los usuarios que tienen este id_rol asignado
        $usuariosConRol = UsuarioModel::where('id_rol', $id)->get();
        Log::info('Verificación de existencia de usuario completada.', ['usuariosConRol' => $usuariosConRol]);

        if ($usuariosConRol->isNotEmpty()) {
            // Asignar el rol por defecto a todos los usuarios que tienen el rol a eliminar
            UsuarioModel::where('id_rol', $id)
                ->update([
                    'id_rol' => $rolDefectoId, // Asignar el rol por defecto
                    'id_usuario_actualizo' => auth()->id() ?? 1, // ID del usuario que realiza la actualización
                    'ip_actualizacion' => $request->ip(), // IP del usuario
                    'fecha_actualizacion' => now(), // Fecha y hora actual
                ]);
        }

        // Marcar el rol como eliminado (cambiando su estado)
        $rol->update([
            "estado" => "E",  // Estado cambiado a "E" para marcarlo como eliminado
            "id_usuario_actualizo" => auth()->id() ?? 1, // ID del usuario que realiza la actualización
            "ip_actualizacion" => $request->ip(), // IP del usuario
            "fecha_actualizacion" => now(), // Fecha y hora actual
        ]);

        return response()->json([
            "ok" => true,
            "message" => "Perfil eliminado con éxito y usuarios actualizados"
        ], 200);

    } catch (Exception $e) {
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




    public function getRoles(Request $request)
    {
        try {
            log::info("Peticion entrante " . __FILE__ . " -> " . __FUNCTION__ . " ip " . request_ip::ip());
            $rol = RolModel::select("rol.id_rol", "rol.descripcion", "rol.estado", "usuarios.usuario as usuarios_ultima_gestion", "rol.fecha_actualizacion")
                ->whereIn("rol.estado", ["A", "I"])
                ->join('usuarios', 'rol.id_usuario_actualizo', 'usuarios.id_usuario')
                ->get();
        } catch (Exception $e) {
            log::error(__FILE__ . __FUNCTION__ . " MENSAJE => " . $e->getMessage());
            return Response()->json([
                "ok" => false,
                "message" => "Error interno en el servidor"
            ], 500);
        } finally {
            return Response()->json([
                "ok" => true,
                "data" => $rol,
                "mensaje" => "Datos obtenidos exitosamente"
            ], 200);
        }
    }
    public function updateRol(Request $request, $id)
    {
        $this->servicio_informe->storeInformativoLogs(__FILE__, __FUNCTION__);
        try {
            // Buscar el rol por su ID
            $rol = RolModel::find($id);

            // Verificar si el rol existe
            if (!$rol) {
                return response()->json([
                    "ok" => false,
                    "message" => "El rol no existe.",
                ], 404);
            }

            // Verificar si la nueva descripción ya existe en otro rol
            $rolExistente = RolModel::where('descripcion', ucfirst(trim($request->descripcion)))
                ->where('estado', 'A')
                ->where('id_rol', '!=', $id) // Excluir el rol actual de la búsqueda
                ->first();

            if ($rolExistente) {
                return response()->json([
                    "ok" => false,
                    "message" => "El rol con la descripción proporcionada ya existe.",
                ], 400);
            }

            // Actualizar los datos del rol
            $rol->descripcion = ucfirst(trim($request->descripcion));
            $rol->ip_actualizacion = $request->ip();
            $rol->id_usuario_actualizo = auth()->id() ?? 1;
            $rol->estado = "A";
            $rol->save();

            return response()->json([
                "ok" => true,
                "message" => "Rol actualizado con éxito",
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
