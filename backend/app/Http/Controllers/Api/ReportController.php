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

        $fileName = 'Reporte_Centralizador_EIE_' . date('Ymd_His') . '.xlsx';

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
            echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Centralizador EIE</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
            echo '<style>';
            echo 'table { border-collapse: collapse; font-family: Arial, sans-serif; font-size: 11px; }';
            echo 'th { background-color: #003B71; color: #ffffff; font-weight: bold; text-align: center; border: 1px solid #002d57; padding: 8px; }';
            echo 'td { border: 1px solid #d1d5db; padding: 6px; }';
            echo '.title { font-size: 16px; font-weight: bold; color: #003B71; text-align: center; }';
            echo '.subtitle { font-size: 12px; color: #4b5563; text-align: center; }';
            echo '.num { text-align: center; }';
            echo '</style>';
            echo '</head><body>';

            echo '<table>';
            echo '<tr><td colspan="12" class="title">ESCUELA DE IDIOMAS DEL EJÉRCITO - COCHABAMBA</td></tr>';
            echo '<tr><td colspan="12" class="subtitle">REPORTES Y CENTRALIZADOR ACADÉMICO OFICIAL</td></tr>';
            echo '<tr><td colspan="12"><b>Fecha de Emisión:</b> ' . date('d/m/Y H:i:s') . '</td></tr>';
            echo '<tr><td colspan="12"></td></tr>';

            echo '<tr>';
            echo '<th>Nº</th>';
            echo '<th>ESTUDIANTE</th>';
            echo '<th>C.I.</th>';
            echo '<th>IDIOMA</th>';
            echo '<th>NIVEL</th>';
            echo '<th>PARALELO</th>';
            echo '<th>TUTOR / PADRES</th>';
            echo '<th>CELULAR EMERGENCIA</th>';
            echo '<th>FECHA REGISTRO</th>';
            echo '<th>PROMEDIO NOTAS</th>';
            echo '<th>ASISTENCIA (%)</th>';
            echo '<th>ESTADO</th>';
            echo '</tr>';

            $i = 1;
            foreach ($inscripciones as $insc) {
                $estudiante = $insc->estudiante;
                $nombreCompleto = $estudiante ? trim(($estudiante->nombres ?? '') . ' ' . ($estudiante->apellidos ?? '')) : 'N/A';
                $ci = $estudiante ? ($estudiante->ci ?? 'N/A') : 'N/A';
                $idioma = $insc->curso ? ($insc->curso->idioma ? ($insc->curso->idioma->nombre_idioma ?? $insc->curso->idioma->nombre) : 'N/A') : 'N/A';
                $nivel = $insc->curso ? ($insc->curso->nivel ?? 'N/A') : 'N/A';
                $paralelo = $insc->paralelo ? ($insc->paralelo->nombre_paralelo ?? 'N/A') : 'N/A';
                $tutor = $estudiante ? ($estudiante->nombre_padres ?? 'N/A') : 'N/A';
                $emergencia = $estudiante ? ($estudiante->contacto_emergencia ?? $estudiante->celular ?? 'N/A') : 'N/A';

                $notasAvg = $insc->notas->count() > 0 ? round($insc->notas->avg('puntaje'), 1) : 0;
                $totalAsist = $insc->asistencias->count();
                $presentes = $insc->asistencias->where('estado', 'presente')->count();
                $asistenciaPct = $totalAsist > 0 ? round(($presentes / $totalAsist) * 100, 1) : 100;

                echo '<tr>';
                echo '<td class="num">' . $i++ . '</td>';
                echo '<td>' . htmlspecialchars(mb_strtoupper($nombreCompleto, 'UTF-8')) . '</td>';
                echo '<td>' . htmlspecialchars($ci) . '</td>';
                echo '<td>' . htmlspecialchars($idioma) . '</td>';
                echo '<td>' . htmlspecialchars($nivel) . '</td>';
                echo '<td>' . htmlspecialchars($paralelo) . '</td>';
                echo '<td>' . htmlspecialchars($tutor) . '</td>';
                echo '<td>' . htmlspecialchars($emergencia) . '</td>';
                echo '<td class="num">' . htmlspecialchars($insc->fecha_registro) . '</td>';
                echo '<td class="num"><b>' . ($notasAvg > 0 ? $notasAvg : '-') . '</b></td>';
                echo '<td class="num">' . $asistenciaPct . '%</td>';
                echo '<td class="num"><b>' . strtoupper($insc->estado) . '</b></td>';
                echo '</tr>';
            }

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
