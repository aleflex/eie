<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Docente;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DocenteController extends Controller
{
    /**
     * Mostrar un listado de los docentes.
     */
    public function index()
    {
        $docentes = Docente::with('user')->get();
        return response()->json($docentes);
    }

    /**
     * Guardar un nuevo docente en la base de datos.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombres' => 'required|string|max:255',
            'apellidos' => 'nullable|string|max:255',
            'correo_electronico' => 'nullable|string|email|max:255|unique:usuarios,correo_institucional',
            'ci' => 'required|string|regex:/^[0-9]{7,8}$/|unique:usuarios,ci',
            'especialidad' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:50',
            'tipo_contrato' => 'nullable|string|in:Contrato,Ítem',
            'fecha_contrato' => 'nullable|date',
            'fecha_inicio_contrato' => 'nullable|date',
            'fecha_fin_contrato' => 'nullable|date',
        ]);

        try {
            DB::beginTransaction();

            // 1. Crear el usuario para el docente
            $email = $request->correo_electronico ?: ('docente.' . $request->ci . '@eie.edu.bo');
            $user = User::create([
                'id_rol' => 2, // DOCENTE
                'correo_institucional' => $email,
                'password' => Hash::make($request->ci),
                'nombres' => $request->nombres,
                'apellidos' => $request->apellidos,
                'ci' => $request->ci,
                'estado' => 'ACTIVO',
            ]);

            // 2. Resolver id_tipo_contrato mediante catálogo 3NF
            $idTipoContrato = null;
            if ($request->filled('tipo_contrato')) {
                $nombreTipo = $request->tipo_contrato === 'Ítem' ? 'Titular' : ($request->tipo_contrato === 'Contrato' ? 'Contratado' : $request->tipo_contrato);
                $tipoContratoObj = \DB::table('tipos_contrato_docente')
                    ->where('nombre_tipo_contrato', $nombreTipo)
                    ->first();
                if (!$tipoContratoObj) {
                    $idTipoContrato = \DB::table('tipos_contrato_docente')->insertGetId([
                        'nombre_tipo_contrato' => $nombreTipo,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                } else {
                    $idTipoContrato = $tipoContratoObj->id_tipo_contrato;
                }
            }

            $fechaContrato = $request->fecha_inicio_contrato ?: ($request->fecha_contrato ?: ($request->fecha_fin_contrato ?: null));

            // 3. Crear el docente vinculado al usuario
            $docente = Docente::create([
                'id_usuario' => $user->id_usuario,
                'especialidad' => $request->especialidad,
                'id_tipo_contrato' => $idTipoContrato,
                'telefono' => $request->telefono,
                'fecha_contrato' => $fechaContrato,
                'estado' => $request->estado ?? 'Activo',
            ]);

            if ($request->hasFile('foto')) {
                $path = $request->file('foto')->store('docentes/' . ($docente->ci ?? $docente->id_docente), 'public');
                $user->foto_url = '/storage/docentes/' . ($docente->ci ?? $docente->id_docente) . '/' . basename($path);
                $user->save();
            }

            DB::commit();

            return response()->json([
                'message' => 'Docente registrado exitosamente',
                'docente' => $docente->fresh()
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al registrar docente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar un docente específico.
     */
    public function show($id)
    {
        $docente = Docente::with('user')->findOrFail($id);
        return response()->json([
            'docente' => $docente,
            'user' => $docente->user
        ]);
    }

    /**
     * Actualizar un docente específico en la base de datos.
     */
    public function update(Request $request, $id)
    {
        $docente = Docente::findOrFail($id);
        $userId = $docente->id_usuario;

        // Validación con regla de unicidad exceptuando al docente actual
        $request->validate([
            'nombres' => 'required|string|max:255',
            'apellidos' => 'nullable|string|max:255',
            'correo_electronico' => 'nullable|string|email|max:255|unique:usuarios,correo_institucional,' . ($userId ?: 0) . ',id_usuario',
            'ci' => 'nullable|string|regex:/^[0-9]{7,8}$/|unique:usuarios,ci,' . ($userId ?: 0) . ',id_usuario',
            'especialidad' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:50',
            'tipo_contrato' => 'nullable|string|in:Contrato,Ítem',
            'fecha_contrato' => 'nullable|date',
            'fecha_inicio_contrato' => 'nullable|date',
            'fecha_fin_contrato' => 'nullable|date',
        ]);

        try {
            DB::beginTransaction();

            // 1. Actualizar o crear el usuario para el docente
            if (!$userId) {
                $email = $request->correo_electronico ?: ('docente.' . $request->ci . '@eie.edu.bo');
                $user = User::create([
                    'id_rol' => 2, // DOCENTE
                    'correo_institucional' => $email,
                    'password' => Hash::make($request->ci),
                    'nombres' => $request->nombres,
                    'apellidos' => $request->apellidos,
                    'ci' => $request->ci,
                    'expedido' => $request->expedido,
                    'estado' => 'ACTIVO',
                ]);
                $docente->id_usuario = $user->id_usuario;
            } else {
                $user = User::find($userId);
                if ($user) {
                    $user->update([
                        'nombres' => $request->nombres,
                        'apellidos' => $request->apellidos,
                        'ci' => $request->ci,
                        'expedido' => $request->expedido,
                        'correo_institucional' => $request->correo_electronico ?: $user->correo_institucional,
                    ]);
                }
            }

            // 2. Actualizar la tabla docentes con resolución 3NF
            $idTipoContrato = $docente->id_tipo_contrato;
            if ($request->filled('tipo_contrato')) {
                $nombreTipo = $request->tipo_contrato === 'Ítem' ? 'Titular' : ($request->tipo_contrato === 'Contrato' ? 'Contratado' : $request->tipo_contrato);
                $tipoContratoObj = \DB::table('tipos_contrato_docente')
                    ->where('nombre_tipo_contrato', $nombreTipo)
                    ->first();
                if (!$tipoContratoObj) {
                    $idTipoContrato = \DB::table('tipos_contrato_docente')->insertGetId([
                        'nombre_tipo_contrato' => $nombreTipo,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                } else {
                    $idTipoContrato = $tipoContratoObj->id_tipo_contrato;
                }
            }

            $fechaContrato = $request->fecha_inicio_contrato ?: ($request->fecha_contrato ?: ($request->fecha_fin_contrato ?: $docente->fecha_contrato));

            $updateData = [
                'especialidad' => $request->especialidad,
                'id_tipo_contrato' => $idTipoContrato,
                'telefono' => $request->telefono,
                'fecha_contrato' => $fechaContrato,
                'expedido' => $request->expedido,
                'estado' => $request->estado ?? $docente->estado,
            ];

            if ($request->hasFile('foto')) {
                $path = $request->file('foto')->store('docentes/' . ($docente->ci ?? $docente->id_docente), 'public');
                if ($user) {
                    $user->foto_url = '/storage/docentes/' . ($docente->ci ?? $docente->id_docente) . '/' . basename($path);
                    $user->save();
                }
            }

            $docente->update($updateData);

            DB::commit();

            return response()->json([
                'message' => 'Información del docente actualizada exitosamente',
                'docente' => $docente->fresh()->load('user')
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar docente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cambiar el estado del docente especificado entre Activo e Inactivo.
     */
    public function toggleStatus($id)
    {
        $docente = Docente::findOrFail($id);
        $docente->estado = $docente->estado === 'Activo' ? 'Inactivo' : 'Activo';
        $docente->save();

        if ($docente->user) {
            $docente->user->estado = $docente->estado === 'Activo' ? 'ACTIVO' : 'INACTIVO';
            $docente->user->save();
        }

        return response()->json([
            'message' => 'Estado actualizado a ' . $docente->estado,
            'docente' => $docente
        ]);
    }

    /**
     * Retorna los paralelos asignados al docente actualmente autenticado,
     * incluyendo los estudiantes inscritos en cada paralelo.
     */
    public function misParalelos(Request $request)
    {
        $userId = $request->input('user_id');

        if (!$userId) {
            return response()->json(['message' => 'user_id requerido'], 422);
        }

        $docente = Docente::where('id_usuario', $userId)->first();

        if (!$docente) {
            return response()->json(['message' => 'Docente no encontrado para este usuario'], 404);
        }

        $paralelos = $docente->paralelos()
            ->with([
                'curso',
                'aula',
                'horarios',
                'inscripciones.estudiante'
            ])
            ->get();

        return response()->json([
            'docente' => $docente->load('user'),
            'paralelos' => $paralelos
        ]);
    }

    /**
     * Eliminar un docente específico de la base de datos.
     */
    public function destroy($id)
    {
        try {
            $docente = Docente::findOrFail($id);
            $user = $docente->user;

            DB::beginTransaction();
            $docente->paralelos()->detach();
            $docente->delete();

            if ($user) {
                $user->delete();
            }
            DB::commit();

            return response()->json(['message' => 'Docente eliminado correctamente']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al eliminar docente',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
