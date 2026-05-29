<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Nota;
use App\Models\Asistencia;
use App\Models\Inscripcion;
use Illuminate\Http\Request;

class NotaAsistenciaController extends Controller
{
    // ==================== NOTAS ====================

    /**
     * Obtener todas las notas de una inscripción
     */
    public function getNotas($inscripcionId)
    {
        $inscripcion = Inscripcion::findOrFail($inscripcionId);
        $notas = $inscripcion->notas()->orderBy('created_at')->get();
        return response()->json($notas);
    }

    /**
     * Guardar o actualizar una nota para una inscripción
     */
    public function saveNota(Request $request, $inscripcionId)
    {
        $inscripcion = Inscripcion::findOrFail($inscripcionId);

        $validated = $request->validate([
            'nota'       => 'required|numeric|min:0|max:100',
            'periodo'    => 'required|string|max:50',
            'observacion'=> 'nullable|string|max:500',
        ]);

        // Upsert: actualizar si ya existe el periodo, crear si no
        $nota = Nota::updateOrCreate(
            [
                'inscripcion_id' => $inscripcion->id,
                'periodo'        => $validated['periodo'],
            ],
            [
                'nota'        => $validated['nota'],
                'observacion' => $validated['observacion'] ?? null,
            ]
        );

        return response()->json([
            'message' => 'Nota guardada correctamente',
            'nota'    => $nota
        ]);
    }

    /**
     * Eliminar una nota
     */
    public function deleteNota($id)
    {
        $nota = Nota::findOrFail($id);
        $nota->delete();
        return response()->json(['message' => 'Nota eliminada']);
    }

    // ==================== ASISTENCIAS ====================

    /**
     * Obtener asistencias de una inscripción
     */
    public function getAsistencias($inscripcionId)
    {
        $inscripcion = Inscripcion::findOrFail($inscripcionId);
        $asistencias = $inscripcion->asistencias()->orderBy('fecha')->get();
        return response()->json($asistencias);
    }

    /**
     * Registrar o actualizar asistencia de una fecha
     */
    public function saveAsistencia(Request $request, $inscripcionId)
    {
        $inscripcion = Inscripcion::findOrFail($inscripcionId);

        $validated = $request->validate([
            'fecha'       => 'required|date',
            'estado'      => 'required|in:presente,ausente,tardanza,justificado',
            'observacion' => 'nullable|string|max:500',
        ]);

        $asistencia = Asistencia::updateOrCreate(
            [
                'inscripcion_id' => $inscripcion->id,
                'fecha'          => $validated['fecha'],
            ],
            [
                'estado'      => $validated['estado'],
                'observacion' => $validated['observacion'] ?? null,
            ]
        );

        return response()->json([
            'message'    => 'Asistencia registrada correctamente',
            'asistencia' => $asistencia
        ]);
    }

    /**
     * Obtener resumen de asistencia de todos los estudiantes de un paralelo
     */
    public function getAsistenciasParalelo($paraleloId)
    {
        $inscripciones = Inscripcion::where('paralelo_id', $paraleloId)
            ->with(['estudiante', 'asistencias', 'notas'])
            ->get();

        return response()->json($inscripciones);
    }
}
