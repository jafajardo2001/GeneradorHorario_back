<?php

namespace App\Http\Controllers;

use App\Http\Responses\TypeResponse;
use App\Models\CarreraModel;
use App\Models\JornadaModel;
use App\Services\Validaciones;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CarreraController extends Controller
{

    public function storeCarrera(Request $request)
{
    try{
        // Validar los campos obligatorios
        $modelo = new CarreraModel();
        
        
        if (!empty($campos_faltantes)) {
            return response()->json([
                "ok" => false,
                "message" => "Los siguientes campos son obligatorios: " . implode(', ', $campos_faltantes)
            ], 400);
        }

        // Verificar si la combinación de carrera y jornada ya existe
        $carreraExistente = CarreraModel::where('nombre', ucfirst(trim($request->nombre)))
            ->where('id_jornada', $request->id_jornada)
            ->where('estado', 'A')
            ->first();

        if ($carreraExistente) {
            return response()->json([
                "ok" => false,
                "message" => "La carrera '" . $request->nombre . "' ya está registrada en la jornada seleccionada."
            ], 400);
        }
        
        // Crear la nueva carrera
        $modelo->nombre = ucfirst(trim($request->nombre));  // Asegúrate de capitalizar el nombre
        $modelo->id_jornada = $request->id_jornada;
        $modelo->ip_creacion = $request->ip();
        $modelo->ip_actualizacion = $request->ip();
        $modelo->id_usuario_creador = auth()->id() ?? 1;
        $modelo->id_usuario_actualizo = auth()->id() ?? 1;
        $modelo->estado = "A";
        $modelo->save();

        return response()->json([
            "ok" => true,
            "message" => "Carrera creada con éxito"
        ], 200);
    } catch (Exception $e) {
        log::error(__FILE__ . " > " . __FUNCTION__);
        log::error("Mensaje : " . $e->getMessage());
        log::error("Línea : " . $e->getLine());

        return response()->json([
            "ok" => false,
            "message" => "Error interno en el servidor"
        ], 500);
    }
}


public function deleteCarrera(Request $request, $id)
{  
    try {
        // Buscar la carrera por su ID
        $carrera = CarreraModel::find($id);
        if (!$carrera) {
            return response()->json([
                "ok" => false,
                "message" => "La carrera no existe con el id $id"
            ], 400);    
        }

        // Cambiar el estado de la carrera a "E"
        $carrera->estado = "E";  // Cambia el estado a "E"
        $carrera->id_usuario_actualizo = auth()->id() ?? 1;  // Actualiza el usuario que hace el cambio
        $carrera->ip_actualizacion = $request->ip();  // Actualiza la IP
        $carrera->save();  // Guarda los cambios

        // Eliminar registros en usuario_carrera_jornada
        \DB::table('usuario_carrera_jornada')
            ->where('id_carrera', $id)
            ->delete();

        // O puedes usar `delete()` si deseas eliminar la fila

        return response()->json([
            "ok" => true,
            "message" => "Carrera eliminada con éxito"
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


    public function updateCarrera(Request $request, $id)
{
    try {
        // Validar los datos recibidos
        $request->validate([
            'nombre' => 'required|string|max:255',
            'id_jornada' => 'required|integer',
        ]);

        // Buscar la carrera por su ID
        $carrera = CarreraModel::find($id);

        // Verificar si la carrera existe
        if (!$carrera) {
            return response()->json([
                "ok" => false,
                "message" => "La carrera no existe.",
            ], 404);
        }

        // Verificar si ya existe una carrera con el mismo nombre y jornada
        $carreraExistente = CarreraModel::where('nombre', $request->nombre)
            ->where('id_jornada', $request->id_jornada)
            ->where('id_carrera', '!=', $id) // Excluir la carrera actual
            ->first();

        if ($carreraExistente) {
            return response()->json([
                "ok" => false,
                "message" => "Ya existe una carrera con el nombre '" . $request->nombre . "' en la misma jornada.",
            ], 400);
        }

        // Actualizar la carrera con la nueva información
        $carrera->update([
            "nombre" => $request->nombre,
            "id_jornada" => $request->id_jornada,
            "id_usuario_creador" => auth()->id() ?? 1,
            "ip_actualizacion" => $request->ip(),
            "fecha_actualizacion" => now(),
        ]);

        // Verificar si hay un registro en la tabla usuario_carrera_jornada
        $usuarioCarreraJornada = \DB::table('usuario_carrera_jornada')
            ->where('id_carrera', $id)
            ->first(); // Solo buscar por el ID de la carrera

        // Si existe un registro y la jornada ha cambiado, actualizar la id_jornada
        if ($usuarioCarreraJornada && $usuarioCarreraJornada->id_jornada !== $request->id_jornada) {
            \DB::table('usuario_carrera_jornada')
                ->where('id_carrera', $id)
                ->update([
                    'id_jornada' => $request->id_jornada,
                ]);
        }

        return response()->json([
            "ok" => true,
            "message" => "Carrera y jornada actualizadas con éxito"
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




    public function showCarrera()
    {
        try {
            $asignatura = CarreraModel::select(
                "carreras.id_carrera",
                "carreras.nombre",
                "jornada.id_jornada",
                "jornada.descripcion as descripcion_jornada",  // Usamos alias para 'descripcion'
                "carreras.estado"
            )
            ->join('jornada', 'carreras.id_jornada', '=', 'jornada.id_jornada')
            ->whereIn("carreras.estado", ["A", "I"])
            ->get();    

            return response()->json([
                "ok" => true,
                "data" => $asignatura
            ], 200);
        } catch (Exception $e) {
            log::error(__FILE__ . " > " . __FUNCTION__);
            log::error("Mensaje : " . $e->getMessage());
            log::error("Linea : " . $e->getLine());

            return response()->json([
                "ok" => false,
                "message" => "Error interno en el servidor"
            ], 500);
        }
    }


}
