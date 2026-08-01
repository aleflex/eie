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
        $notas = Nota::where('id_inscripcion', $inscripcionId)->get();
        
        $response = $notas->map(function($nota) {
            return [
                'id_nota' => $nota->id_nota,
                'id' => $nota->id_nota,
                'id_inscripcion' => $nota->id_inscripcion,
                'inscripcion_id' => $nota->id_inscripcion,
                'nota' => $nota->nota,
                'periodo' => $nota->periodo,
                'observacion' => $nota->observacion,
                'created_at' => $nota->created_at,
                'updated_at' => $nota->updated_at
            ];
        });
        
        return response()->json($response);
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

        $nota = Nota::updateOrCreate(
            [
                'id_inscripcion' => $inscripcion->id_inscripcion,
                'periodo' => $validated['periodo'],
            ],
            [
                'nota' => $validated['nota'],
                'observacion' => $validated['observacion'] ?? null,
            ]
        );

        return response()->json([
            'message' => 'Nota guardada correctamente',
            'nota'    => [
                'id_nota' => $nota->id_nota,
                'id' => $nota->id_nota,
                'inscripcion_id' => $nota->id_inscripcion,
                'nota' => $validated['nota'],
                'periodo' => $validated['periodo'],
                'observacion' => $nota->observacion
            ]
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
        $asistencias = $inscripcion->asistencias()->orderBy('fecha')->get()->map(function($asistencia) {
            return [
                'id_asistencia' => $asistencia->id_asistencia,
                'id' => $asistencia->id_asistencia,
                'inscripcion_id' => $asistencia->id_inscripcion,
                'fecha' => $asistencia->fecha,
                'estado' => $asistencia->estado,
                'observacion' => $asistencia->observacion,
                'created_at' => $asistencia->created_at,
                'updated_at' => $asistencia->updated_at
            ];
        });
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
                'id_inscripcion' => $inscripcion->id_inscripcion,
                'fecha'          => $validated['fecha'],
            ],
            [
                'estado'      => $validated['estado'],
                'observacion' => $validated['observacion'] ?? null,
            ]
        );

        return response()->json([
            'message'    => 'Asistencia registrada correctamente',
            'asistencia' => [
                'id_asistencia' => $asistencia->id_asistencia,
                'id' => $asistencia->id_asistencia,
                'inscripcion_id' => $asistencia->id_inscripcion,
                'fecha' => $asistencia->fecha,
                'estado' => $asistencia->estado,
                'observacion' => $asistencia->observacion
            ]
        ]);
    }

    /**
     * Obtener resumen de asistencia de todos los estudiantes de un paralelo
     */
    public function getAsistenciasParalelo($paraleloId)
    {
        $inscripciones = Inscripcion::where('id_paralelo', $paraleloId)
            ->with([
                'estudiante.user',
                'asistencias', 
                'notas'
            ])
            ->get()->map(function($ins) {
                $flatNotas = [];
                $nota = $ins->notas->first();
                if ($nota) {
                    if ($nota->nota_1 !== null) {
                        $flatNotas[] = [
                            'id_nota' => $nota->id_nota,
                            'id_inscripcion' => $nota->id_inscripcion,
                            'nota' => $nota->nota_1,
                            'periodo' => 'Parcial 1',
                            'observacion' => $nota->observacion
                        ];
                    }
                    if ($nota->nota_2 !== null) {
                        $flatNotas[] = [
                            'id_nota' => $nota->id_nota,
                            'id_inscripcion' => $nota->id_inscripcion,
                            'nota' => $nota->nota_2,
                            'periodo' => 'Parcial 2',
                            'observacion' => $nota->observacion
                        ];
                    }
                    if ($nota->nota_final !== null) {
                        $flatNotas[] = [
                            'id_nota' => $nota->id_nota,
                            'id_inscripcion' => $nota->id_inscripcion,
                            'nota' => $nota->nota_final,
                            'periodo' => 'Examen Final',
                            'observacion' => $nota->observacion
                        ];
                    }
                }

                return [
                    'id_inscripcion' => $ins->id_inscripcion,
                    'id' => $ins->id_inscripcion,
                    'estudiante_id' => $ins->id_estudiante,
                    'paralelo_id' => $ins->id_paralelo,
                    'estado' => $ins->estado,
                    'estudiante' => $ins->estudiante ? [
                        'id' => $ins->estudiante->id_estudiante,
                        'nombres' => $ins->estudiante->nombres,
                        'apellidos' => $ins->estudiante->apellidos,
                        'ci' => $ins->estudiante->ci,
                        'grado_academico' => $ins->estudiante->grado_academico,
                        'arma_especialidad' => $ins->estudiante->arma_especialidad,
                    ] : null,
                    'asistencias' => $ins->asistencias,
                    'notas' => $flatNotas
                ];
            });

        return response()->json($inscripciones);
    }
}
