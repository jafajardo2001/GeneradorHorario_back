<?php

namespace App\Http\Controllers;

use App\Models\NivelModel;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NivelController extends Controller
{

    public function storeNivelCarrera(Request $request)
    {
        try{
            // Validar que el campo 'descripcion' esté presente
        
            $modelo = new NivelModel();
            

             // Verificar si ya existe un nivel con el mismo número, nemonico y termino
                $nivelExistente = NivelModel::where('numero', $request->numero)
                ->where('nemonico', $request->nemonico)
                ->where('termino', $request->termino)
                ->first();

                if ($nivelExistente) {
                return response()->json([
                    "ok" => false,
                    "msg_error" => "El nivel ya existe con el número " . $request->numero . ", nemonico " . $request->nemonico . " y termino " . $request->termino
                ], 400);
                }
            
            $modelo->numero = $request->numero;
            $modelo->nemonico = $request->nemonico;
            $modelo->termino = $request->termino;
            $modelo->ip_creacion = $request->ip();
            $modelo->ip_actualizacion = $request->ip();
            $modelo->id_usuario_creador = auth()->id() ?? 1;
            $modelo->id_usuario_actualizo = auth()->id() ?? 1;
            $modelo->estado = "A";
            $modelo->save();

            return Response()->json([
                "ok" => true,
                "message" => "Nivel creado con exito"
            ], 500);
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


    public function showNivel()
    {
        try{
            $nivel = NivelModel::select('nivel.id_nivel','nivel.numero','nivel.nemonico','nivel.termino','nivel.estado','usuarios.usuario as usuarios_ultima_gestion','nivel.fecha_actualizacion as fecha_actualizacion')
            ->join('usuarios','nivel.id_usuario_actualizo','usuarios.id_usuario')
            ->whereIn("nivel.estado",["A","I"])
            ->orderBy("nivel.nemonico")
            ->get();
            return Response()->json([
                "ok" => true,
                "data" => $nivel
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

    public function deleteNivel(Request $request, $id)
{
    try {
        // Buscar el nivel por su ID
        $nivel = NivelModel::find($id);
        if (!$nivel) {
            return response()->json([
                "ok" => false,
                "message" => "El nivel no existe con el id $id"
            ], 400);    
        }

        // Verificar si hay distribuciones asociadas
        $distribuciones = \DB::table('distribuciones_horario_academica')
            ->where('id_nivel', $id) // Cambia a 'id_nivel' para verificar el curso
            ->exists();

        // Cambiar el estado del nivel a "E"
        $nivel->estado = "E";  // Cambia el estado a "E"
        $nivel->id_usuario_actualizo = auth()->id() ?? 1;  // Actualiza el usuario que hace el cambio
        $nivel->ip_actualizacion = $request->ip();  // Actualiza la IP
        $nivel->fecha_actualizacion = now(); // Actualiza la fecha de actualización
        $nivel->save();  // Guarda los cambios

        // Inhabilitar distribuciones asociadas si existen
        if ($distribuciones) {
            \DB::table('distribuciones_horario_academica')
                ->where('id_nivel', $id)
                ->update(['estado' => "E"]); // Cambia el estado de las distribuciones a "E"
        }
        
        return response()->json([
            "ok" => true,
            "message" => $distribuciones 
                ? "Nivel y distribuciones eliminadas con éxito" 
                : "Nivel eliminado con éxito"
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

    public function checkDistribucionesPorCurso($id)
    {
        try {
            $distribuciones = \DB::table('distribuciones_horario_academica')
                ->where('id_nivel', $id)
                ->where('estado', "A") // Cambia a 'id_nivel' para verificar el curso
                // Cambia a 'id_nivel' para verificar el curso
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
    
    public function updateNivel(Request $request,$id)
    {
        try{
            $nivel = NivelModel::find($id);
            if(!$nivel){
                return Response()->json([
                    "ok" => true,
                    "message" => "El registro no existe con el id $id"
                ],400);
            }
            NivelModel::find($id)->update([
                "numero" => isset($request->numero)?$request->numero:$nivel->numero,
                "nemonico" => isset($request->nemonico)?$request->nemonico:$nivel->nemonico,
                "termino" => isset($request->termino)?$request->termino:$nivel->termino,
                "id_usuario_actualizo" => auth()->id() ?? 1,
                "ip_actualizo" => $request->ip(),
                "estado" => isset($request->estado) ? $request->estado : "A"
            ]);
            return Response()->json([
                "ok" => true,
                "message" => "Carrera actualizada con exito"
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

}