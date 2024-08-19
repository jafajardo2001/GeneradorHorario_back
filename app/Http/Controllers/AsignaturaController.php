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
        try{
            if (!isset($request->descripcion)) {
                return Response()->json([
                    "ok" => true,
                    "message" => "El campo de descripcion es obligatorio"
                ], 400);
            }

            $modelo = new AsignaturaModel();
            $modelo->descripcion = $request->descripcion;
            $modelo->ip_creacion = $request->ip();
            $modelo->ip_actualizacion = $request->ip();
            $modelo->id_usuario_creador = auth()->id() ?? 1;
            $modelo->id_usuario_actualizo = auth()->id() ?? 1;
            $modelo->estado = "A";
            $modelo->save();

            return Response()->json([
                "ok" => true,
                "message" => "Asignatura creada con exito"
            ], 200);

        }catch(Exception $e){
            log::error( __FILE__ . " > " . __FUNCTION__);
            log::error("Mensaje : " . $e->getMessage());
            log::error("Linea : " . $e->getLine());

            return Response()->json([
                "ok" => true,
                "message" => "Error interno en el servidor"
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
            "estado" => "I",
            "id_usuario_creador" => auth()->id() ?? 1,
            "ip_actualizacion" => $request->ip(),
            "fecha_actualizacion" => now(),  // Estado cambiado a "E" para marcarlo como desactivado
        ]);

        if ($result) {
            return response()->json([
                "ok" => true,
                "message" => "Asignatura desactivada con éxito"
            ], 200);
        } else {
            return response()->json([
                "ok" => false,
                "message" => "No se pudo desactivar la asignatura"
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
public function updateEstadoAsignatura(Request $request, $id)
{
    try {
        // Buscar la asignatura por su ID
        $asignatura = AsignaturaModel::find($id);

        if (!$asignatura) {
            return response()->json([
                "ok" => false,
                "message" => "La asignatura no existe con el id $id"
            ], 400);
        }

        // Alternar el estado de la asignatura
        $nuevoEstado = $asignatura->estado === 'A' ? 'I' : 'A';
        $asignatura->update([
            "estado" => $nuevoEstado,
            "fecha_actualizacion" => now(),
            "ip_actualizacion" => $request->ip(),
            "id_usuario_creador" => auth()->id() ?? 1,
        ]);

        $accion = $nuevoEstado === 'A' ? 'activada' : 'inactivada';

        return response()->json([
            "ok" => true,
            "message" => "Asignatura $accion con éxito"
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



    public function updateAsignatura(Request $request,$id)
    {
        try{
            $asignatura = AsignaturaModel::find($id);
            if(!$asignatura){
                return Response()->json([
                    "ok" => true,
                    "message" => "El registro no existe con el id $id"
                ],400);
            }

            AsignaturaModel::find($id)->update([
                "descripcion" => isset($request->descripcion)?$request->descripcion:$asignatura->descripcion,
                "id_usuario_actualizo" => auth()->id() ?? 1,
                "ip_actualizo" => $request->ip(),
                "estado" => isset($request->estado) ? $request->estado : "A"
            ]);
            return Response()->json([
                "ok" => true,
                "message" => "Asignatura actualizada con exito"
            ],200);
        }catch(Exception $e){
            log::error( __FILE__ . " > " . __FUNCTION__);
            log::error("Mensaje : " . $e->getMessage());
            log::error("Linea : " . $e->getLine());

            return Response()->json([
                "ok" => true,
                "message" => "Error interno en el servidor"
            ], 500);
        }
    }

    public function showAsignatura()
    {
        try{
            $asignatura = AsignaturaModel::select("id_materia","descripcion","fecha_creacion","estado","fecha_actualizacion")->whereIn("estado",["A","I"])->get();
            return Response()->json([
                "ok" => true,
                "data" => $asignatura
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
}
