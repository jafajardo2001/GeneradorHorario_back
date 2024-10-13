<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Log;

use Illuminate\Http\Request;
use App\Models\PeriodoElectivoModel;

class PeriodoElectivoController extends Controller
{
    public function show_data_periodo_electivo(Request $request)
    {
        try {
            // Obtener todos los periodos con estado 'A' (Activo)
            $periodos = PeriodoElectivoModel::where('estado', 'A') // 'A' para Activo
                ->orderBy('anio', 'desc') // Ordenar por año descendente
                ->get(['id_periodo', 'anio', 'periodo', 'fecha_creacion', 'fecha_actualizacion', 'estado']);

            // Respuesta con los datos
            return response()->json(['data' => $periodos, 'ok' => true]);

        } catch (\Exception $e) {
            // En caso de error, devolver una respuesta con error
            return response()->json(['message' => 'Error al obtener los periodos', 'ok' => false], 500);
        }
    }

    /**
     * Eliminar un periodo electivo (marcarlo como eliminado).
     */
    public function deletePeriodoElectivo(Request $request, $id)
{
    try {
        // Buscar el periodo por su ID
        $periodo = PeriodoElectivoModel::find($id);
        if (!$periodo) {
            return response()->json([
                "ok" => false,
                "message" => "El periodo electivo no existe con el id $id"
            ], 400);    
        }

        // Verificar si hay distribuciones asociadas a este periodo
        $distribuciones = \DB::table('distribuciones_horario_academica')
            ->where('id_periodo_academico', $id)
            ->exists();

        // Cambiar el estado del periodo a "E" (Eliminado)
        $periodo->estado = "E";  // Cambia el estado a "E"
        $periodo->id_usuario_actualizo = auth()->id() ?? 1;  // Actualiza el usuario que hace el cambio
        $periodo->ip_actualizacion = $request->ip();  // Actualiza la IP
        $periodo->save();  // Guarda los cambios

        
        // Inhabilitar distribuciones asociadas si existen
        if ($distribuciones) {
            \DB::table('distribuciones_horario_academica')
                ->where('id_periodo_academico', $id)
                ->update(['estado' => "E"]);
        }
            
        return response()->json([
            "ok" => true,
            "message" => $distribuciones 
                ? "Periodo electivo y distribuciones eliminados con éxito" 
                : "Periodo electivo eliminado con éxito"
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

    public function checkDistribucionesPorPeriodo($id)
{
    try {
        // Verificar si hay distribuciones asociadas a la carrera
        $distribuciones = \DB::table('distribuciones_horario_academica')
            ->where('estado', "A") // Cambia a 'id_nivel' para verificar el curso
            ->where('id_periodo_academico', $id)
            ->count();

        return response()->json([
            "ok" => true,
            "count" => $distribuciones, // Retornar el conteo de distribuciones
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
    public function create(Request $request)
{
    // Validación de los datos
    $validated = $request->validate([
        'anio' => 'required|string|min:4|max:4', // El campo "anio" debe ser una cadena con 4 caracteres (por ejemplo, "2023")
        'periodo' => 'required|string|max:255', // El nombre del periodo no puede ser vacío y debe ser una cadena
    ]);

    // Verificación de si ya existen dos periodos activos para el mismo año
    $existingActivePeriodos = PeriodoElectivoModel::where('anio', $validated['anio'])
        ->where('estado', 'A') // Filtra solo los que tienen el estado 'A'
        ->count();

    if ($existingActivePeriodos >= 2) {
        return response()->json([
            'ok' => false,
            'message' => 'Ya existen dos periodos activos para el año ' . $validated['anio'] . '. No se puede agregar un tercer periodo.',
        ], 400); // 400 error de solicitud incorrecta
    }

    // Verificación si existe un periodo con estado 'E' (eliminado) para el mismo año
    $existingDeletedPeriodo = PeriodoElectivoModel::where('anio', $validated['anio'])
        ->where('estado', 'E') // Filtra solo los que tienen el estado 'E' (eliminado)
        ->first();

    if ($existingDeletedPeriodo) {
        // Si ya existe un periodo eliminado para este año, solo cambiamos su estado a 'A'
        $existingDeletedPeriodo->estado = 'A'; // Cambiar estado a 'A'
        $existingDeletedPeriodo->ip_actualizacion = $request->ip();
        $existingDeletedPeriodo->id_usuario_actualizo = auth()->id() ?? 1;
        $existingDeletedPeriodo->save(); // Guardar cambios
        Log::info('Periodo electivo reactivado exitosamente', ['periodo' => $existingDeletedPeriodo]);

        return response()->json([
            'ok' => true,
            'message' => 'Periodo Electivo reactivado exitosamente.',
            'data' => $existingDeletedPeriodo
        ], 200); // 200 OK
    }

    // Si no existe un periodo eliminado, se crea uno nuevo
    Log::info('Datos validados para el nuevo periodo electivo', ['validated_data' => $validated]);

    try {
        // Crear el nuevo periodo electivo
        $periodo = new PeriodoElectivoModel();
        $periodo->anio = $validated['anio'];  // Asigna el valor del campo "anio"
        $periodo->periodo = $validated['periodo'];
        $periodo->estado = 'A';  // Establece el estado como 'A' (activo)
        $periodo->ip_creacion = $request->ip();
        $periodo->ip_actualizacion = $request->ip();
        $periodo->id_usuario_creador = auth()->id() ?? 1;
        $periodo->id_usuario_actualizo = auth()->id() ?? 1;
        $periodo->save(); // Guardar el nuevo periodo
        Log::info('Periodo electivo creado exitosamente', ['periodo' => $periodo]);

        // Responder con éxito
        return response()->json([
            'ok' => true,
            'message' => 'Periodo Electivo creado exitosamente.',
            'data' => $periodo
        ], 201); // 201 creado exitosamente
    } catch (\Exception $e) {
        // En caso de error
        return response()->json([
            'ok' => false,
            'message' => 'Error al crear el periodo electivo: ' . $e->getMessage(),
        ], 500); // 500 error interno del servidor
    }
}



public function updatePeriodoElectivo(Request $request, $id)
{
    // Validar los datos que vienen en la solicitud
    $validated = $request->validate([
        'anio' => 'required|string|max:4', // El año debe ser un string de 4 caracteres
        'periodo' => 'required|string|max:255', // El periodo es un campo string
    ]);

    // Buscar el periodo por el ID
    $periodo = PeriodoElectivoModel::find($id);

    if (!$periodo) {
        // Si no se encuentra el periodo, retornar un error 404
        return response()->json(['ok' => false, 'message' => 'Periodo no encontrado.'], 404);
    }

    // Verificar si ya existe un periodo activo con el mismo año y periodo, excluyendo el actual
    $existingPeriodo = PeriodoElectivoModel::where('anio', $validated['anio'])
        ->where('periodo', $validated['periodo'])
        ->where('estado', 'A') // Solo se verifica los que están activos
        ->where('id_periodo', '!=', $id) // Excluye el periodo actual
        ->first();
        Log::info('Ya existe un periodo activo con el mismo año y periodo', ['periodo' => $existingPeriodo]);

    if ($existingPeriodo) {
        // Si ya existe un periodo activo con el mismo año y periodo, retornar un error
        return response()->json([
            'ok' => false,
            'message' => 'Ya existe un periodo activo con el mismo año y periodo.',
        ], 400); // 400 solicitud incorrecta
    }

    // Validar que no haya más de dos periodos activos para el mismo año
    $activePeriodosCount = PeriodoElectivoModel::where('anio', $validated['anio'])
        ->where('estado', 'A')
        ->count();

    if ($activePeriodosCount >= 2) {
        // Si ya hay dos periodos activos para el mismo año, retornar un error
        return response()->json([
            'ok' => false,
            'message' => 'Un año solo puede tener dos periodos activos.',
        ], 400); // 400 solicitud incorrecta
    }

    // Actualizar los campos en la base de datos
    $periodo->anio = $validated['anio']; // Asignamos el nuevo valor del año
    $periodo->periodo = $validated['periodo'];
    $periodo->ip_actualizacion = $request->ip();
    $periodo->id_usuario_actualizo = auth()->id() ?? 1;  // Asigna el valor del campo "periodo"

    // Guardar los cambios en la base de datos
    if ($periodo->save()) {
        return response()->json(['ok' => true, 'message' => 'Periodo actualizado correctamente.']);
    } else {
        return response()->json(['ok' => false, 'message' => 'Error al actualizar el periodo.'], 500);
    }
}


    
}

