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
            $modelo = new NivelModel();
            $campos_requeridos = $modelo->getFillable();
            $campos_recibidos = array_keys($request->all());
            $campos_faltantes = array_diff($campos_requeridos, $campos_recibidos);
        
            if (!empty(array_diff($campos_requeridos, $campos_recibidos))) {
                return response()->json([
                    "ok" => false,
                    "message" => "Los siguientes campos son obligatorios: " . implode(', ', $campos_faltantes)
                ], 400);
            }

             // Validar si el nivel ya existe
             $nivelExistente = NivelModel::where('numero', $request->numero)
             ->where('nemonico', $request->nemonico)
             ->where('termino', $request->termino)
             ->first();

            if ($nivelExistente) {
                return response()->json([
                    "ok" => false,
                    "message" => "El nivel ya existe"
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

    public function deleteNivel(Request $request,$id)
    {  
        try{
            $asignatura = NivelModel::find($id);
            if(!$asignatura){
                return Response()->json([
                    "ok" => true,
                    "message" => "El nivel no existe con el id $id"
                ], 400);    
            }
            
            NivelModel::find($id)->updated([
                "estado" => "E",
                "id_usuario_actualizo" => auth()->id() ?? 1,
                "ip_actualizo" => $request->ip(),

            ]);

            return Response()->json([
                "ok" => true,
                "message" => "Nivel eliminado con exito"
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