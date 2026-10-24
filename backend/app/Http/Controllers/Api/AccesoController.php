<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Docente;
use App\Models\Estudiante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AccesoController extends Controller
{
    /**
     * Retorna la lista unificada de docentes, estudiantes y administradores con su estado de cuenta
     */
    public function index()
    {
        // 1. Obtener Docentes con su cuenta de usuario
        $docentes = Docente::with('user')->get()->map(function ($docente) {
            $user = $docente->user;
            if ($user && empty($user->usuario)) {
                $user->usuario = User::generateUsername($docente->nombres ?? $user->nombres, $docente->apellidos ?? $user->apellidos ?? '', $user->id_usuario);
                $user->save();
            }
            return [
                'persona_id' => $docente->id_docente,
                'user_id' => $docente->id_usuario,
                'id_rol' => $user ? $user->id_rol : 3,
                'nombres' => $docente->nombres ?? 'Docente',
                'apellidos' => $docente->apellidos ?? '',
                'ci' => $docente->ci ?? 'N/A',
                'tipo' => 'docente',
                'usuario' => $user ? $user->usuario : null,
                'correo_electronico' => $user ? $user->email : $docente->correo_electronico,
                'telefono' => $docente->telefono,
                'tiene_cuenta' => $docente->id_usuario !== null,
                'estado' => $docente->estado ?? 'Activo'
            ];
        });

        // 2. Obtener Estudiantes con su cuenta de usuario
        $estudiantes = Estudiante::with('user')->get()->map(function ($estudiante) {
            $user = $estudiante->user;
            if ($user && empty($user->usuario)) {
                $user->usuario = User::generateUsername($estudiante->nombres ?? $user->nombres, $estudiante->apellidos ?? $user->apellidos ?? '', $user->id_usuario);
                $user->save();
            }
            return [
                'persona_id' => $estudiante->id_estudiante,
                'user_id' => $estudiante->id_usuario,
                'id_rol' => $user ? $user->id_rol : 2,
                'nombres' => $estudiante->nombres,
                'apellidos' => $estudiante->apellidos,
                'ci' => $estudiante->ci,
                'tipo' => 'estudiante',
                'usuario' => $user ? $user->usuario : null,
                'correo_electronico' => $user ? $user->email : $estudiante->correo_electronico,
                'telefono' => $estudiante->celular,
                'tiene_cuenta' => $estudiante->id_usuario !== null,
                'estado' => 'Activo'
            ];
        });

        // 3. Obtener Administradores (usuarios de la tabla users que no son docentes ni estudiantes)
        $docenteUserIds = Docente::whereNotNull('id_usuario')->pluck('id_usuario')->toArray();
        $estudianteUserIds = Estudiante::whereNotNull('id_usuario')->pluck('id_usuario')->toArray();
        $excluidos = array_merge($docenteUserIds, $estudianteUserIds);

        $administradores = User::whereNotIn('id_usuario', $excluidos)->get()->map(function ($user) {
            if (empty($user->usuario)) {
                $user->usuario = User::generateUsername($user->nombres ?? 'admin', $user->apellidos ?? '', $user->id_usuario);
                $user->save();
            }
            $rolNombre = 'Administrador General';
            if ($user->id_rol == 5) {
                $rolNombre = 'Secretaría';
            } elseif ($user->id_rol == 4) {
                $rolNombre = 'Jefe de Unidad';
            }
            return [
                'persona_id' => $user->id_usuario,
                'user_id' => $user->id_usuario,
                'nombres' => $user->name,
                'apellidos' => '',
                'ci' => $user->ci ?? 'N/A',
                'tipo' => 'admin',
                'id_rol' => $user->id_rol ?: 1,
                'rol_nombre' => $rolNombre,
                'usuario' => $user->usuario,
                'correo_electronico' => $user->email,
                'telefono' => 'N/A',
                'tiene_cuenta' => true,
                'estado' => $user->estado ?? 'Activo'
            ];
        });

        // 4. Unificar listas
        $unificada = $docentes->concat($estudiantes)->concat($administradores);

        return response()->json($unificada);
    }

    /**
     * Asigna credenciales creando un usuario y vinculándolo a la persona (o crea un administrador directamente)
     */
    public function asignar(Request $request)
    {
        if ($request->has('email') && trim((string)$request->email) === '') {
            $request->merge(['email' => null]);
        }

        $request->validate([
            'persona_id' => 'required_unless:tipo,admin|integer',
            'tipo' => 'required|string|in:docente,estudiante,admin',
            'nombres' => 'required_if:tipo,admin|string|max:255',
            'usuario' => 'sometimes|required|string|max:100|unique:usuarios,usuario',
            'email' => 'nullable|email|max:255',
            'password' => 'nullable|string|min:4',
            'id_rol' => 'nullable|integer',
        ], [
            'usuario.unique' => 'Este nombre de usuario ya se encuentra registrado.',
            'email.email' => 'El formato del correo electrónico no es válido.',
            'password.min' => 'La contraseña debe contener al menos 4 caracteres.',
        ]);

        try {
            DB::beginTransaction();

            $nombres = '';
            $apellidos = '';
            $ci = null;

            if ($request->tipo === 'admin') {
                $nombres = $request->nombres;
                $apellidos = 'Admin';
            } elseif ($request->tipo === 'docente') {
                $docente = Docente::findOrFail($request->persona_id);
                if ($docente->id_usuario) {
                    return response()->json(['message' => 'El docente ya cuenta con un usuario asignado'], 422);
                }
                $nombres = $docente->nombres;
                $apellidos = $docente->apellidos;
                $ci = $docente->ci;
            } else {
                $estudiante = Estudiante::findOrFail($request->persona_id);
                if ($estudiante->id_usuario) {
                    return response()->json(['message' => 'El estudiante ya cuenta con un usuario asignado'], 422);
                }
                $nombres = $estudiante->nombres;
                $apellidos = $estudiante->apellidos;
                $ci = $estudiante->ci;
            }

            // Usar nombre de usuario personalizado o generar automático
            $username = $request->filled('usuario') 
                ? strtolower(trim($request->usuario)) 
                : User::generateUsername($nombres, $apellidos);

            $email = $request->filled('email') 
                ? strtolower(trim($request->email)) 
                : "{$username}@eie.edu.bo";

            // Si no se envió contraseña, usar el carnet limpio (solo dígitos) por defecto
            $cleanCi = $ci ? preg_replace('/[^0-9]/', '', (string)$ci) : '';
            $passwordPlain = $request->filled('password') 
                ? $request->password 
                : ($cleanCi ?: 'password123');

            // Determinar rol
            $idRol = 1;
            if ($request->tipo === 'admin') {
                $idRol = (int) $request->input('id_rol', 1);
            } elseif ($request->tipo === 'docente') {
                $idRol = 3;
            } else {
                $idRol = 2;
            }

            // 1. Crear el usuario
            $user = User::create([
                'id_rol' => $idRol,
                'correo_institucional' => $email,
                'usuario' => $username,
                'password' => Hash::make($passwordPlain),
                'debe_cambiar_password' => true,
                'estado' => 'ACTIVO',
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'ci' => $ci,
            ]);

            // 2. Vincular el usuario si aplica
            if ($request->tipo === 'docente') {
                $docente->id_usuario = $user->id_usuario;
                $docente->save();
            } elseif ($request->tipo === 'estudiante') {
                $estudiante->id_usuario = $user->id_usuario;
                $estudiante->save();
            }

            DB::commit();

            return response()->json([
                'message' => $request->tipo === 'admin' ? "Administrador creado. Usuario: {$username}" : "Credenciales creadas. Usuario: {$username}",
                'user' => $user,
                'usuario_generado' => $username
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al procesar solicitud',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualiza el correo de acceso o la contraseña de un usuario
     */
    public function actualizar(Request $request, $userId)
    {
        $user = User::findOrFail($userId);

        if ($request->has('email') && trim((string)$request->email) === '') {
            $request->merge(['email' => null]);
        }
        if ($request->has('password') && trim((string)$request->password) === '') {
            $request->merge(['password' => null]);
        }

        $request->validate([
            'usuario' => 'required|string|max:100|unique:usuarios,usuario,' . $user->id_usuario . ',id_usuario',
            'email' => 'nullable|email|max:255',
            'password' => 'nullable|string|min:4',
            'id_rol' => 'nullable|integer',
        ], [
            'usuario.required' => 'El nombre de usuario es obligatorio.',
            'usuario.unique' => 'Este nombre de usuario ya está en uso por otra cuenta.',
            'email.email' => 'El correo electrónico no tiene un formato válido.',
            'password.min' => 'La nueva contraseña debe tener al menos 4 caracteres.',
        ]);

        try {
            DB::beginTransaction();

            if ($request->filled('usuario')) {
                $user->usuario = strtolower(trim($request->usuario));
            }
            if ($request->filled('email')) {
                $user->correo_institucional = strtolower(trim($request->email));
            }
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
                $user->debe_cambiar_password = true; // Exigir cambio al actualizar la contraseña por admin
            }
            if ($request->filled('id_rol')) {
                $user->id_rol = (int) $request->id_rol;
            }
            $user->save();

            // Sincronizar el correo en estudiantes si corresponde y se proporcionó
            if ($request->filled('email')) {
                $estudiante = Estudiante::where('id_usuario', $user->id_usuario)->first();
                if ($estudiante) {
                    $estudiante->correo_electronico = strtolower(trim($request->email));
                    $estudiante->save();
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Credenciales actualizadas correctamente',
                'user' => $user
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar credenciales',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Desvincula y elimina la cuenta de un usuario sin eliminar a la persona
     */
    public function desvincular($userId)
    {
        $user = User::findOrFail($userId);

        try {
            DB::beginTransaction();

            Docente::where('id_usuario', $user->id_usuario)->update(['id_usuario' => null]);
            Estudiante::where('id_usuario', $user->id_usuario)->update(['id_usuario' => null]);
            $user->delete();

            DB::commit();

            return response()->json([
                'message' => 'Cuenta desvinculada y eliminada con éxito'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al desvincular cuenta',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
