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
            'correo_electronico' => 'nullable|string|email|max:255',
            'ci' => 'nullable|string|max:20|unique:docentes,ci',
            'especialidad' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:50',
            'tipo_contrato' => 'nullable|string|in:Contrato,Ítem',
            'fecha_contrato' => 'nullable|date',
        ]);

        try {
            DB::beginTransaction();

            // Crear el perfil del docente localmente (sin credenciales de ingreso iniciales)
            $docente = Docente::create([
                'user_id' => null,
                'nombres' => $request->nombres,
                'apellidos' => $request->apellidos,
                'correo_electronico' => $request->correo_electronico,
                'ci' => $request->ci,
                'especialidad' => $request->especialidad,
                'telefono' => $request->telefono,
                'tipo_contrato' => $request->tipo_contrato,
                'fecha_contrato' => $request->tipo_contrato === 'Contrato' ? $request->fecha_contrato : null,
            ]);

            if ($request->hasFile('foto')) {
                $path = $request->file('foto')->store('docentes/' . ($docente->ci ?? $docente->id), 'public');
                $docente->foto_url = '/storage/docentes/' . ($docente->ci ?? $docente->id) . '/' . basename($path);
                $docente->save();
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

        // Validación con regla de unicidad exceptuando al docente actual
        $request->validate([
            'nombres' => 'required|string|max:255',
            'apellidos' => 'nullable|string|max:255',
            'correo_electronico' => 'nullable|string|email|max:255',
            'ci' => 'nullable|string|max:20|unique:docentes,ci,' . $docente->id,
            'especialidad' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:50',
            'tipo_contrato' => 'nullable|string|in:Contrato,Ítem',
            'fecha_contrato' => 'nullable|date',
        ]);

        try {
            DB::beginTransaction();

            $updateData = [
                'nombres' => $request->nombres,
                'apellidos' => $request->apellidos,
                'correo_electronico' => $request->correo_electronico,
                'ci' => $request->ci,
                'especialidad' => $request->especialidad,
                'telefono' => $request->telefono,
                'estado' => $request->estado ?? $docente->estado,
                'tipo_contrato' => $request->tipo_contrato,
                'fecha_contrato' => $request->tipo_contrato === 'Contrato' ? $request->fecha_contrato : null,
            ];

            if ($request->hasFile('foto')) {
                $path = $request->file('foto')->store('docentes/' . ($docente->ci ?? $docente->id), 'public');
                $updateData['foto_url'] = '/storage/docentes/' . ($docente->ci ?? $docente->id) . '/' . basename($path);
            }

            $docente->update($updateData);

            // Si tiene una cuenta vinculada, mantener el nombre del usuario sincronizado
            if ($docente->user_id) {
                $user = $docente->user;
                if ($user) {
                    $user->name = $request->nombres . ' ' . ($request->apellidos ?? '');
                    $user->save();
                }
            }

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

        $docente = Docente::where('user_id', $userId)->first();

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
            $docente->delete();
            $user->delete();
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
