<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;
use App\Http\Responses\TypeResponse;

use App\Services\Validaciones;
use Exception;
use Illuminate\Support\Facades\Log;
class CategoriaController extends Controller
{
    //
    public function showCategorias()
    {
        try{
            $categoria = Categoria::select("id_categoria","nombre")->get();
            return Response()->json([
                "ok" => true,
                "data" => $categoria
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
