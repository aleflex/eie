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
        // Normalizar cadenas vacías a null para evitar falsos negativos en validaciones nullable
        $inputs = $request->all();
        foreach (['correo_electronico', 'telefono', 'tipo_contrato', 'fecha_contrato', 'fecha_inicio_contrato', 'fecha_fin_contrato', 'especialidad', 'lugar_nacimiento', 'fecha_nacimiento'] as $field) {
            if (isset($inputs[$field]) && trim((string)$inputs[$field]) === '') {
                $inputs[$field] = null;
            }
        }
        $request->merge($inputs);

        $request->validate([
            'nombres' => 'required|string|max:255',
            'apellidos' => 'nullable|string|max:255',
            'correo_electronico' => 'nullable|email|max:255|unique:usuarios,correo_institucional',
            'ci' => 'required|string|max:30|unique:usuarios,ci',
            'especialidad' => 'nullable|string|max:255',
            'fecha_nacimiento' => 'nullable|date',
            'lugar_nacimiento' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:50',
            'tipo_contrato' => 'nullable|string|in:Contrato,Ítem,Titular,Contratado',
            'expedido' => 'nullable|string|max:10',
            'fecha_contrato' => 'nullable|date',
            'fecha_inicio_contrato' => 'nullable|date',
            'fecha_fin_contrato' => 'nullable|date',
        ], [
            'nombres.required' => 'El nombre del instructor es obligatorio.',
            'ci.required' => 'El número de C.I. es obligatorio.',
            'ci.unique' => 'El C.I. ya se encuentra registrado en el sistema.',
            'correo_electronico.email' => 'El formato del correo electrónico es inválido.',
            'correo_electronico.unique' => 'El correo electrónico ya está registrado por otro usuario.',
            'tipo_contrato.in' => 'El tipo de contrato debe ser Ítem o Contrato.',
        ]);

        try {
            DB::beginTransaction();

            // 1. Crear el usuario para el docente (Rol 3 = DOCENTE)
            $username = User::generateUsername($request->nombres, $request->apellidos ?? '');
            $email = $request->correo_electronico ?: ($username . '@eie.edu.bo');
            $cleanCi = trim($request->ci);

            $user = User::create([
                'id_rol' => 3, // DOCENTE
                'correo_institucional' => $email,
                'usuario' => $username,
                'password' => Hash::make($cleanCi),
                'debe_cambiar_password' => true,
                'nombres' => $request->nombres,
                'apellidos' => $request->apellidos,
                'ci' => $request->ci,
                'expedido' => $request->expedido,
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
                'fecha_nacimiento' => $request->fecha_nacimiento,
                'lugar_nacimiento' => $request->lugar_nacimiento,
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

        // Normalizar cadenas vacías a null para evitar falsos negativos en validaciones nullable
        $inputs = $request->all();
        foreach (['correo_electronico', 'telefono', 'tipo_contrato', 'fecha_contrato', 'fecha_inicio_contrato', 'fecha_fin_contrato', 'especialidad', 'lugar_nacimiento', 'fecha_nacimiento'] as $field) {
            if (isset($inputs[$field]) && trim((string)$inputs[$field]) === '') {
                $inputs[$field] = null;
            }
        }
        $request->merge($inputs);

        // Validación con regla de unicidad exceptuando al docente actual
        $request->validate([
            'nombres' => 'required|string|max:255',
            'apellidos' => 'nullable|string|max:255',
            'correo_electronico' => 'nullable|email|max:255|unique:usuarios,correo_institucional,' . ($userId ?: 0) . ',id_usuario',
            'ci' => 'nullable|string|max:30|unique:usuarios,ci,' . ($userId ?: 0) . ',id_usuario',
            'especialidad' => 'nullable|string|max:255',
            'fecha_nacimiento' => 'nullable|date',
            'lugar_nacimiento' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:50',
            'tipo_contrato' => 'nullable|string|in:Contrato,Ítem,Titular,Contratado',
            'fecha_contrato' => 'nullable|date',
            'fecha_inicio_contrato' => 'nullable|date',
            'fecha_fin_contrato' => 'nullable|date',
        ], [
            'nombres.required' => 'El nombre del instructor es obligatorio.',
            'ci.unique' => 'El C.I. ya se encuentra registrado por otro usuario.',
            'correo_electronico.email' => 'El formato del correo electrónico es inválido.',
            'correo_electronico.unique' => 'El correo electrónico ya está registrado por otro usuario.',
            'tipo_contrato.in' => 'El tipo de contrato debe ser Ítem o Contrato.',
        ]);

        try {
            DB::beginTransaction();

            // 1. Actualizar o crear el usuario para el docente (Rol 3 = DOCENTE)
            $cleanCi = trim($request->ci);
            if (!$userId) {
                $username = User::generateUsername($request->nombres, $request->apellidos ?? '');
                $email = $request->correo_electronico ?: ($username . '@eie.edu.bo');
                $user = User::create([
                    'id_rol' => 3, // DOCENTE
                    'correo_institucional' => $email,
                    'usuario' => $username,
                    'password' => Hash::make($cleanCi),
                    'debe_cambiar_password' => true,
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
                    $updateData = [
                        'nombres' => $request->nombres,
                        'apellidos' => $request->apellidos,
                        'ci' => $request->ci,
                        'expedido' => $request->expedido,
                        'correo_institucional' => $request->correo_electronico ?: $user->correo_institucional,
                    ];
                    if (empty($user->usuario)) {
                        $updateData['usuario'] = User::generateUsername($request->nombres, $request->apellidos ?? '', $user->id_usuario);
                    }
                    $user->update($updateData);
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
                'fecha_nacimiento' => $request->fecha_nacimiento,
                'lugar_nacimiento' => $request->lugar_nacimiento,
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
            ->get()
            ->unique('id_paralelo')
            ->values();

        return response()->json([
            'docente' => $docente->load('user'),
            'paralelos' => $paralelos
        ]);
    }

    /**
     * Da de baja a un docente registrando obligatoriamente el informe / motivo de la baja.
     * No se elimina el registro de la base de datos para preservar el historial institucional.
     */
    public function darDeBaja(Request $request, $id)
    {
        $request->validate([
            'motivo_baja' => 'required|string|min:5'
        ], [
            'motivo_baja.required' => 'El informe o motivo de la baja es obligatorio.',
            'motivo_baja.min' => 'El informe o motivo de la baja debe contener al menos 5 caracteres descriptivos.'
        ]);

        try {
            DB::beginTransaction();
            $docente = Docente::with('user')->findOrFail($id);

            $docente->update([
                'estado' => 'De Baja',
                'motivo_baja' => trim($request->motivo_baja),
                'fecha_baja' => now(),
            ]);

            if ($docente->user) {
                $docente->user->update([
                    'estado' => 'INACTIVO'
                ]);
            }

            // Desvincular de paralelos activos para no interferir con las clases en curso
            $docente->paralelos()->detach();

            DB::commit();

            return response()->json([
                'message' => 'El instructor ha sido dado de baja exitosamente con informe registrado.',
                'docente' => $docente->fresh()->load('user')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al procesar la baja del docente: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rehabilitar a un docente dado de baja.
     */
    public function rehabilitar($id)
    {
        try {
            DB::beginTransaction();
            $docente = Docente::with('user')->findOrFail($id);

            $docente->update([
                'estado' => 'Activo',
                'motivo_baja' => null,
                'fecha_baja' => null,
            ]);

            if ($docente->user) {
                $docente->user->update([
                    'estado' => 'ACTIVO'
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'El instructor ha sido reactivado exitosamente.',
                'docente' => $docente->fresh()->load('user')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al reactivar docente: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Intento de eliminación: Redirige a darDeBaja con informe obligatorio.
     */
    public function destroy(Request $request, $id)
    {
        return $this->darDeBaja($request, $id);
    }
}
