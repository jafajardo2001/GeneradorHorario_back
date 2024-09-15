<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UsuarioModel;

class AutenticacionController extends Controller
{
    public function autenticacion(Request $request)
    {
        // Validar el correo
        $request->validate([
            'correo' => 'required|email',
        ]);

        // Buscar el usuario por correo
        $usuario = UsuarioModel::where('correo', $request->correo)->first();

        if (!$usuario) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        // Mapear el id_rol a un rol descriptivo
        $roles = [
            1 => 'admin',
            2 => 'docente',
            3 => 'alumno',
        ];

        $rol = $roles[$usuario->id_rol] ?? 'unknown';

        // Devolver el rol del usuario
        return response()->json(['rol' => $rol]);
    }
}
