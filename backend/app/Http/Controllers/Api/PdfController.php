<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inscripcion;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PdfController extends Controller
{
    public function generateCertificate($id)
    {
        $inscripcion = Inscripcion::with([
            'estudiante.user', 
            'estudiante.gradoRel',
            'estudiante.armaRel',
            'curso.idioma',
            'paralelo'
        ])->findOrFail($id);
        
        // Formatear datos de forma compatible con la vista del PDF
        $estudianteData = (object)[
            'id_estudiante' => $inscripcion->estudiante->id_estudiante,
            'nombres' => $inscripcion->estudiante->user->nombres ?? '',
            'apellidos' => $inscripcion->estudiante->user->apellidos ?? '',
            'ci' => $inscripcion->estudiante->user->ci ?? '',
            'grado_academico' => $inscripcion->estudiante->grado_academico ?? '',
            'arma_especialidad' => $inscripcion->estudiante->arma_especialidad ?? '',
            'celular' => $inscripcion->estudiante->celular,
            'domicilio' => $inscripcion->estudiante->domicilio,
        ];

        $cursoData = (object)[
            'idioma' => $inscripcion->curso->idioma->nombre_idioma ?? '',
            'nivel' => $inscripcion->curso->nivel ?? '',
            'modalidad' => $inscripcion->curso->modalidad ?? '',
        ];

        $data = [
            'estudiante' => $estudianteData,
            'curso' => $cursoData,
            'inscripcion' => $inscripcion,
            'paralelo' => (object)['nombre' => $inscripcion->paralelo->nombre_paralelo ?? 'Asignado según cronograma']
        ];

        $pdf = Pdf::loadView('pdf.constancia', $data);
        
        $ci = $inscripcion->estudiante->user->ci ?? $inscripcion->id_inscripcion;
        return $pdf->download('constancia_'.$ci.'.pdf');
    }
}
