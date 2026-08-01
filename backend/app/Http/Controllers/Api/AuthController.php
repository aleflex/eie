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
     * Recibe el usuario/email y la contraseña, los valida, e inicia la sesión.
     * Detecta si el usuario es Docente, Estudiante o Administrador.
     */
    public function login(Request $request)
    {
        $loginInput = trim($request->input('usuario', $request->input('login', $request->input('email', ''))));
        $password = $request->input('password');

        if (empty($loginInput) || empty($password)) {
            return response()->json([
                'message' => 'Por favor ingrese su Nombre de Usuario y Contraseña.'
            ], 422);
        }

        // Buscar por usuario o por correo institucional
        $user = User::where(function($q) use ($loginInput) {
            $q->where('usuario', strtolower($loginInput))
              ->orWhere('correo_institucional', strtolower($loginInput));
        })->first();

        if ($user && Hash::check($password, $user->password)) {
            // Iniciar sesión
            Auth::login($user);

            // Verificar si el usuario tiene perfil de docente o estudiante
            $docente = \App\Models\Docente::where('id_usuario', $user->id_usuario)->first();
            $estudiante = \App\Models\Estudiante::where('id_usuario', $user->id_usuario)->first();

            $userData = $user->toArray();
            if ($docente) {
                if ($docente->tipo_contrato === 'Contrato' && $docente->fecha_contrato) {
                    $fechaExpiracion = \Carbon\Carbon::parse($docente->fecha_contrato)->endOfDay();
                    if (now()->greaterThan($fechaExpiracion)) {
                        Auth::logout();
                        return response()->json([
                            'message' => 'Su contrato ha expirado. Por favor, contáctese o diríjase a dirección para recontratar.'
                        ], 403);
                    }
                }

                $userData['docente_id'] = $docente->id_docente;
                $userData['rol'] = 'docente';
            } elseif ($estudiante) {
                $userData['estudiante_id'] = $estudiante->id_estudiante;
                $userData['rol'] = 'estudiante';
            } else {
                $userData['rol'] = 'admin';
            }

            $token = $user->createToken('auth_token')->plainTextToken;
            $userData['token'] = $token;
            $userData['usuario'] = $user->usuario;
            $userData['debe_cambiar_password'] = (bool) $user->debe_cambiar_password;

            return response()->json([
                'message' => 'Login exitoso',
                'user' => $userData,
                'token' => $token
            ]);
        }

        return response()->json([
            'message' => 'Credenciales inválidas (usuario o contraseña incorrectos)'
        ], 401);
    }

    /**
     * Permite a un usuario autenticado cambiar su contraseña obligatoriamente o voluntariamente.
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = auth()->user() ?? User::find($request->input('user_id'));
        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        $user->password = Hash::make($request->password);
        $user->debe_cambiar_password = false;
        $user->save();

        return response()->json([
            'message' => 'Contraseña actualizada exitosamente. Ahora puede acceder a todas las funciones.',
            'debe_cambiar_password' => false
        ]);
    }

    /**
     * Cierra la sesión activa del usuario.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        return response()->json(['message' => 'Sesión cerrada']);
    }

    /**
     * Registra un nuevo usuario en la base de datos (Administradores).
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'nombres' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:usuarios,correo_institucional',
            'password' => 'required|string|min:8',
        ]);

        $username = User::generateUsername($validated['nombres'], $validated['apellidos']);

        $user = User::create([
            'id_rol' => 1, // ADMINISTRADOR
            'correo_institucional' => strtolower($validated['email']),
            'usuario' => $username,
            'password' => Hash::make($validated['password']),
            'debe_cambiar_password' => true,
            'estado' => 'ACTIVO',
            'nombres' => $validated['nombres'],
            'apellidos' => $validated['apellidos'],
        ]);

        return response()->json([
            'message' => 'Usuario creado exitosamente',
            'user' => $user
        ], 201);
    }

    /**
     * Actualiza el perfil del usuario autenticado.
     */
    public function updateProfile(Request $request)
    {
        $user = auth()->user() ?? User::first();
        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:usuarios,correo_institucional,' . $user->id_usuario . ',id_usuario',
            'password' => 'sometimes|nullable|string|min:8',
        ]);

        $data = [];
        if ($request->has('email') && !empty($request->email)) {
            $data['correo_institucional'] = strtolower($request->email);
        }
        if ($request->has('password') && !empty($request->password)) {
            $data['password'] = Hash::make($request->password);
            $data['debe_cambiar_password'] = false;
        }

        if (!empty($data)) {
            $user->update($data);
        }

        if ($request->has('name') && !empty($request->name)) {
            if ($user->estudiante) {
                $user->estudiante->nombres = $request->name;
                $user->estudiante->save();
            } elseif ($user->docente) {
                $user->docente->nombres = $request->name;
                $user->docente->save();
            }
        }

        if ($request->hasFile('foto')) {
            $path = $request->file('foto')->store('profiles/' . $user->id_usuario, 'public');
            $fotoUrl = '/storage/profiles/' . $user->id_usuario . '/' . basename($path);
            if ($user->estudiante) {
                $user->estudiante->foto_4x4_url = $fotoUrl;
                $user->estudiante->save();
            } elseif ($user->docente) {
                $user->docente->foto_url = $fotoUrl;
                $user->docente->save();
            }
        }

        return response()->json([
            'message' => 'Perfil actualizado exitosamente',
            'user' => $user->fresh()
        ]);
    }
}
