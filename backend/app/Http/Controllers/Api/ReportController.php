<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Inscripcion;
use App\Models\Paralelo;
use App\Models\Aula;
use App\Models\Curso;
use App\Models\Idioma;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    /**
     * RF 18 - HU 18: Estadísticas por Idioma (groupBy y count)
     */
    public function getLanguageStatistics(Request $request)
    {
        $filters = $request->only(['id_idioma', 'id_nivel', 'id_curso', 'id_paralelo', 'estado', 'fecha_desde', 'fecha_hasta']);

        $query = DB::table('inscripciones')
            ->join('cursos', 'inscripciones.id_curso', '=', 'cursos.id_curso')
            ->join('idiomas', 'cursos.id_idioma', '=', 'idiomas.id_idioma');

        if (!empty($filters['id_idioma'])) {
            $query->where('cursos.id_idioma', $filters['id_idioma']);
        }
        if (!empty($filters['id_nivel'])) {
            $query->where('cursos.id_nivel', $filters['id_nivel']);
        }
        if (!empty($filters['id_curso'])) {
            $query->where('inscripciones.id_curso', $filters['id_curso']);
        }
        if (!empty($filters['id_paralelo'])) {
            $query->where('inscripciones.id_paralelo', $filters['id_paralelo']);
        }
        if (!empty($filters['estado'])) {
            $query->where('inscripciones.estado', strtolower($filters['estado']));
        }
        if (!empty($filters['fecha_desde'])) {
            $query->whereDate('inscripciones.fecha_registro', '>=', $filters['fecha_desde']);
        }
        if (!empty($filters['fecha_hasta'])) {
            $query->whereDate('inscripciones.fecha_registro', '<=', $filters['fecha_hasta']);
        }

        $stats = $query->select(
            'idiomas.id_idioma',
            'idiomas.nombre_idioma as idioma',
            DB::raw('COUNT(inscripciones.id_inscripcion) as total_estudiantes')
        )
        ->groupBy('idiomas.id_idioma', 'idiomas.nombre_idioma')
        ->orderByDesc('total_estudiantes')
        ->get();

        $totalGeneral = $stats->sum('total_estudiantes');

        $result = $stats->map(function ($item) use ($totalGeneral) {
            return [
                'id_idioma' => $item->id_idioma,
                'idioma' => $item->idioma,
                'total_estudiantes' => (int) $item->total_estudiantes,
                'porcentaje' => $totalGeneral > 0 ? round(($item->total_estudiantes / $totalGeneral) * 100, 1) : 0
            ];
        });

        return response()->json([
            'total_general' => $totalGeneral,
            'estadisticas' => $result
        ]);
    }

    /**
     * RF 19 - HU 19: Porcentajes de ocupación de aulas
     */
    public function getClassroomOccupancy(Request $request)
    {
        $filters = $request->only(['id_idioma', 'id_nivel', 'id_curso', 'id_paralelo', 'estado', 'fecha_desde', 'fecha_hasta']);

        $paralelos = Paralelo::with(['aula', 'curso.idioma', 'curso.nivelRel', 'inscripciones' => function ($q) use ($filters) {
            $q->filterMultiCriteria($filters);
        }])->get();

        $ocupacion = $paralelos->map(function ($paralelo) {
            $capacidad = $paralelo->aula ? ($paralelo->aula->capacidad ?: 30) : 30;
            $inscritosCount = $paralelo->inscripciones->count();
            $porcentaje = $capacidad > 0 ? round(($inscritosCount / $capacidad) * 100, 1) : 0;

            return [
                'id_paralelo' => $paralelo->id_paralelo,
                'nombre_paralelo' => $paralelo->nombre_paralelo,
                'curso' => $paralelo->curso ? $paralelo->curso->nombre_curso : 'N/A',
                'aula' => $paralelo->aula ? ($paralelo->aula->nombre_aula ?: $paralelo->aula->nombre) : 'Sin Aula',
                'capacidad' => $capacidad,
                'inscritos' => $inscritosCount,
                'porcentaje_ocupacion' => min($porcentaje, 100),
                'estado_ocupacion' => $porcentaje >= 100 ? 'Lleno' : ($porcentaje >= 75 ? 'Alta' : ($porcentaje >= 40 ? 'Media' : 'Baja'))
            ];
        });

        $totalCapacidad = $ocupacion->sum('capacidad');
        $totalInscritos = $ocupacion->sum('inscritos');
        $promedioOcupacion = $totalCapacidad > 0 ? round(($totalInscritos / $totalCapacidad) * 100, 1) : 0;

        return response()->json([
            'total_capacidad' => $totalCapacidad,
            'total_inscritos' => $totalInscritos,
            'promedio_ocupacion' => $promedioOcupacion,
            'aulas' => $ocupacion
        ]);
    }

    /**
     * Resumen general para KPI Dashboard
     */
    public function getDashboardSummary(Request $request)
    {
        $filters = $request->only(['id_idioma', 'id_nivel', 'id_curso', 'id_paralelo', 'estado', 'fecha_desde', 'fecha_hasta']);

        $inscripcionesQuery = Inscripcion::query()->filterMultiCriteria($filters);

        $totalInscritos = (clone $inscripcionesQuery)->count();
        $totalHabilitados = (clone $inscripcionesQuery)->where('estado', 'activo')->count();
        $totalPendientes = (clone $inscripcionesQuery)->where('estado', 'pendiente')->count();
        $totalRetirados = (clone $inscripcionesQuery)->where('estado', 'retirado')->count();

        // Idioma con mayor demanda
        $topLanguage = DB::table('inscripciones')
            ->join('cursos', 'inscripciones.id_curso', '=', 'cursos.id_curso')
            ->join('idiomas', 'cursos.id_idioma', '=', 'idiomas.id_idioma')
            ->select('idiomas.nombre_idioma', DB::raw('COUNT(inscripciones.id_inscripcion) as total'))
            ->groupBy('idiomas.id_idioma', 'idiomas.nombre_idioma')
            ->orderByDesc('total')
            ->first();

        // Ocupación general de aulas
        $occupancyRes = $this->getClassroomOccupancy($request)->getData(true);

        return response()->json([
            'total_inscritos' => $totalInscritos,
            'habilitados' => $totalHabilitados,
            'pendientes' => $totalPendientes,
            'retirados' => $totalRetirados,
            'porcentaje_habilitados' => $totalInscritos > 0 ? round(($totalHabilitados / $totalInscritos) * 100, 1) : 0,
            'idioma_top' => $topLanguage ? $topLanguage->nombre_idioma : 'N/A',
            'ocupacion_promedio' => $occupancyRes['promedio_ocupacion'] ?? 0
        ]);
    }

    /**
     * RF 20 - HU 20: Exportación a Excel (.xlsx)
     */
    public function exportExcel(Request $request)
    {
        $filters = $request->only(['id_idioma', 'id_nivel', 'id_curso', 'id_paralelo', 'estado', 'fecha_desde', 'fecha_hasta']);

        $inscripciones = Inscripcion::with(['estudiante.user', 'curso.idioma', 'curso.nivelRel', 'paralelo', 'notas', 'asistencias'])
            ->filterMultiCriteria($filters)
            ->get();

        $fileName = 'Relacion_Nominal_EIE_' . date('Ymd_His') . '.xlsx';

        $headers = [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$fileName\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function () use ($inscripciones) {
            echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
            echo '<head><meta charset="UTF-8">';
            echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Relacion Nominal</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
            echo '<style>';
            echo 'table { border-collapse: collapse; font-family: Arial, sans-serif; font-size: 9px; }';
            echo 'th, td { border: 1px solid #000; padding: 3px 4px; text-align: center; vertical-align: middle; }';
            echo 'th { background-color: #ffffff; color: #000; font-weight: bold; font-size: 8.5px; }';
            echo '.header-title { font-weight: bold; font-size: 10.5px; border: none; text-align: center; }';
            echo '.left { text-align: left; }';
            echo '</style>';
            echo '</head><body>';

            echo '<table>';
            echo '<tr><td colspan="31" class="header-title">DEPARTAMENTO VI - EDUCACIÓN</td></tr>';
            echo '<tr><td colspan="31" class="header-title">ESCUELA DE IDIOMAS DEL EJÉRCITO</td></tr>';
            echo '<tr><td colspan="31" class="header-title"><u>BOLIVIA</u></td></tr>';
            echo '<tr><td colspan="31" class="header-title">&nbsp;</td></tr>';
            echo '<tr><td colspan="31" class="header-title">RELACION NOMINAL DEL PERSONAL DE ALUMNOS</td></tr>';
            echo '<tr><td colspan="31" class="header-title">DEL CURSO: CGEC1108A26I FILIAL: COCHABAMBA BOOK: NIVEL 1</td></tr>';
            echo '<tr><td colspan="31" class="header-title">&nbsp;</td></tr>';

            // Column Header 1
            echo '<tr>';
            echo '<th rowspan="2">Nro</th>';
            echo '<th rowspan="2">Grado</th>';
            echo '<th colspan="2">APELLIDOS</th>';
            echo '<th rowspan="2">Nombres</th>';
            echo '<th colspan="14">ASISTENCIA</th>';
            echo '<th colspan="4">HW</th>';
            echo '<th colspan="4">EE</th>';
            echo '<th colspan="4">LAB</th>';
            echo '<th rowspan="2">PROM<br>100</th>';
            echo '<th rowspan="2">PART<br>100</th>';
            echo '<th rowspan="2">OP<br>100</th>';
            echo '<th rowspan="2">OBS</th>';
            echo '</tr>';

            // Column Header 2
            echo '<tr>';
            echo '<th>Ap. Paterno</th>';
            echo '<th>Ap. Materno</th>';
            for ($a = 1; $a <= 14; $a++) {
                echo '<th>' . sprintf('%02d', $a) . '</th>';
            }
            for ($h = 1; $h <= 4; $h++) { echo '<th>' . $h . '</th>'; }
            for ($e = 1; $e <= 4; $e++) { echo '<th>' . $e . '</th>'; }
            for ($l = 1; $l <= 4; $l++) { echo '<th>' . $l . '</th>'; }
            echo '</tr>';

            $i = 1;
            foreach ($inscripciones as $insc) {
                $est = $insc->estudiante;
                $paterno = 'N/A';
                $materno = '-';
                $nombres = 'N/A';
                $grado = $est ? ($est->grado_academico ?: 'SR') : 'SR';

                if ($est) {
                    $parts = explode(' ', trim($est->apellidos ?? ''));
                    $paterno = $parts[0] ?? 'N/A';
                    $materno = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '-';
                    $nombres = $est->nombres ?? 'N/A';
                }

                echo '<tr>';
                echo '<td>' . $i++ . '</td>';
                echo '<td>' . htmlspecialchars(strtoupper($grado)) . '</td>';
                echo '<td class="left">' . htmlspecialchars(strtoupper($paterno)) . '</td>';
                echo '<td class="left">' . htmlspecialchars(strtoupper($materno)) . '</td>';
                echo '<td class="left">' . htmlspecialchars(strtoupper($nombres)) . '</td>';

                // 14 cols asistencia
                for ($a = 1; $a <= 14; $a++) { echo '<td></td>'; }
                // 4 HW
                for ($h = 1; $h <= 4; $h++) { echo '<td></td>'; }
                // 4 EE
                for ($e = 1; $e <= 4; $e++) { echo '<td></td>'; }
                // 4 LAB
                for ($l = 1; $l <= 4; $l++) { echo '<td></td>'; }

                $notasAvg = $insc->notas->count() > 0 ? round($insc->notas->avg('puntaje')) : 100;
                echo '<td><b>' . $notasAvg . '</b></td>';
                echo '<td>100</td>';
                echo '<td>100</td>';
                echo '<td></td>';
                echo '</tr>';
            }

            echo '<tr><td colspan="31" style="border:none;">&nbsp;</td></tr>';
            echo '<tr><td colspan="31" class="left" style="border:none;"><b>NOMBRE DEL DOCENTE:</b> __________________________________________________</td></tr>';
            echo '<tr><td colspan="31" class="left" style="border:none;"><b>NOMBRE DE EC:</b> ______________________________________________________</td></tr>';
            echo '<tr><td colspan="31" class="left" style="border:none;"><b>OBSERVACIONES:</b> ____________________________________________________</td></tr>';
            echo '<tr><td colspan="31" style="border:none;">&nbsp;</td></tr>';

            // Glosario
            echo '<tr>';
            echo '<td colspan="8" class="left" style="border: 1px solid #000; font-size: 8.5px; font-weight: bold; background: #ffffff;">';
            echo 'GLOSARIO:<br>';
            echo 'A &nbsp;&nbsp;&nbsp;&nbsp;- ASISTIO A CLASE<br>';
            echo '. &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- NO ASISTIO A CLASE<br>';
            echo 'L &nbsp;&nbsp;&nbsp;&nbsp;- LICENCIA<br>';
            echo 'S &nbsp;&nbsp;&nbsp;&nbsp;- ASISTIO SIN CAMARA';
            echo '</td>';
            echo '</tr>';

            echo '</table>';
            echo '</body></html>';
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * RF 20 - HU 20: Exportación a PDF Oficial
     */
    public function exportPdf(Request $request)
    {
        $filters = $request->only(['id_idioma', 'id_nivel', 'id_curso', 'id_paralelo', 'estado', 'fecha_desde', 'fecha_hasta']);

        $inscripciones = Inscripcion::with(['estudiante.user', 'curso.idioma', 'curso.nivelRel', 'paralelo', 'notas', 'asistencias'])
            ->filterMultiCriteria($filters)
            ->get();

        $data = [
            'titulo' => 'REPORTE Y CENTRALIZADOR ACADÉMICO OFICIAL',
            'institucion' => 'ESCUELA DE IDIOMAS DEL EJÉRCITO',
            'departamento' => 'Cochabamba - Bolivia',
            'fecha' => date('d/m/Y H:i'),
            'total_registros' => $inscripciones->count(),
            'inscripciones' => $inscripciones
        ];

        $pdf = Pdf::loadView('pdf.reporte_oficial', $data);
        $pdf->setPaper('letter', 'landscape');

        return $pdf->download('Reporte_Centralizador_EIE_' . date('Ymd_His') . '.pdf');
    }
}
