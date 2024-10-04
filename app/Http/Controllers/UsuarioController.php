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
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\ModelNotFoundException;

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

        // Validar que se reciban carreras con sus jornadas
        if (!$request->has('carreras_jornadas') || !is_array($request->carreras_jornadas)) {
            return response()->json([
                "ok" => false,
                "message" => "Es necesario enviar las carreras con sus respectivas jornadas."
            ], 400);
        }

        // Verificar si ya existe el usuario
        $usuarioExistente = UsuarioModel::where('cedula', $request->cedula)
            ->where('nombres', $request->nombres)
            ->where('apellidos', $request->apellidos)
            ->first();

        Log::info('Verificación de existencia de usuario completada.', ['usuarioExistente' => $usuarioExistente]);

        if ($usuarioExistente) {
            // Obtener las combinaciones de carrera y jornada existentes del usuario
            $carrerasExistentes = $usuarioExistente->carreras()
                ->select('usuario_carrera_jornada.id_carrera', 'usuario_carrera_jornada.id_jornada')
                ->get()->toArray();

            Log::info('Carreras y jornadas existentes del usuario.', ['carrerasExistentes' => $carrerasExistentes]);

            // Verificar si alguna combinación ya está asignada
            $carrerasDuplicadas = [];
            foreach ($request->carreras_jornadas as $cj) {
                if (in_array(['id_carrera' => $cj['id_carrera'], 'id_jornada' => $cj['id_jornada']], $carrerasExistentes)) {
                    $carrerasDuplicadas[] = $cj;
                }
            }

            Log::info('Carreras duplicadas encontradas.', ['carrerasDuplicadas' => $carrerasDuplicadas]);

            if (!empty($carrerasDuplicadas)) {
                return response()->json([
                    "ok" => false,
                    "message" => "El usuario ya está inscrito en alguna de las combinaciones de carrera y jornada seleccionadas."
                ], 400);
            }

            // Agregar las nuevas combinaciones de carrera y jornada sin duplicar
            foreach ($request->carreras_jornadas as $cj) {
                $usuarioExistente->carreras()->syncWithoutDetaching([$cj['id_carrera'] => ['id_jornada' => $cj['id_jornada']]]);
            }

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

            // Asignar carreras y jornadas al usuario recién creado
            foreach ($request->carreras_jornadas as $cj) {
                // Asegúrate de que la relación esté configurada correctamente
                $modelo->carreras()->syncWithoutDetaching([$cj['id_carrera'] => ['id_jornada' => $cj['id_jornada']]]);
            }

            return response()->json([
                "ok" => true,
                "message" => "Usuario creado y carreras con jornadas asignadas con éxito."
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
             //->with(['carreras' => function ($query) {
             //    $query->select('carreras.id_carrera', 'carreras.nombre');  // Obtener los campos necesarios de las carreras
            // }])
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

    public function showCoordinadorC()
    {
        try {
            // Obtener el ID del rol "Docente"
            $rolCoordinador = RolModel::select('id_rol')
                ->where('descripcion', '=', 'Coordinador de Carrera')
                ->first();

            if (!$rolCoordinador) {
                return response()->json([
                    "ok" => false,
                    "message" => "Rol Coordinador de Carrera no encontrado"
                ], 404);
            }

            // Obtener los usuarios que tienen el rol de "Docente"
            $coordinadores = UsuarioModel::select(
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
            
            ->where('id_rol', '=', $rolCoordinador->id_rol)
            ->where('usuarios.estado', '=', 'A')
            ->get();

            return response()->json([
                "ok" => true,
                "data" => $coordinadores
            ], 200);
        } catch (Exception $e) {
            Log::error(__FILE__ . " > " . __FUNCTION__);
            Log::error("Mensaje : " . $e->getMessage());
            Log::error("Línea : " . $e->getLine());

        if (!$rolCoordinador) {
            return response()->json([
                "ok" => false,
                "message" => "Rol Docente no encontrado"
            ], 404);
        }

        // Obtener los usuarios que tienen el rol de "Docente"
        $coordinadores = UsuarioModel::select(
            "usuarios.id_usuario",
            "usuarios.nombres",
            "usuarios.apellidos",
            "usuarios.cedula",
            UsuarioModel::raw("CONCAT(usuarios.nombres, ' ', usuarios.apellidos) as nombre_completo"),
            "titulo_academico.descripcion as titulo_academico",
        )
        ->join('titulo_academico', 'usuarios.id_titulo_academico', '=', 'titulo_academico.id_titulo_academico')
        ->where('usuarios.id_rol', '=', $rolCoordinador->id_rol)
        ->where('usuarios.estado', '=', 'A')
        ->get();

        return response()->json([
            "ok" => true,
            "data" => $coordinadores
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

    public function showCoordinadorA()
    {
        try {
            // Obtener el ID del rol "Docente"
            $rolCoordinadorA = RolModel::select('id_rol')
                ->where('descripcion', '=', 'Coordinador Academico') 
                ->first();

            if (!$rolCoordinadorA) {
                return response()->json([
                    "ok" => false,
                    "message" => "Rol Coordinador de Academico no encontrado"
                ], 404);
            }

            // Obtener los usuarios que tienen el rol de "Coordinador"
            $coordinadoresa = UsuarioModel::select(
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
            
            ->where('id_rol', '=', $rolCoordinadorA->id_rol)
            ->where('usuarios.estado', '=', 'A')
            ->get();

            return response()->json([
                "ok" => true,
                "data" => $coordinadoresa
            ], 200);
        } catch (Exception $e) {
            Log::error(__FILE__ . " > " . __FUNCTION__);
            Log::error("Mensaje : " . $e->getMessage());
            Log::error("Línea : " . $e->getLine());

        if (!$rolCoordinadorA) {
            return response()->json([
                "ok" => false,
                "message" => "Rol Docente no encontrado"
            ], 404);
        }

        // Obtener los usuarios que tienen el rol de "Docente"
        $coordinadoresa = UsuarioModel::select(
            "usuarios.id_usuario",
            "usuarios.nombres",
            "usuarios.apellidos",
            "usuarios.cedula",
            UsuarioModel::raw("CONCAT(usuarios.nombres, ' ', usuarios.apellidos) as nombre_completo"),
            "titulo_academico.descripcion as titulo_academico",
        )
        ->join('titulo_academico', 'usuarios.id_titulo_academico', '=', 'titulo_academico.id_titulo_academico')
        ->where('usuarios.id_rol', '=', $rolCoordinadorA->id_rol)
        ->where('usuarios.estado', '=', 'A')
        ->get();

        return response()->json([
            "ok" => true,
            "data" => $coordinadoresa
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


    public function updateUsuarios(Request $request, $id)
{
    try {
        Log::info('Iniciando actualización de usuario.');

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

        // Buscar el usuario por ID
        $usuarioExistente = UsuarioModel::findOrFail($id);
        Log::info('Usuario encontrado para actualización.', ['usuarioExistente' => $usuarioExistente]);

        // Actualizar datos del usuario
        $nombres = ucfirst(trim($request->nombres));
        $apellidos = ucfirst(trim($request->apellidos));

        $usuarioExistente->cedula = $request->cedula;
        $usuarioExistente->nombres = $nombres;
        $usuarioExistente->apellidos = $apellidos;
        $usuarioExistente->correo = $request->correo;
        $usuarioExistente->telefono = $request->telefono;
        $usuarioExistente->id_rol = $request->id_rol;
        $usuarioExistente->id_job = $request->id_job;
        $usuarioExistente->id_titulo_academico = $request->id_titulo_academico;
        $usuarioExistente->usuario = strtolower(explode(' ', $nombres)[0] . explode(' ', $apellidos)[0]);
        $usuarioExistente->ip_actualizacion = $request->ip();
        $usuarioExistente->id_usuario_actualizo = auth()->id() ?? 1;

        // Guardar los cambios en la base de datos
        $usuarioExistente->save();
        Log::info('Datos de usuario actualizados exitosamente.');

        // Actualizar las carreras del usuario sin comprobar duplicados
        if ($request->id_carreras && is_array($request->id_carreras)) {
            // Asignar las nuevas carreras sin duplicar
            $usuarioExistente->carreras()->syncWithoutDetaching($request->id_carreras);
            Log::info('Carreras actualizadas exitosamente.');
        }

        return response()->json([
            "ok" => true,
            "message" => "Usuario actualizado exitosamente",
            "data" => $usuarioExistente
        ], 200);

    } catch (ModelNotFoundException $e) {
        return response()->json([
            "ok" => false,
            "message" => "Usuario no encontrado"
        ], 404);
        
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





    public function show($id)
{
    try {
        // Log para el registro de acciones
        $this->servicio_informe->storeInformativoLogs(__FILE__, __FUNCTION__);

        // Buscar el usuario por ID
        $usuario = UsuarioModel::select(
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
        ->where("usuarios.id_usuario", $id) // Filtrar por ID
        ->where("usuarios.estado", "A") // Asegurarse de que el usuario esté activo
        ->with(['carreras' => function ($query) {
            $query->select('carreras.id_carrera', 'carreras.nombre');  // Obtener los campos necesarios de las carreras
        }])
        ->first(); // Usar first() para obtener un único registro

        // Verificar si el usuario fue encontrado
        if (!$usuario) {
            return response()->json([
                "ok" => false,
                "message" => "Usuario no encontrado"
            ], 404);
        }

        // Devolver el usuario encontrado
        return response()->json([
            "ok" => true,
            "data" => $usuario
        ], 200);

    } catch (Exception $e) {
        // Log del error
        log::error(__FILE__ . " > " . __FUNCTION__);
        log::error("Mensaje : " . $e->getMessage());
        log::error("Línea : " . $e->getLine());

        return response()->json([
            "ok" => false,
            "message" => "Error interno en el servidor"
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
