<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use App\Http\Responses\TypeResponse;
use App\Models\AsignaturaModel;
use Exception;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;

class AsignaturaController extends Controller
{
    public function storeAsignatura(Request $request)
{
    try {
        // Validar que los campos 'descripcion' y 'id_categoria' estén presentes
        $validatedData = $request->validate([
            'descripcion' => 'required|string|max:255',
            'id_categoria' => 'required|integer|exists:categorias,id_categoria', // Asegúrate de que `categorias` es la tabla correcta y `id` es el campo
        ]);

        // Verificar si ya existe una asignatura con la misma descripción
        $asignaturaExistente = AsignaturaModel::where('descripcion', $request->descripcion)->first();

        if ($asignaturaExistente) {
            return response()->json([
                "ok" => false,
                "msg_error" => "La materia ya existe con la descripción " . $request->descripcion
            ], 400);
        }

        // Crear una nueva asignatura si no existe
        $modelo = new AsignaturaModel();
        $modelo->descripcion = $request->descripcion;
        $modelo->id_categoria = $request->id_categoria; // Añadir el ID de la categoría
        $modelo->ip_creacion = $request->ip();
        $modelo->ip_actualizacion = $request->ip();
        $modelo->id_usuario_creador = auth()->id() ?? 1;
        $modelo->id_usuario_actualizo = auth()->id() ?? 1;
        $modelo->estado = "A";
        $modelo->save();

        return response()->json([
            "ok" => true,
            "message" => "Materia '" . $modelo->descripcion . "' creada con éxito"
        ], 200);

    } catch (Exception $e) {
        Log::error(__FILE__ . " > " . __FUNCTION__);
        Log::error("Mensaje: " . $e->getMessage());
        Log::error("Línea: " . $e->getLine());

        return response()->json([
            "ok" => false,
            "msg_error" => "Error interno en el servidor"
        ], 500);
    }
}




    public function deleteAsignatura(Request $request, $id)
{
    try {
        $asignatura = AsignaturaModel::find($id);

        if (!$asignatura) {
            return response()->json([
                "ok" => false,
                "message" => "La asignatura no existe con el id $id"
            ], 400);
        }

        $result = $asignatura->update([
            "estado" => "E",
            "id_usuario_creador" => auth()->id() ?? 1,
            "ip_actualizacion" => $request->ip(),
            "fecha_actualizacion" => now(),  // Estado cambiado a "E" para marcarlo como desactivado
        ]);

        if ($result) {
            return response()->json([
                "ok" => true,
                "message" => "materia eliminada con éxito"
            ], 200);
        } else {
            return response()->json([
                "ok" => false,
                "message" => "No se pudo eliminar la materia"
            ], 400);
        }

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




public function updateAsignatura(Request $request, $id)
{
    try {
        $asignatura = AsignaturaModel::find($id);
        if (!$asignatura) {
            return response()->json([
                "ok" => false,
                "message" => "El registro no existe con el id $id"
            ], 400);
        }

        // Verificar si la nueva descripción ya existe en otra asignatura
        $asignaturaExistente = AsignaturaModel::where('descripcion', ucfirst(trim($request->descripcion)))
            ->where('id_materia', '!=', $id) // Excluir la asignatura actual de la búsqueda
            ->where('estado', 'A') // Considerar solo asignaturas activas
            ->first();

        if ($asignaturaExistente) {
            return response()->json([
                "ok" => false,
                "message" => "La descripción de la asignatura ya existe."
            ], 400);
        }

        // Actualizar la asignatura
        $asignatura->update([
            "descripcion" => isset($request->descripcion) ? ucfirst(trim($request->descripcion)) : $asignatura->descripcion,
            "id_categoria" => isset($request->categoria) ? $request->categoria : $asignatura->id_categoria, // Actualizar la categoría
            "id_usuario_actualizo" => auth()->id() ?? 1,
            "ip_actualizo" => $request->ip(),
            "estado" => isset($request->estado) ? $request->estado : "A"
        ]);

        return response()->json([
            "ok" => true,
            "message" => "Asignatura actualizada con éxito"
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


public function showAsignatura()
{
    try {
        // Asegúrate de incluir la relación para obtener el nombre de la categoría
        $asignaturas = AsignaturaModel::select(
                "materias.id_materia",
                "materias.descripcion",
                "materias.fecha_creacion",
                "materias.estado",
                "materias.fecha_actualizacion",
                "categorias.nombre as categoria_nombre" // Incluye el nombre de la categoría
            )
            ->join('categorias', 'materias.id_categoria', '=', 'categorias.id_categoria') // Asegúrate de que la relación esté bien definida
            ->whereIn("materias.estado", ["A", "I"])
            ->get();

        return response()->json([
            "ok" => true,
            "data" => $asignaturas
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


}
