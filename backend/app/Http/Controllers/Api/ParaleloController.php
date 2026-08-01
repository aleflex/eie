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
    const STATIC_HORARIOS = [
        1 => ['dia_semana' => 'Lunes a Viernes', 'hora_inicio' => '08:00:00', 'hora_fin' => '10:00:00'],
        2 => ['dia_semana' => 'Lunes a Viernes', 'hora_inicio' => '10:00:00', 'hora_fin' => '12:00:00'],
        3 => ['dia_semana' => 'Lunes a Viernes', 'hora_inicio' => '14:00:00', 'hora_fin' => '16:00:00'],
        4 => ['dia_semana' => 'Lunes a Viernes', 'hora_inicio' => '16:00:00', 'hora_fin' => '18:00:00'],
        5 => ['dia_semana' => 'Lunes a Viernes', 'hora_inicio' => '18:30:00', 'hora_fin' => '20:30:00'],
        6 => ['dia_semana' => 'Lunes a Viernes', 'hora_inicio' => '19:00:00', 'hora_fin' => '21:00:00'],
        7 => ['dia_semana' => 'Sábado', 'hora_inicio' => '08:00:00', 'hora_fin' => '13:00:00'],
        8 => ['dia_semana' => 'Sábado', 'hora_inicio' => '14:00:00', 'hora_fin' => '19:00:00'],
    ];

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
            'curso_id' => 'required|exists:cursos,id_curso',
            'aula_id' => 'nullable|exists:aulas,id_aula',
            'nombre' => 'required|string|max:255',
            'docentes' => 'nullable|array',
            'docentes.*' => 'exists:docentes,id_docente',
            'horarios' => 'required|array|min:1',
            'horarios.*' => 'integer|min:1|max:8'
        ]);

        // Validar cruce de horarios para cada docente
        if ($request->has('docentes') && !empty($request->docentes)) {
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

            $paralelo = Paralelo::create([
                'id_curso' => $request->curso_id,
                'id_aula' => $request->aula_id,
                'nombre_paralelo' => $request->nombre,
            ]);

            if ($request->has('docentes') && !empty($request->docentes)) {
                $paralelo->docentes()->attach($request->docentes);
            }

            // Crear los registros de horarios para el paralelo
            foreach ($request->horarios as $staticId) {
                $static = self::STATIC_HORARIOS[$staticId];
                $horario = Horario::create([
                    'dia_semana' => $static['dia_semana'],
                    'hora_inicio' => $static['hora_inicio'],
                    'hora_fin' => $static['hora_fin']
                ]);
                $paralelo->horarios()->attach($horario->id_horario);
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
            'curso_id' => 'required|exists:cursos,id_curso',
            'aula_id' => 'nullable|exists:aulas,id_aula',
            'nombre' => 'required|string|max:255',
            'docentes' => 'nullable|array',
            'docentes.*' => 'exists:docentes,id_docente',
            'horarios' => 'required|array|min:1',
            'horarios.*' => 'integer|min:1|max:8'
        ]);

        // Validar cruce de horarios para cada docente (excluyendo este paralelo)
        if ($request->has('docentes') && !empty($request->docentes)) {
            foreach ($request->docentes as $docenteId) {
                if ($this->checkOverlap($docenteId, $request->horarios, $paralelo->id_paralelo)) {
                    return response()->json([
                        'message' => "Conflicto de horario: El docente ya está ocupado en otro paralelo en los horarios seleccionados.",
                        'docente_id' => $docenteId
                    ], 422);
                }
            }
        }

        try {
            DB::beginTransaction();

            $paralelo->update([
                'id_curso' => $request->curso_id,
                'id_aula' => $request->aula_id,
                'nombre_paralelo' => $request->nombre,
            ]);

            if ($request->has('docentes')) {
                $paralelo->docentes()->sync($request->docentes);
            }

            // Obtener y eliminar horarios anteriores de la base de datos y la relación
            $horarioIds = $paralelo->horarios()->pluck('horarios.id_horario');
            $paralelo->horarios()->detach();
            Horario::whereIn('id_horario', $horarioIds)->delete();

            // Crear nuevos horarios
            foreach ($request->horarios as $staticId) {
                $static = self::STATIC_HORARIOS[$staticId];
                $horario = Horario::create([
                    'dia_semana' => $static['dia_semana'],
                    'hora_inicio' => $static['hora_inicio'],
                    'hora_fin' => $static['hora_fin']
                ]);
                $paralelo->horarios()->attach($horario->id_horario);
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
        $paralelosQuery = Paralelo::whereHas('docentes', function($q) use ($docenteId) {
            $q->where('docentes.id_docente', $docenteId);
        });
        if ($excludeParaleloId) {
            $paralelosQuery->where('id_paralelo', '!=', $excludeParaleloId);
        }
        $paralelos = $paralelosQuery->with('horarios')->get();

        $ocupados = [];
        foreach ($paralelos as $p) {
            foreach ($p->horarios as $h) {
                foreach (self::STATIC_HORARIOS as $id => $static) {
                    if ($h->dia_semana === $static['dia_semana'] &&
                        substr($h->hora_inicio, 0, 5) === substr($static['hora_inicio'], 0, 5) &&
                        substr($h->hora_fin, 0, 5) === substr($static['hora_fin'], 0, 5)) {
                        $ocupados[] = $id;
                        break;
                    }
                }
            }
        }

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
            'nombre' => 'required|string|max:255|unique:aulas,nombre_aula',
            'capacidad' => 'nullable|integer|min:1'
        ]);

        $aula = Aula::create([
            'nombre_aula' => $request->nombre,
            'capacidad' => $request->capacidad
        ]);

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
                'nombre' => 'required|string|max:255|unique:aulas,nombre_aula,' . $aula->id_aula . ',id_aula',
                'capacidad' => 'nullable|integer|min:1'
            ]);

            $aula->update([
                'nombre_aula' => $request->nombre,
                'capacidad' => $request->capacidad
            ]);

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
        $response = [];
        foreach (self::STATIC_HORARIOS as $id => $static) {
            $response[] = [
                'id' => $id,
                'id_horario' => $id,
                'dia_semana' => $static['dia_semana'],
                'hora_inicio' => $static['hora_inicio'],
                'hora_fin' => $static['hora_fin']
            ];
        }
        return response()->json($response);
    }
}
