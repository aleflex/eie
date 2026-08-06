<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inscripcion;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Throwable;

class PdfController extends Controller
{
    public function generateCertificate($id)
    {
        try {
            $inscripcion = Inscripcion::with([
                'estudiante.user', 
                'estudiante.gradoRel',
                'estudiante.armaRel',
                'curso.idioma',
                'paralelo'
            ])->findOrFail($id);
            
            $est = $inscripcion->estudiante;
            $usr = $est ? $est->user : null;
            $cur = $inscripcion->curso;
            $idm = $cur ? $cur->idioma : null;
            $par = $inscripcion->paralelo;

            $estudianteData = (object)[
                'id_estudiante' => $est->id_estudiante ?? $est->id ?? $id,
                'nombres' => $est->nombres ?? $usr->nombres ?? '',
                'apellidos' => $est->apellidos ?? $usr->apellidos ?? '',
                'ci' => $est->ci ?? $usr->ci ?? (string)$id,
                'grado_academico' => $est->grado_academico ?? '',
                'celular' => $est->celular ?? $usr->celular ?? '',
                'domicilio' => $est->domicilio ?? '',
                'nombre_padres' => $est->nombre_padres ?? '',
                'contacto_emergencia' => $est->contacto_emergencia ?? '',
            ];

            $cursoData = (object)[
                'idioma' => $idm->nombre_idioma ?? $idm->nombre ?? 'INGLÉS',
                'nivel' => $cur->nivel ?? 'NIVEL 1',
                'modalidad' => $cur->modalidad ?? 'PRESENCIAL',
                'horario' => $cur->horario ?? ($par->horario ?? '08:00 - 12:00 / Turno Regular'),
            ];

            $data = [
                'estudiante' => $estudianteData,
                'curso' => $cursoData,
                'inscripcion' => $inscripcion,
                'paralelo' => (object)[
                    'nombre' => $par->nombre_paralelo ?? $par->nombre ?? 'A',
                    'horario' => $par->horario ?? ''
                ],
            ];

            $pdf = Pdf::loadView('pdf.constancia', $data);
            $pdf->setPaper('letter', 'portrait');
            
            $ci = $estudianteData->ci ?: $id;
            return $pdf->download('Contrato_Inscripcion_'.$ci.'.pdf');

        } catch (Throwable $e) {
            \Log::error("Error al generar contrato de inscripción PDF para id {$id}: " . $e->getMessage());
            return response()->json([
                'message' => 'Error interno al generar el contrato de inscripción PDF.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
