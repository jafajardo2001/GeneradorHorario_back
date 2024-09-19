<?php

namespace App\Http\Controllers;

use App\Models\CarreraModel;
use App\Models\RolModel;
use App\Models\TituloAcademicoModel;
use App\Models\UsuarioModel;
use App\Services\MensajeAlertasServicio;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
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

            $modelo_carrera = CarreraModel::find($request->id_carrera);
            if (!$modelo_carrera) {
                return response()->json([
                    "ok" => false,
                    "message" => "Está carrera no existe con el id $request->id_carrera"
                ], 400);
            }

            $modelo->id_titulo_academico = $request->id_titulo_academico;
            $modelo->cedula = $request->cedula;
            $modelo->nombres = $nombres;
            $modelo->apellidos = $apellidos;
            $modelo->correo = $request->correo; // Agregar correo
            $modelo->telefono = $request->telefono; // Agregar teléfono
            $modelo->usuario = $usuario;
            $modelo->clave = bcrypt($request->cedula);
            $modelo->id_rol = $request->id_rol;
            $modelo->id_job = $request->id_job;
            $modelo->id_carrera = $request->id_carrera;
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
        try {
            // Almacena logs informativos
            $this->servicio_informe->storeInformativoLogs(__FILE__, __FUNCTION__);

            // Selecciona los datos requeridos
            $usuarios = UsuarioModel::select(
                "usuarios.id_usuario",
                "usuarios.cedula",
                "usuarios.nombres",
                "usuarios.apellidos",
                "usuarios.correo",
                "usuarios.telefono",
                "usuarios.usuario",
                "usuarios.imagen_perfil",
                "rol.id_rol",
                "rol.descripcion as rol_descripcion",  // Rol del usuario
                "job.id_job",
                "job.descripcion as job_descripcion",  // Descripción del job
                "titulo_academico.id_titulo_academico",
                "titulo_academico.descripcion as titulo_academico_descripcion",  // Descripción del título académico
                "carreras.id_carrera",
                "carreras.nombre as carrera_nombre",  // Descripción de la carrera
                "usuarios.estado",
                UsuarioModel::raw("CONCAT(creador.nombres, ' ', creador.apellidos) as creador_nombre_completo")  // Nombre completo del creador
            )
            ->join("rol", "usuarios.id_rol", "=", "rol.id_rol")
            ->leftJoin("job", "usuarios.id_job", "=", "job.id_job")  // Join para jobs
            ->leftJoin("titulo_academico", "usuarios.id_titulo_academico", "=", "titulo_academico.id_titulo_academico")  // Join para título académico
            ->leftJoin("carreras", "usuarios.id_carrera", "=", "carreras.id_carrera")  // Join para carrera
            ->leftJoin("usuarios as creador", "usuarios.id_usuario_creador", "=", "creador.id_usuario")  // Join para obtener creador
            ->where("usuarios.estado", "A")  // Filtra por estado activo
            ->get();

            // Respuesta en JSON
            return Response()->json([
                "ok" => true,
                "data" => $usuarios
            ], 200);
        } catch (Exception $e) {
            // Registro de logs de error
            log::error(__FILE__ . " > " . __FUNCTION__);
            log::error("Mensaje : " . $e->getMessage());
            log::error("Línea : " . $e->getLine());

            // Respuesta en caso de error
            return Response()->json([
                "ok" => false,
                "message" => "Error interno en el servidor"
            ], 500);
        }
    }


    public function showDocentes()
    {
        try {
            // Obtener el ID del rol "Docente"
            $rolDocente = RolModel::select('id_rol')
                ->where('descripcion', '=', 'Docente')
                ->first();

            if (!$rolDocente) {
                return response()->json([
                    "ok" => false,
                    "message" => "Rol Docente no encontrado"
                ], 404);
            }

            // Obtener los usuarios que tienen el rol de "Docente"
            $docentes = UsuarioModel::select(
                "id_usuario",
                "nombres",
                "apellidos",
                "cedula",
                "correo",  // Agregar correo
                "telefono",  // Agregar teléfono
                UsuarioModel::raw("CONCAT(nombres, ' ', apellidos) as nombre_completo"),
                "titulo_academico.descripcion as titulo_academico", // Título académico
                "carreras.nombre as carrera" // Carrera
            )
            ->join('titulo_academico', 'usuarios.id_titulo_academico', '=', 'titulo_academico.id_titulo_academico')
            ->leftjoin('carreras', 'usuarios.id_carrera', '=', 'carreras.id_carrera')
            
            ->where('id_rol', '=', $rolDocente->id_rol)
            ->where('usuarios.estado', '=', 'A')
            ->get();

            return response()->json([
                "ok" => true,
                "data" => $docentes
            ], 200);
        } catch (Exception $e) {
            Log::error(__FILE__ . " > " . __FUNCTION__);
            Log::error("Mensaje : " . $e->getMessage());
            Log::error("Línea : " . $e->getLine());

        if (!$rolDocente) {
            return response()->json([
                "ok" => false,
                "message" => "Rol Docente no encontrado"
            ], 404);
        }

        // Obtener los usuarios que tienen el rol de "Docente"
        $docentes = UsuarioModel::select(
            "usuarios.id_usuario",
            "usuarios.nombres",
            "usuarios.apellidos",
            "usuarios.cedula",
            UsuarioModel::raw("CONCAT(usuarios.nombres, ' ', usuarios.apellidos) as nombre_completo"),
            "titulo_academico.descripcion as titulo_academico",
            'carreras.nombre as carrera'
        )
        ->join('titulo_academico', 'usuarios.id_titulo_academico', '=', 'titulo_academico.id_titulo_academico')
        ->leftjoin('carreras', 'usuarios.id_carrera', '=', 'carreras.id_carrera')
        ->where('usuarios.id_rol', '=', $rolDocente->id_rol)
        ->where('usuarios.estado', '=', 'A')
        ->get();

        return response()->json([
            "ok" => true,
            "data" => $docentes
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
            $usuario->update([
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
    public function updateUsuario(Request $request, $id)
    {
        try {
            // Validar los datos entrantes
            $validatedData = $request->validate([
                'cedula' => 'required|string|max:10',
                'nombres' => 'required|string|max:255',
                'apellidos' => 'required|string|max:255',
                'perfil' => 'required|integer',
            ]);

            // Buscar el usuario por ID
            $usuario = UsuarioModel::findOrFail($id);

            // Generar el nuevo valor para el campo 'usuario'
            $usuarioStr = strtolower(substr($validatedData['nombres'], 0, 1) . substr($validatedData['apellidos'], 0, 1));

            // Actualizar los campos permitidos
            $usuario->cedula = $validatedData['cedula'];
            $usuario->nombres = $validatedData['nombres'];
            $usuario->apellidos = $validatedData['apellidos'];
            $usuario->perfil = $validatedData['perfil'];
            $usuario->usuario = $usuarioStr;

            // Guardar los cambios
            $usuario->save();

            return response()->json([
                'ok' => true,
                'message' => 'Usuario actualizado correctamente',
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Usuario no encontrado',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Error en la validación de los datos',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Error al actualizar el usuario',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            // Valida que los campos requeridos estén presentes
            $validatedData = $request->validate([
                'correo' => 'required|string',
                'clave' => 'required|string',
            ]);

            // Busca el usuario por el correo
            $correo = UsuarioModel::where('correo', $validatedData['correo'])->first();

            if (!$correo) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            // Verifica si la contraseña es correcta
            if (!Hash::check($validatedData['clave'], $correo->clave)) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Contraseña incorrecta'
                ], 401);
            }

            // Si todo está correcto, puede generar un token o iniciar sesión
            // En este caso, simplemente devuelves una respuesta de éxito
            return response()->json([
                'ok' => true,
                'message' => 'Inicio de sesión exitoso',
                'usuario' => $correo  // O cualquier dato que necesites devolver
            ], 200);

        } catch (\Exception $e) {
            // Captura cualquier excepción y retorna un mensaje de error
            return response()->json([
                'ok' => false,
                'message' => 'Error en el servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }



/*public function show($id)
{
    try {
        $usuario = UsuarioModel::select(
            "id_usuario",
            "cedula",
            "nombres",
            "apellidos",
            "usuario",
            "imagen_perfil",
            "rol.id_rol",
            "rol.descripcion",
            "usuarios.estado",
            UsuarioModel::raw("CONCAT(creador.nombres, ' ', creador.apellidos) as creador_nombre_completo")
        )
        ->join("rol", "usuarios.id_rol", "=", "rol.id_rol")
        ->join("usuarios as creador", "usuarios.id_usuario_creador", "=", "creador.id_usuario")
        ->where("usuarios.id_usuario", $id)
        ->first();

        if (!$usuario) {
            return response()->json([
                "ok" => false,
                "message" => "Usuario no encontrado"
            ], 404);
        }

        return response()->json([
            "ok" => true,
            "data" => $usuario
        ], 200);
    } catch (Exception $e) {
        Log::error("Error en show: " . $e->getMessage());
        return response()->json([
            "ok" => false,
            "message" => "Error interno en el servidor"
        ], 500);
    }
}*/
}
