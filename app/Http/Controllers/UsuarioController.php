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
        Log::info('Iniciando creación o actualización de usuario.');

        // Validar campos requeridos
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

        // Verificar si ya existe el usuario
        $usuarioExistente = UsuarioModel::where('cedula', $request->cedula)
            ->where('nombres', $request->nombres)
            ->where('apellidos', $request->apellidos)
            ->first();

        Log::info('Verificación de existencia de usuario completada.', ['usuarioExistente' => $usuarioExistente]);

        if ($usuarioExistente) {
            // Obtener las carreras que ya están asociadas al usuario, especificando las tablas
            $carrerasExistentes = $usuarioExistente->carreras()->select('usuario_carrera.id_carrera')->pluck('id_carrera')->toArray();
            Log::info('Carreras existentes del usuario.', ['carrerasExistentes' => $carrerasExistentes]);

            // Verificar si alguna de las carreras ya está asignada
            $carrerasDuplicadas = array_intersect($carrerasExistentes, $request->id_carreras);
            Log::info('Carreras duplicadas encontradas.', ['carrerasDuplicadas' => $carrerasDuplicadas]);

            if (!empty($carrerasDuplicadas)) {
                return response()->json([
                    "ok" => false,
                    "message" => "El usuario ya está inscrito en alguna de las carreras seleccionadas."
                ], 400);
            }

            // Agregar las nuevas carreras sin duplicar
            $usuarioExistente->carreras()->syncWithoutDetaching($request->id_carreras);

            return response()->json([
                "ok" => true,
                "message" => "Carreras adicionales asignadas exitosamente."
            ], 200);

        } else {
            Log::info('El usuario no existe, creando uno nuevo.');
            // Crear un nuevo usuario si no existe
            $nombres = ucfirst(trim($request->nombres));
            $apellidos = ucfirst(trim($request->apellidos));

            $modelo->cedula = $request->cedula;
            $modelo->nombres = $nombres;
            $modelo->apellidos = $apellidos;
            $modelo->correo = $request->correo;
            $modelo->telefono = $request->telefono;
            $modelo->clave = bcrypt($request->cedula);
            $modelo->id_rol = $request->id_rol;
            $modelo->id_job = $request->id_job;
            $modelo->id_titulo_academico = $request->id_titulo_academico;
            $modelo->usuario = strtolower(explode(' ', $nombres)[0] . explode(' ', $apellidos)[0]);
            $modelo->ip_creacion = $request->ip();
            $modelo->ip_actualizacion = $request->ip();
            $modelo->id_usuario_creador = auth()->id() ?? 1;
            $modelo->id_usuario_actualizo = auth()->id() ?? 1;
            $modelo->estado = "A";
            $modelo->save();

            // Asignar carreras al usuario recién creado sin duplicar
            if ($request->id_carreras && is_array($request->id_carreras)) {
                $modelo->carreras()->syncWithoutDetaching($request->id_carreras);
            }

            return response()->json([
                "ok" => true,
                "message" => "Usuario creado y carreras asignadas con éxito."
            ], 200);
        }

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



    // Ejemplo de endpoint en Laravel
    public function obtenerDocentesPorCarrera($idCarrera)
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

            // Obtener los usuarios que tienen el rol de "Docente" y están asociados con la carrera seleccionada
            $docentes = UsuarioModel::select(
                "usuarios.id_usuario",
                "usuarios.nombres",
                "usuarios.apellidos",
                "usuarios.cedula",
                "usuarios.correo",  // Agregar correo
                "usuarios.telefono",  // Agregar teléfono
                UsuarioModel::raw("CONCAT(usuarios.nombres, ' ', usuarios.apellidos) as nombre_completo"),
                "titulo_academico.descripcion as titulo_academico" // Título académico
            )
            ->join('usuario_carrera', 'usuarios.id_usuario', '=', 'usuario_carrera.id_usuario')  // Unir con la tabla de usuario_carrera
            ->join('titulo_academico', 'usuarios.id_titulo_academico', '=', 'titulo_academico.id_titulo_academico')  // Unir con la tabla de títulos académicos
            ->where('usuario_carrera.id_carrera', '=', $idCarrera)  // Filtrar por la carrera seleccionada
            ->where('usuarios.id_rol', '=', $rolDocente->id_rol)  // Filtrar por el rol Docente
            ->where('usuarios.estado', '=', 'A')  // Solo usuarios activos
            ->get();

            // Validar si no se encontraron docentes
            if ($docentes->isEmpty()) {
                return response()->json([
                    "ok" => false,
                    "message" => "No se encontraron docentes para esta carrera"
                ], 404);
            }

            // Retornar los docentes en formato JSON
            return response()->json([
                "ok" => true,
                "data" => $docentes
            ], 200);

        } catch (\Exception $e) {
            // Manejo de errores
            return response()->json([
                "ok" => false,
                "message" => "Error en el servidor"
            ], 500);
        }
    }

    



    public function showUsuarios()
    {
        try {
            $this->servicio_informe->storeInformativoLogs(__FILE__, __FUNCTION__);

            // Selecciona los datos requeridos, incluyendo las carreras asociadas al usuario
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
                "rol.descripcion as rol_descripcion",
                "job.id_job",
                "job.descripcion as job_descripcion",
                "titulo_academico.id_titulo_academico",
                "titulo_academico.descripcion as titulo_academico_descripcion",
                "usuarios.estado",
                UsuarioModel::raw("CONCAT(creador.nombres, ' ', creador.apellidos) as creador_nombre_completo")
            )
            ->join("rol", "usuarios.id_rol", "=", "rol.id_rol")
            ->leftJoin("job", "usuarios.id_job", "=", "job.id_job")
            ->leftJoin("titulo_academico", "usuarios.id_titulo_academico", "=", "titulo_academico.id_titulo_academico")
            ->leftJoin("usuarios as creador", "usuarios.id_usuario_creador", "=", "creador.id_usuario")
            ->where("usuarios.estado", "A")
            ->with(['carreras' => function ($query) {
                $query->select('carreras.id_carrera', 'carreras.nombre');  // Obtener los campos necesarios de las carreras
            }])
            ->get();

            return response()->json([
                "ok" => true,
                "data" => $usuarios
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
            )
            ->join('titulo_academico', 'usuarios.id_titulo_academico', '=', 'titulo_academico.id_titulo_academico')
            
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
        )
        ->join('titulo_academico', 'usuarios.id_titulo_academico', '=', 'titulo_academico.id_titulo_academico')
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
                'correo' => 'required|email',
                'telefono' => 'required|string|max:15',
                'id_titulo_academico' => 'nullable|array', // Puede ser uno o más títulos académicos
                'id_carreras' => 'nullable|array',  // Puede ser una o más carreras
                'id_rol' => 'required|integer',
            ]);

            // Buscar el usuario por ID
            $usuario = UsuarioModel::findOrFail($id);

            // Actualizar los campos básicos del usuario
            $usuario->cedula = $validatedData['cedula'];
            $usuario->nombres = ucfirst(trim($validatedData['nombres']));
            $usuario->apellidos = ucfirst(trim($validatedData['apellidos']));
            $usuario->correo = $validatedData['correo'];
            $usuario->telefono = $validatedData['telefono'];
            $usuario->id_rol = $validatedData['id_rol'];
            $usuario->ip_actualizacion = $request->ip();
            $usuario->id_usuario_actualizo = auth()->id() ?? 1;

            // Actualizar el campo 'usuario' (nombre de usuario)
            $usuario->usuario = strtolower(substr($validatedData['nombres'], 0, 1) . substr($validatedData['apellidos'], 0, 1));

            // Guardar los cambios en el usuario
            $usuario->save();

            // Asignar carreras, si se proporcionaron
            if (!empty($validatedData['id_carreras'])) {
                $usuario->carreras()->sync($validatedData['id_carreras']); // Actualiza carreras, eliminando las anteriores si es necesario
            }

            // Asignar títulos académicos, si se proporcionaron (puede ser más de uno)
            if (!empty($validatedData['id_titulo_academico'])) {
                // Si tienes una tabla de relación entre usuarios y títulos académicos, usa sync para agregar/actualizar títulos
                $usuario->titulosAcademicos()->sync($validatedData['id_titulo_academico']);
            }

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

}
