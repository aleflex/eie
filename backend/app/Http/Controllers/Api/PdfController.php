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
        $inscripcion = Inscripcion::with(['estudiante', 'curso'])->findOrFail($id);
        
        $data = [
            'estudiante' => $inscripcion->estudiante,
            'curso' => $inscripcion->curso,
            'inscripcion' => $inscripcion,
            'paralelo' => (object)['nombre' => 'Asignado según cronograma']
        ];

        $pdf = Pdf::loadView('pdf.constancia', $data);
        
        return $pdf->download('constancia_'.$inscripcion->estudiante->ci.'.pdf');
    }
}
