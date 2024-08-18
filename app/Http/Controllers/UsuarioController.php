<?php

namespace App\Http\Controllers;

use App\Models\RolModel;
use App\Models\TituloAcademicoModel;
use App\Models\UsuarioModel;
use App\Services\MensajeAlertasServicio;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UsuarioController extends Controller
{
    private $servicio_informe;

    public function __construct()
    {
        $this->servicio_informe = new MensajeAlertasServicio();
    }
    public function storeUsuarios(Request $request)
    {
        try {
            $this->servicio_informe->storeInformativoLogs(__FILE__, __FUNCTION__);

            $modelo = new UsuarioModel();
            $campos_requeridos = $modelo->getFillable();
            $campos_recibidos = array_keys($request->all());
            $campos_faltantes = array_diff($campos_requeridos, $campos_recibidos);
            if (!empty($campos_faltantes)) {
                return response()->json([
                    "ok" => false,
                    "message" => "Los siguientes campos son obligatorios: " . implode(', ', $campos_faltantes)
                ], 400);
            }

            $usuarioExistente = UsuarioModel::where("cedula", $request->cedula)->first();
            if ($usuarioExistente) {
                $estadoMensaje = [
                    "A" => "El usuario ya existe con el número de cédula.",
                    "I" => "El usuario ya existe con el número de cédula pero está inactivo.",
                    "E" => "Este usuario fue eliminado."
                ];

                return response()->json([
                    "ok" => false,
                    "message" => $estadoMensaje[$usuarioExistente->estado] ?? "Error desconocido"
                ], 400);
            }

            $nombres = explode(" ", trim(strtolower($request->nombres)));
            $apellidos = explode(" ", trim(strtolower($request->apellidos)));

            if (count($nombres) < 2 || count($apellidos) < 2) {
                return response()->json([
                    "ok" => false,
                    "message" => "Error en limpiar los nombres o apellidos, verifique si está llenando bien los campos."
                ], 400);
            }

            $usuario = ucfirst(trim($nombres[0][0])) . ucfirst(trim($apellidos[0])) . ucfirst(trim($apellidos[1]));
            $nombres = ucfirst(trim($nombres[0])) . " " . ucfirst(trim($nombres[1]));
            $apellidos = ucfirst(trim($apellidos[0])) . " " . ucfirst(trim($apellidos[1]));

            $modelo_rol = RolModel::find($request->id_rol);
            if (!$modelo_rol) {
                return response()->json([
                    "ok" => false,
                    "message" => "El rol no existe con el id $request->id_rol"
                ], 400);
            }

            $modelo_titulo = TituloAcademicoModel::find($request->id_titulo_academico);
            if (!$modelo_titulo) {
                return response()->json([
                    "ok" => false,
                    "message" => "El título académico no existe con el id $request->id_titulo_academico"
                ], 400);
            }

            $modelo->id_titulo_academico = $request->id_titulo_academico;
            $modelo->cedula = $request->cedula;
            $modelo->nombres = $nombres;
            $modelo->apellidos = $apellidos;
            $modelo->usuario = $usuario;
            $modelo->clave = bcrypt($request->cedula);
            $modelo->id_rol = $request->id_rol;
            $modelo->ip_creacion = $request->ip();
            $modelo->ip_actualizacion = $request->ip();
            $modelo->id_usuario_creador = auth()->id() ?? 1;
            $modelo->id_usuario_actualizo = auth()->id() ?? 1;
            $modelo->imagen_perfil = null;
            $modelo->estado = "A";
            $modelo->save();

            return response()->json([
                "ok" => true,
                "message" => "Usuario creado con éxito"
            ], 200);
        } catch (Exception $e) {
            Log::error(__FILE__ . " > " . __FUNCTION__);
            Log::error("Mensaje : " . $e->getMessage());
            Log::error("Línea : " . $e->getLine());

            return response()->json([
                "ok" => false,
                "message" => "Error interno en el servidor"
            ], 500);
        }
    }

    public function showUsuarios()
    {
        try{
            $this->servicio_informe->storeInformativoLogs(__FILE__,__FUNCTION__);$this->servicio_informe->storeInformativoLogs(__FILE__,__FUNCTION__);
            $usuarios = UsuarioModel::select("id_usuario","cedula","nombres"
            ,"apellidos","usuario",
            "imagen_perfil","rol.id_rol", "rol.descripcion as rol_descripcion",
            "titulo_academico.id_titulo_academico", "titulo_academico.descripcion")
            ->join("rol","usuarios.id_rol","=","rol.id_rol")
            ->join("titulo_academico", "usuarios.id_titulo_academico", "=", "titulo_academico.id_titulo_academico")
            ->get();
            return Response()->json([
                "ok" => true,
                "data" => $usuarios
            ], 200);
        }catch (Exception $e) {
            log::error( __FILE__ . " > " . __FUNCTION__);
            log::error("Mensaje : " . $e->getMessage());
            log::error("Linea : " . $e->getLine());

            return Response()->json([
                "ok" => true,
                "message" => "Error interno en el servidor"
            ], 500);
        }
    }

    public function deleteUsuario(Request $request,$id)
    {
        try{
            $this->servicio_informe->storeInformativoLogs(__FILE__,__FUNCTION__);
            $usuario = UsuarioModel::find($id);
            if(!$usuario){
                return Response()->json([
                    "ok" => true,
                    "message" => "El usuario con id  $request->id_rol no existe"
                ], 400);
            }
            $usuario->updated([
                "estado" => "E",
                "id_usuario_actualizo" => auth()->id(),
                "ip_actualizo" => $request->ip(),
            ]);

            return Response()->json([
                "ok" => true,
                "data" => "Usuario eliminado con exito"
            ], 200);
        }catch (Exception $e) {
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
