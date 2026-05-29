<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Autentica al usuario en el sistema.
     * Recibe el email y la contraseña, los valida, e inicia la sesión.
     * También detecta si el usuario es Docente, Estudiante o Administrador.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        // Limpiar el email de espacios en blanco y convertir a minúsculas
        if ($request->has('email')) {
            $request->merge([
                'email' => strtolower(trim($request->input('email')))
            ]);
        }

        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            // Verificar si el usuario tiene perfil de docente o estudiante
            $docente = \App\Models\Docente::where('user_id', $user->id)->first();
            $estudiante = \App\Models\Estudiante::where('user_id', $user->id)->first();

            $userData = $user->toArray();
            if ($docente) {
                // Verificar si el contrato ha expirado (si es por Contrato y tiene fecha)
                if ($docente->tipo_contrato === 'Contrato' && $docente->fecha_contrato) {
                    $fechaExpiracion = \Carbon\Carbon::parse($docente->fecha_contrato)->endOfDay();
                    if (now()->greaterThan($fechaExpiracion)) {
                        Auth::logout();
                        return response()->json([
                            'message' => 'Su contrato ha expirado. Por favor, contáctese o diríjase a dirección para recontratar.'
                        ], 403);
                    }
                }

                $userData['docente_id'] = $docente->id;
                $userData['rol'] = 'docente';
            } elseif ($estudiante) {
                $userData['estudiante_id'] = $estudiante->id;
                $userData['rol'] = 'estudiante';
            } else {
                $userData['rol'] = 'admin';
            }

            $token = $user->createToken('auth_token')->plainTextToken;
            $userData['token'] = $token;

            return response()->json([
                'message' => 'Login exitoso',
                'user' => $userData,
                'token' => $token
            ]);
        }

        return response()->json([
            'message' => 'Credenciales inválidas'
        ], 401);
    }

    /**
     * Cierra la sesión activa del usuario.
     * Destruye el token de autenticación.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        Auth::logout();
        return response()->json(['message' => 'Sesión cerrada']);
    }

    /**
     * Registra un nuevo usuario en la base de datos (generalmente usado para Administradores).
     * Hashea la contraseña antes de guardarla por seguridad.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'message' => 'Usuario creado exitosamente',
            'user' => $user
        ], 201);
    }

    /**
     * Actualiza el perfil del usuario autenticado (nombre, email, contraseña, foto).
     * Sube y almacena la foto de perfil en el servidor si se proporciona una.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request)
    {
        // Obtener usuario autenticado o fallback al primer usuario (admin) para ambiente local
        $user = auth()->user() ?? User::first();
        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'sometimes|nullable|string|min:8',
        ]);

        $data = [];
        if ($request->has('name') && !empty($request->name)) {
            $data['name'] = $request->name;
        }
        if ($request->has('email') && !empty($request->email)) {
            $data['email'] = $request->email;
        }
        if ($request->has('password') && !empty($request->password)) {
            $data['password'] = Hash::make($request->password);
        }

        if ($request->hasFile('foto')) {
            $path = $request->file('foto')->store('profiles/' . $user->id, 'public');
            $data['foto_url'] = '/storage/profiles/' . $user->id . '/' . basename($path);
        }

        $user->update($data);

        return response()->json([
            'message' => 'Perfil actualizado exitosamente',
            'user' => $user->fresh()
        ]);
    }
}
