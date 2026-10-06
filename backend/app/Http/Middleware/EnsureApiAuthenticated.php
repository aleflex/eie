<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiAuthenticated
{
    /**
     * Valida que la petición cuente con un Token Bearer de Sanctum o una sesión activa.
     * Si no está autenticado, devuelve 401 Unauthorized sin exponer ningún dato del sistema.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $requiredRole (opcional: 'admin', 'docente', etc.)
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, ?string $requiredRole = null): Response
    {
        $user = null;

        // 1. Intentar autenticar mediante Bearer Token (Sanctum)
        $bearerToken = $request->bearerToken();
        if ($bearerToken) {
            $accessToken = PersonalAccessToken::findToken($bearerToken);
            if ($accessToken && $accessToken->tokenable) {
                $user = $accessToken->tokenable;
            }
        }

        // 2. Si no se encontró mediante token directo, probar guard de Sanctum
        if (!$user) {
            $user = auth('sanctum')->user();
        }

        // 3. Fallback a sesión web o guard por defecto
        if (!$user) {
            $user = auth('web')->user() ?? auth()->user();
        }

        // 4. Si no hay usuario autenticado, RECHAZAR con HTTP 401
        if (!$user) {
            return response()->json([
                'status' => 'unauthorized',
                'message' => 'Acceso denegado: Esta API está protegida. Debe iniciar sesión con credenciales válidas para consultar esta información.',
                'error' => 'No autorizado'
            ], 401);
        }

        // 5. Verificar si el usuario está inactivo
        if (isset($user->estado) && strtoupper($user->estado) === 'INACTIVO') {
            return response()->json([
                'status' => 'forbidden',
                'message' => 'Su cuenta se encuentra inactiva o deshabilitada. Contacte a la administración.',
                'error' => 'Cuenta inactiva'
            ], 403);
        }

        // 6. Verificar rol requerido si se especificó (ej: 'admin')
        if ($requiredRole === 'admin') {
            $esAdmin = ($user->id_rol == 1) || (!$user->docente && !$user->estudiante);
            if (!$esAdmin) {
                return response()->json([
                    'status' => 'forbidden',
                    'message' => 'Acceso restringido: Se requieren permisos de Administrador para este recurso.',
                    'error' => 'Permiso denegado'
                ], 403);
            }
        }

        // Establecer usuario en el contexto del request
        Auth::setUser($user);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
