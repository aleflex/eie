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
            return [
                'persona_id' => $docente->id,
                'user_id' => $docente->user_id,
                'nombres' => $docente->nombres ?? 'Docente',
                'apellidos' => $docente->apellidos ?? '',
                'ci' => $docente->ci ?? 'N/A',
                'tipo' => 'docente',
                'correo_electronico' => $docente->user ? $docente->user->email : $docente->correo_electronico,
                'telefono' => $docente->telefono,
                'tiene_cuenta' => $docente->user_id !== null,
                'estado' => $docente->estado ?? 'Activo'
            ];
        });

        // 2. Obtener Estudiantes con su cuenta de usuario
        $estudiantes = Estudiante::with('user')->get()->map(function ($estudiante) {
            return [
                'persona_id' => $estudiante->id,
                'user_id' => $estudiante->user_id,
                'nombres' => $estudiante->nombres,
                'apellidos' => $estudiante->apellidos,
                'ci' => $estudiante->ci,
                'tipo' => 'estudiante',
                'correo_electronico' => $estudiante->user ? $estudiante->user->email : $estudiante->correo_electronico,
                'telefono' => $estudiante->celular,
                'tiene_cuenta' => $estudiante->user_id !== null,
                'estado' => 'Activo'
            ];
        });

        // 3. Obtener Administradores (usuarios de la tabla users que no son docentes ni estudiantes)
        $docenteUserIds = Docente::whereNotNull('user_id')->pluck('user_id')->toArray();
        $estudianteUserIds = Estudiante::whereNotNull('user_id')->pluck('user_id')->toArray();
        $excluidos = array_merge($docenteUserIds, $estudianteUserIds);

        $administradores = User::whereNotIn('id', $excluidos)->get()->map(function ($user) {
            return [
                'persona_id' => $user->id,
                'user_id' => $user->id,
                'nombres' => $user->name,
                'apellidos' => '',
                'ci' => 'N/A',
                'tipo' => 'admin',
                'correo_electronico' => $user->email,
                'telefono' => 'N/A',
                'tiene_cuenta' => true,
                'estado' => 'Activo'
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
        $request->validate([
            'persona_id' => 'required_unless:tipo,admin|integer',
            'tipo' => 'required|string|in:docente,estudiante,admin',
            'nombres' => 'required_if:tipo,admin|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        try {
            DB::beginTransaction();

            $name = '';
            if ($request->tipo === 'admin') {
                // Crear administrador directamente
                $name = $request->nombres;
            } elseif ($request->tipo === 'docente') {
                $docente = Docente::findOrFail($request->persona_id);
                if ($docente->user_id) {
                    return response()->json(['message' => 'El docente ya cuenta con un usuario asignado'], 422);
                }
                $name = $docente->user ? $docente->user->name : 'Instructor EIE';
            } else {
                $estudiante = Estudiante::findOrFail($request->persona_id);
                if ($estudiante->user_id) {
                    return response()->json(['message' => 'El estudiante ya cuenta con un usuario asignado'], 422);
                }
                $name = $estudiante->nombres . ' ' . $estudiante->apellidos;
            }

            // 1. Crear el usuario
            $user = User::create([
                'name' => $name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // 2. Vincular el usuario si aplica
            if ($request->tipo === 'docente') {
                $docente->user_id = $user->id;
                $user->name = $user->name === 'Instructor EIE' ? 'Instructor ' . $docente->id : $user->name;
                $user->save();
                $docente->save();
            } elseif ($request->tipo === 'estudiante') {
                $estudiante->user_id = $user->id;
                $estudiante->save();
            }

            DB::commit();

            return response()->json([
                'message' => $request->tipo === 'admin' ? 'Administrador registrado con éxito' : 'Credenciales asignadas correctamente',
                'user' => $user
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

        $request->validate([
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
        ]);

        try {
            DB::beginTransaction();

            $user->email = $request->email;
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }
            $user->save();

            // Sincronizar el correo en estudiantes si corresponde
            $estudiante = Estudiante::where('user_id', $user->id)->first();
            if ($estudiante) {
                $estudiante->correo_electronico = $request->email;
                $estudiante->save();
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
     * Desvincula y elimina la cuenta de un usuario sin eliminar a la persona (o elimina un administrador)
     */
    public function desvincular($userId)
    {
        $user = User::findOrFail($userId);

        try {
            DB::beginTransaction();

            // Desvincular de docentes
            Docente::where('user_id', $user->id)->update(['user_id' => null]);

            // Desvincular de estudiantes
            Estudiante::where('user_id', $user->id)->update(['user_id' => null]);

            // Eliminar usuario
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
