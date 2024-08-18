<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TituloAcademicoModel;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Request as request_ip;
use Illuminate\Support\Facades\Log;

class TituloAcademicoController extends Controller
{

    public function getTituloAcademico(Request $request)
    {
        try{
            log::info("Peticion entrante " . __FILE__ ." -> ". __FUNCTION__ . " ip " . request_ip::ip());
            return Response()->json([
                "ok" => true,
                "data" => TituloAcademicoModel::when(isset($request->estado),function ($query) use($request){
                    if(is_array($request->estado)){
                        return $query->whereIn("estado",$request->estado);
                    }else{
                        return $query->where("estado","A");
                    }
                })->get(),
                "mensaje" => "Operacion realizada con exito"
            ],202);
        }catch(Exception $e){
            log::error(__FILE__ . __FUNCTION__ . " MENSAJE => " . $e->getMessage());
            return Response()->json([
                "ok" => false,
                "mensaje" => "Error interno en el servidor"
            ],505);
        }
    }

    public function storeTituloAcademico(Request $request)
    {
        try {
            log::info("Petición entrante " . __FILE__ . " -> " . __FUNCTION__ . " ip " . request_ip::ip());

            if (!$request->input('descripcion')) {
                log::alert(__FILE__ . " -> " . __FUNCTION__ . " el parámetro descripción es obligatorio");
                return response()->json([
                    "ok" => false,
                    "mensaje" => "Hace falta el parámetro descripción"
                ], 400);
            }

            // Validar si el título académico ya existe
            $tituloExistente = TituloAcademicoModel::where('descripcion', $request->input('descripcion'))->first();

            if ($tituloExistente) {
                return response()->json([
                    "ok" => false,
                    "mensaje" => "El título académico ya existe"
                ], 400);
            }

            $modelo = new TituloAcademicoModel();
            $modelo->descripcion = $request->input('descripcion');
            $modelo->ip_creacion = request_ip::ip();
            $modelo->id_usuario_creador = auth()->id() ?? 1;
            $modelo->fecha_creacion = Carbon::now();
            $modelo->save();

            log::info("Operación realizada con éxito");
            return response()->json([
                "ok" => true,
                "mensaje" => "Título académico creado con éxito"
            ], 201);

        } catch (Exception $e) {
            log::error(__FILE__ . __FUNCTION__ . " MENSAJE => " . $e->getMessage());
            return response()->json([
                "ok" => false,
                "mensaje" => "Error interno en el servidor"
            ], 500);
        }
    }




    public function updateTituloAcademico(Request $request,$id){
        try{
            log::info("Peticion entrante " . __FILE__ ." -> ". __FUNCTION__ . " ip " . request_ip::ip());
            $modelo = TituloAcademicoModel::find($id);
            if(!$modelo){
                return Response()->json([
                    "ok" => false,
                    "mensaje" => "Error el registro no existe"
                ],404);
            }
            $descripcion = $request->input('descripcion') ?? null;
            $campos_actualizar = [
                "id_usuario_actualizo"  => auth()->id() ?? 1,
                "fecha_actualizacion" => Carbon::now()
            ];
            if($descripcion){
                $campos_actualizar["descripcion"] = $descripcion;
            }
            $modelo = TituloAcademicoModel::find($id)->update($campos_actualizar);
        }catch(Exception $e){
            log::error(__FILE__ . __FUNCTION__ . " MENSAJE => " . $e->getMessage());
            return Response()->json([
                "ok" => false,
                "mensaje" => "Error interno en el servidor"
            ],505);
        }finally{
            return Response()->json([
                "ok" => true,
                "mensaje" => "Titulo academico actualizado exitosamente."
            ],202);
        }
    }

    public function delete(Request $request)
    {
        try{
            $id = $request->input('id') ?? null;
            if(!$id){
                return Response()->json([
                    "ok" => false,
                    "mensaje" => "Error falta el parametro del id"
                ],404);
            }
            $modelo = TituloAcademicoModel::find($id);
            if(!$modelo){
                return Response()->json([
                    "ok" => false,
                    "mensaje" => "Error el registro no existe"
                ],404);
            }
            $modelo = TituloAcademicoModel::find($id)->update([
                "estado" => "E",
                "id_usuario_actualizo"  => auth()->id() ?? 1,
                "fecha_actualizacion" => Carbon::now()
            ]);
        }catch(Exception $e){
            return Response()->json([
                "ok" => false,
                "mensaje" => "Error interno en el servidor"
            ],505);
        }finally{
            return Response()->json([],202);
        }
    }
}
