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
                'paralelo',
                'notas'
            ])->findOrFail($id);
            
            $est = $inscripcion->estudiante;
            $usr = $est ? $est->user : null;
            $cur = $inscripcion->curso;
            $idm = $cur ? $cur->idioma : null;

            $estudianteData = (object)[
                'id_estudiante' => $est->id_estudiante ?? $est->id ?? $id,
                'nombres' => $est->nombres ?? $usr->nombres ?? '',
                'apellidos' => $est->apellidos ?? $usr->apellidos ?? '',
                'ci' => $est->ci ?? $usr->ci ?? (string)$id,
                'grado_academico' => $est->grado_academico ?? '',
                'arma_especialidad' => $est->arma_especialidad ?? '',
                'celular' => $est->celular ?? '',
                'domicilio' => $est->domicilio ?? '',
            ];

            $cursoData = (object)[
                'idioma' => $idm->nombre_idioma ?? $idm->nombre ?? 'INGLÉS',
                'nivel' => $cur->nivel ?? 'NIVEL I (BOOK 1-6)',
                'modalidad' => $cur->modalidad ?? 'PRESENCIAL',
            ];

            $meses = [
                '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
                '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
                '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'
            ];
            $mesNombre = $meses[date('m')] ?? 'Julio';

            $notasCollection = $inscripcion->notas ?: collect();

            $notasLibros = [
                1 => 0.00,
                2 => 0.00,
                3 => 0.00,
                4 => 0.00,
                5 => 0.00,
                6 => 0.00,
            ];
            $examenNivel = 0.00;

            foreach ($notasCollection as $n) {
                $val = (float)($n->nota ?? $n->puntaje ?? 0);
                $periodo = strtolower(trim($n->periodo ?? $n->descripcion ?? ''));

                if (str_contains($periodo, 'examen') || str_contains($periodo, 'nivel')) {
                    $examenNivel = $val;
                } elseif (preg_match('/(\d+)/', $periodo, $matches)) {
                    $numLibro = (int)$matches[1];
                    if ($numLibro >= 1 && $numLibro <= 6) {
                        $notasLibros[$numLibro] = $val;
                    }
                }
            }

            // Si hay notas pero no por periodos especificos, mapear secuencialmente
            if (array_sum($notasLibros) == 0 && $notasCollection->count() > 0) {
                $idx = 1;
                foreach ($notasCollection as $n) {
                    if ($idx <= 6) {
                        $notasLibros[$idx] = (float)($n->nota ?? $n->puntaje ?? 0);
                        $idx++;
                    }
                }
            }

            $validas = array_filter($notasLibros, fn($n) => $n > 0);
            $promedioLibros = count($validas) > 0 ? round(array_sum($validas) / count($validas), 2) : 0.00;

            $promedio80 = round($promedioLibros * 0.8, 2);
            $examen20 = round($examenNivel * 0.2, 2);
            $promedioNivel = round($promedio80 + $examen20, 2);
            $promedioGral = $promedioNivel > 0 ? $promedioNivel : $promedioLibros;

            $data = [
                'estudiante' => $estudianteData,
                'curso' => $cursoData,
                'inscripcion' => $inscripcion,
                'paralelo' => (object)['nombre' => ($inscripcion->paralelo ? $inscripcion->paralelo->nombre_paralelo : 'Asignado según cronograma')],
                'notasLibros' => $notasLibros,
                'promedioLibros' => $promedioLibros,
                'promedio80' => $promedio80,
                'examenNivel' => $examenNivel,
                'examen20' => $examen20,
                'promedioNivel' => $promedioNivel,
                'promedioGral' => $promedioGral,
                'mesNombre' => $mesNombre
            ];

            $pdf = Pdf::loadView('pdf.constancia', $data);
            $pdf->setPaper('letter', 'portrait');
            
            $ci = $estudianteData->ci ?: $id;
            return $pdf->download('Reporte_Notas_'.$ci.'.pdf');

        } catch (Throwable $e) {
            \Log::error("Error al generar certificado PDF para inscripción {$id}: " . $e->getMessage());
            return response()->json([
                'message' => 'Error interno al generar la constancia de notas PDF.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
