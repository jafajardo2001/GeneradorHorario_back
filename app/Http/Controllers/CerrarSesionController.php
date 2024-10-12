<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Exception;

class CerrarSesionController extends Controller
{
    public function logout(Request $request)
    {
        try {
            // Verificar que el usuario esté autenticado
            $user = $request->user();
            if ($user) {
                // Invalidar el token del usuario
                $user->token()->revoke();

                return response()->json([
                    'ok' => true,
                    'message' => 'Sesión cerrada con éxito.'
                ], 200);
            } else {
                return response()->json([
                    'ok' => false,
                    'message' => 'No se pudo identificar al usuario.'
                ], 401); // Unauthorized
            }
        } catch (Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Hubo un error al intentar cerrar sesión.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
