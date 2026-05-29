<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Paralelo;
use App\Models\Aula;
use App\Models\Horario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ParaleloController extends Controller
{
    /**
     * Mostrar un listado de todos los paralelos.
     */
    public function index()
    {
        return Paralelo::with(['curso', 'aula', 'docentes.user', 'horarios'])->get();
    }

    /**
     * Guardar un nuevo paralelo en la base de datos.
     */
    public function store(Request $request)
    {
        $request->validate([
            'curso_id' => 'required|exists:cursos,id',
            'aula_id' => 'nullable|exists:aulas,id',
            'nombre' => 'required|string|max:255',
            'docentes' => 'nullable|array',
            'docentes.*' => 'exists:docentes,id',
            'horarios' => 'required|array|min:1',
            'horarios.*' => 'exists:horarios,id'
        ]);

        // Validar cruce de horarios para cada docente
        if ($request->has('docentes')) {
            foreach ($request->docentes as $docenteId) {
                if ($this->checkOverlap($docenteId, $request->horarios)) {
                    return response()->json([
                        'message' => "El docente seleccionado ya tiene clases asignadas en uno de los horarios elegidos.",
                        'docente_id' => $docenteId
                    ], 422);
                }
            }
        }

        try {
            DB::beginTransaction();

            $paralelo = Paralelo::create($request->only(['curso_id', 'aula_id', 'nombre']));

            $paralelo->horarios()->sync($request->horarios);

            if ($request->has('docentes')) {
                $paralelo->docentes()->sync($request->docentes);
            }

            DB::commit();

            return response()->json([
                'message' => 'Paralelo creado exitosamente',
                'paralelo' => $paralelo->load(['curso', 'aula', 'docentes.user', 'horarios'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al crear paralelo', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Mostrar un paralelo específico.
     */
    public function show($id)
    {
        return Paralelo::with(['curso', 'aula', 'docentes.user', 'horarios'])->findOrFail($id);
    }

    /**
     * Actualizar un paralelo específico en la base de datos.
     */
    public function update(Request $request, $id)
    {
        $paralelo = Paralelo::findOrFail($id);

        $request->validate([
            'curso_id' => 'required|exists:cursos,id',
            'aula_id' => 'nullable|exists:aulas,id',
            'nombre' => 'required|string|max:255',
            'docentes' => 'nullable|array',
            'docentes.*' => 'exists:docentes,id',
            'horarios' => 'required|array|min:1',
            'horarios.*' => 'exists:horarios,id'
        ]);

        // Validar cruce de horarios para cada docente (excluyendo este paralelo)
        if ($request->has('docentes')) {
            foreach ($request->docentes as $docenteId) {
                if ($this->checkOverlap($docenteId, $request->horarios, $paralelo->id)) {
                    return response()->json([
                        'message' => "Conflicto de horario: El docente ya está ocupado en otro paralelo en los horarios seleccionados.",
                        'docente_id' => $docenteId
                    ], 422);
                }
            }
        }

        try {
            DB::beginTransaction();

            $paralelo->update($request->only(['curso_id', 'aula_id', 'nombre']));

            $paralelo->horarios()->sync($request->horarios);

            if ($request->has('docentes')) {
                $paralelo->docentes()->sync($request->docentes);
            }

            DB::commit();

            return response()->json([
                'message' => 'Paralelo actualizado exitosamente',
                'paralelo' => $paralelo->load(['curso', 'aula', 'docentes.user', 'horarios'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al actualizar paralelo', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Valida si un docente tiene choques de horario con otros paralelos
     */
    private function checkOverlap($docenteId, $newHorarioIds, $excludeParaleloId = null)
    {
        // Buscamos todos los horarios ocupados por el docente en otros paralelos
        $query = DB::table('horario_paralelo')
            ->join('docente_paralelo', 'horario_paralelo.paralelo_id', '=', 'docente_paralelo.paralelo_id')
            ->where('docente_paralelo.docente_id', $docenteId);

        if ($excludeParaleloId) {
            $query->where('horario_paralelo.paralelo_id', '!=', $excludeParaleloId);
        }

        $ocupados = $query->pluck('horario_id')->toArray();

        // Si hay alguna intersección entre los horarios ocupados y los nuevos, hay choque
        return count(array_intersect($ocupados, $newHorarioIds)) > 0;
    }

    /**
     * Eliminar un paralelo de la base de datos.
     */
    public function destroy($id)
    {
        $paralelo = Paralelo::findOrFail($id);
        $paralelo->delete();

        return response()->json(['message' => 'Paralelo eliminado exitosamente']);
    }

    /**
     * Metodos auxiliares para el frontend
     */
    public function getAulas()
    {
        return response()->json(Aula::all());
    }

    public function storeAula(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:aulas,nombre',
            'capacidad' => 'nullable|integer|min:1'
        ]);

        $aula = Aula::create($request->only(['nombre', 'capacidad']));

        return response()->json([
            'message' => 'Aula creada exitosamente',
            'aula' => $aula
        ], 201);
    }

    public function updateAula(Request $request, $id)
    {
        try {
            $aula = Aula::findOrFail($id);

            $request->validate([
                'nombre' => 'required|string|max:255|unique:aulas,nombre,' . $aula->id,
                'capacidad' => 'nullable|integer|min:1'
            ]);

            $aula->update($request->only(['nombre', 'capacidad']));

            return response()->json([
                'message' => 'Aula actualizada exitosamente',
                'aula' => $aula
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar aula.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroyAula($id)
    {
        try {
            $aula = Aula::findOrFail($id);

            // Verificar si el aula está siendo usada por algún paralelo
            if ($aula->paralelos()->exists()) {
                return response()->json([
                    'message' => 'No se puede eliminar el aula porque está asignada a uno o más paralelos.'
                ], 422);
            }

            $aula->delete();

            return response()->json([
                'message' => 'Aula eliminada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar aula.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getHorarios()
    {
        return response()->json(Horario::all());
    }
}
