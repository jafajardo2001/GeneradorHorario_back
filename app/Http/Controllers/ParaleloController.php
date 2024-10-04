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
        try{
            
            $modelo = new ParaleloModel();
           

            // Validar si el paralelo ya existe
            $paraleloExistente = ParaleloModel::where('paralelo', $request->paralelo)->first();
            if ($paraleloExistente) {
                return response()->json([
                    "ok" => false,
                    "message" => "El paralelo ya existe"
                ], 400);
            }
            
            $modelo->paralelo = $request->paralelo;
            $modelo->ip_creacion = $request->ip();
            $modelo->ip_actualizacion = $request->ip();
            $modelo->id_usuario_creador = auth()->id() ?? 1;
            $modelo->id_usuario_actualizo = auth()->id() ?? 1;
            $modelo->estado = "A";
            $modelo->save();
            return Response()->json([
                "ok" => true,
                "message" => "Paralelo creado Exitosamente"
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


    public function deleteParalelo(Request $request,$id)
    {  
        try{
            $paralelo = ParaleloModel::find($id);
            if(!$paralelo){
                return Response()->json([
                    "ok" => true,
                    "message" => "El paralelo no existe con el id $id"
                ], 400);    
            }
            
            ParaleloModel::find($id)->update([
                "estado" => "E",
                "id_usuario_actualizo" => auth()->id() ?? 1,
                "ip_actualizo" => $request->ip(),
                "fecha_actualizacion" => now(),
            ]);

            return Response()->json([
                "ok" => true,
                "message" => "Paralelo eliminado con exito"
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

    public function updateParalelo(Request $request,$id)
    {
        try{
            $insituto = ParaleloModel::find($id);

            if ($insituto) {
                return Response()->json(
                    [
                        "ok" => true,
                        "message" => "No existe un paralelo con el id $id"
                    ],400
                );
            }

            $modelo = ParaleloModel::where("id_insituto", $id)->update([
                "paralelo" => $request->paralelo,
                "id_usuario_actualizo" => auth()->id() ?? 1,
                "ip_actualizo" => $request->ip(),
                "estado" => isset($request->estado) ? $request->estado : "A"
            ]);


            return Response()->json([
                "ok" => true,
                "message" => "Paralelo actualizado con exito"
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