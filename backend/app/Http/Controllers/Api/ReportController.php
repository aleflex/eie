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
        try {
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
        } catch (\Exception $e) {
            \Log::error("Error en getLanguageStatistics: " . $e->getMessage());
            return response()->json([
                'total_general' => 0,
                'estadisticas' => []
            ]);
        }
    }

    /**
     * RF 19 - HU 19: Porcentajes de ocupación de aulas y detalle de estudiantes con notas
     */
    public function getClassroomOccupancy(Request $request)
    {
        try {
            $filters = $request->only(['id_idioma', 'id_nivel', 'id_curso', 'id_paralelo', 'estado', 'fecha_desde', 'fecha_hasta']);

            $paralelos = Paralelo::with([
                'aula',
                'curso.idioma',
                'curso.nivelRel',
                'inscripciones' => function ($q) use ($filters) {
                    $q->filterMultiCriteria($filters);
                },
                'inscripciones.estudiante.user',
                'inscripciones.notas'
            ])->get();

            $ocupacion = $paralelos->map(function ($paralelo) {
                $capacidad = $paralelo->aula ? ($paralelo->aula->capacidad ?: 30) : 30;
                $inscritosCount = $paralelo->inscripciones ? $paralelo->inscripciones->count() : 0;
                $porcentaje = $capacidad > 0 ? round(($inscritosCount / $capacidad) * 100, 1) : 0;

                $estudiantesDetalle = ($paralelo->inscripciones ?? collect())->map(function ($ins) {
                    $est = $ins->estudiante ?? null;
                    $user = $est ? ($est->user ?? null) : null;
                    $notas = ($ins->relationLoaded('notas') && $ins->notas) ? $ins->notas->pluck('nota')->toArray() : [];
                    $prom = count($notas) > 0 ? round(array_sum($notas) / count($notas), 1) : null;

                    return [
                        'id_inscripcion' => $ins->id_inscripcion,
                        'id_estudiante' => $ins->id_estudiante,
                        'nombre_completo' => $user ? trim(($user->nombres ?? '') . ' ' . ($user->apellidos ?? '')) : 'Estudiante N/A',
                        'ci' => $user->ci ?? 'N/A',
                        'estado' => ucfirst($ins->estado ?? 'pendiente'),
                        'notas' => $notas,
                        'promedio' => $prom !== null ? $prom : 'Sin Notas'
                    ];
                })->values();

                return [
                    'id_paralelo' => $paralelo->id_paralelo,
                    'nombre_paralelo' => $paralelo->nombre_paralelo,
                    'curso' => $paralelo->curso ? $paralelo->curso->nombre_curso : 'N/A',
                    'aula' => $paralelo->aula ? ($paralelo->aula->nombre_aula ?: $paralelo->aula->nombre) : 'Sin Aula',
                    'capacidad' => $capacidad,
                    'inscritos' => $inscritosCount,
                    'porcentaje_ocupacion' => min($porcentaje, 100),
                    'estado_ocupacion' => $porcentaje >= 100 ? 'Lleno' : ($porcentaje >= 75 ? 'Alta' : ($porcentaje >= 40 ? 'Media' : 'Baja')),
                    'estudiantes' => $estudiantesDetalle
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
        } catch (\Exception $e) {
            \Log::error("Error en getClassroomOccupancy: " . $e->getMessage());
            return response()->json([
                'total_capacidad' => 0,
                'total_inscritos' => 0,
                'promedio_ocupacion' => 0,
                'aulas' => []
            ]);
        }
    }

    /**
     * Resumen general para KPI Dashboard
     */
    public function getDashboardSummary(Request $request)
    {
        try {
            $filters = $request->only(['id_idioma', 'id_nivel', 'id_curso', 'id_paralelo', 'estado', 'fecha_desde', 'fecha_hasta']);

            $inscripcionesQuery = Inscripcion::query()->filterMultiCriteria($filters);

            $totalInscritos = (clone $inscripcionesQuery)->count();
            $totalHabilitados = (clone $inscripcionesQuery)->where('estado', 'activo')->count();
            $totalPendientes = (clone $inscripcionesQuery)->where('estado', 'pendiente')->count();
            $totalRetirados = (clone $inscripcionesQuery)->where('estado', 'retirado')->count();

            // Promedio general de notas
            $promedioNotas = 0;
            try {
                $idsInscripciones = (clone $inscripcionesQuery)->pluck('id_inscripcion');
                if (count($idsInscripciones) > 0) {
                    $promedioNotas = DB::table('notas')
                        ->whereIn('id_inscripcion', $idsInscripciones)
                        ->avg('nota');
                }
            } catch (\Exception $ne) {
                $promedioNotas = 0;
            }

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
                'promedio_notas' => $promedioNotas ? round($promedioNotas, 1) : 0,
                'porcentaje_habilitados' => $totalInscritos > 0 ? round(($totalHabilitados / $totalInscritos) * 100, 1) : 0,
                'idioma_top' => $topLanguage ? $topLanguage->nombre_idioma : 'N/A',
                'ocupacion_promedio' => $occupancyRes['promedio_ocupacion'] ?? 0
            ]);
        } catch (\Exception $e) {
            \Log::error("Error en getDashboardSummary: " . $e->getMessage());
            return response()->json([
                'total_inscritos' => 0,
                'habilitados' => 0,
                'pendientes' => 0,
                'retirados' => 0,
                'promedio_notas' => 0,
                'porcentaje_habilitados' => 0,
                'idioma_top' => 'N/A',
                'ocupacion_promedio' => 0
            ]);
        }
    }

    /**
     * RF 20 - HU 20: Exportación de REPORTES Y ESTADÍSTICAS a Excel (.xls)
     * Usado en el módulo "Reportes & Dashboards Estadísticos"
     */
    public function exportExcel(Request $request)
    {
        $filters = $request->only(['id_idioma', 'id_nivel', 'id_curso', 'id_paralelo', 'estado', 'fecha_desde', 'fecha_hasta']);

        $summary = $this->getDashboardSummary($request)->getData(true);
        $langStats = $this->getLanguageStatistics($request)->getData(true);
        $occStats = $this->getClassroomOccupancy($request)->getData(true);
        
        $inscripciones = Inscripcion::with(['estudiante.user', 'curso.idioma', 'curso.nivelRel', 'paralelo'])
            ->filterMultiCriteria($filters)
            ->get();

        $fileName = 'Reportes_Estadisticos_EIE_' . date('Ymd_His') . '.xlsx';

        if (class_exists(\ZipArchive::class)) {
            $rows = [
                [['val' => 'ESCUELA DE IDIOMAS DEL EJÉRCITO', 'style' => 2]],
                [['val' => 'REPORTES Y ESTADÍSTICAS GENERALES DE GESTIÓN ACADÉMICA', 'style' => 2]],
                [['val' => 'FILIAL COCHABAMBA - BOLIVIA (' . date('d/m/Y') . ')', 'style' => 0]],
                [''],
                [['val' => '1. INDICADORES CLAVE DE RENDIMIENTO (KPIs)', 'style' => 2]],
                [
                    ['val' => 'Total Inscritos', 'style' => 1],
                    ['val' => 'Estudiantes Habilitados', 'style' => 1],
                    ['val' => 'Pendientes', 'style' => 1],
                    ['val' => 'Retirados', 'style' => 1],
                    ['val' => '% Habilitados', 'style' => 1],
                    ['val' => 'Idioma Mayor Demanda', 'style' => 1],
                    ['val' => 'Ocupación Promedio Aulas', 'style' => 1],
                ],
                [
                    ['val' => $summary['total_inscritos'] ?? 0, 'style' => 3],
                    ['val' => $summary['habilitados'] ?? 0, 'style' => 3],
                    ['val' => $summary['pendientes'] ?? 0, 'style' => 3],
                    ['val' => $summary['retirados'] ?? 0, 'style' => 3],
                    ['val' => ($summary['porcentaje_habilitados'] ?? 0) . '%', 'style' => 3],
                    ['val' => $summary['idioma_top'] ?? 'N/A', 'style' => 3],
                    ['val' => ($summary['ocupacion_promedio'] ?? 0) . '%', 'style' => 3],
                ],
                [''],
                [['val' => '2. DISTRIBUCIÓN DE MATRÍCULAS POR IDIOMA', 'style' => 2]],
                [
                    ['val' => 'Idioma', 'style' => 1],
                    ['val' => 'Total Estudiantes', 'style' => 1],
                    ['val' => 'Porcentaje (%)', 'style' => 1],
                ]
            ];

            foreach ($langStats['estadisticas'] ?? [] as $l) {
                $rows[] = [
                    ['val' => $l['idioma'] ?? 'N/A', 'style' => 0],
                    ['val' => $l['total_estudiantes'] ?? 0, 'style' => 0],
                    ['val' => ($l['porcentaje'] ?? 0) . '%', 'style' => 0],
                ];
            }

            $rows[] = [''];
            $rows[] = [['val' => '3. OCUPACIÓN DE AULAS Y PARALELOS', 'style' => 2]];
            $rows[] = [
                ['val' => 'Paralelo', 'style' => 1],
                ['val' => 'Curso / Nivel', 'style' => 1],
                ['val' => 'Aula Asignada', 'style' => 1],
                ['val' => 'Capacidad', 'style' => 1],
                ['val' => 'Inscritos', 'style' => 1],
                ['val' => '% Ocupación', 'style' => 1],
            ];

            foreach ($occStats['aulas'] ?? [] as $a) {
                $rows[] = [
                    ['val' => $a['nombre_paralelo'] ?? 'N/A', 'style' => 0],
                    ['val' => $a['curso'] ?? 'N/A', 'style' => 0],
                    ['val' => $a['aula'] ?? 'Sin Aula', 'style' => 0],
                    ['val' => $a['capacidad'] ?? 0, 'style' => 0],
                    ['val' => $a['inscritos'] ?? 0, 'style' => 0],
                    ['val' => ($a['porcentaje_ocupacion'] ?? 0) . '%', 'style' => 0],
                ];
            }

            $rows[] = [''];
            $rows[] = [['val' => '4. DETALLE DE MATRÍCULAS FILTRADAS', 'style' => 2]];
            $rows[] = [
                ['val' => 'Nro', 'style' => 1],
                ['val' => 'C.I.', 'style' => 1],
                ['val' => 'Apellidos y Nombres', 'style' => 1],
                ['val' => 'Idioma', 'style' => 1],
                ['val' => 'Nivel', 'style' => 1],
                ['val' => 'Paralelo', 'style' => 1],
                ['val' => 'Estado', 'style' => 1],
            ];

            $idx = 1;
            foreach ($inscripciones as $insc) {
                $est = $insc->estudiante;
                $cur = $insc->curso;
                $idm = $cur ? $cur->idioma : null;
                $par = $insc->paralelo;

                $rows[] = [
                    ['val' => $idx++, 'style' => 0],
                    ['val' => $est->ci ?? 'N/A', 'style' => 0],
                    ['val' => strtoupper(($est->apellidos ?? '') . ' ' . ($est->nombres ?? '')), 'style' => 0],
                    ['val' => $idm->nombre_idioma ?? $idm->nombre ?? 'N/A', 'style' => 0],
                    ['val' => $cur->nivel ?? 'N/A', 'style' => 0],
                    ['val' => $par->nombre_paralelo ?? $par->nombre ?? 'N/A', 'style' => 0],
                    ['val' => strtoupper($insc->estado ?? 'CONFIRMADO'), 'style' => 0],
                ];
            }

            $xlsxContent = \App\Services\SimpleXlsxWriter::create('Reportes Estadisticos', $rows);
            return response($xlsxContent, 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => "attachment; filename=\"$fileName\"",
                'Cache-Control' => 'max-age=0'
            ]);
        }

        $headers = [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$fileName\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function () use ($summary, $langStats, $occStats, $inscripciones) {
            echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
            echo '<head><meta charset="UTF-8">';
            echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Reportes Estadisticos</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
            echo '<style>';
            echo 'table { border-collapse: collapse; font-family: Arial, sans-serif; font-size: 10px; margin-bottom: 20px; }';
            echo 'th, td { border: 1px solid #cbd5e0; padding: 5px 8px; text-align: center; vertical-align: middle; }';
            echo 'th { background-color: #003B71; color: #ffffff; font-weight: bold; font-size: 9.5px; }';
            echo '.header-title { font-weight: bold; font-size: 13px; color: #003B71; border: none; text-align: center; }';
            echo '.section-title { font-weight: bold; font-size: 11px; background-color: #e2e8f0; color: #003B71; text-align: left; padding: 6px; }';
            echo '.left { text-align: left; }';
            echo '.kpi-val { font-weight: bold; font-size: 11px; color: #003B71; }';
            echo '</style>';
            echo '</head><body>';

            echo '<table>';
            echo '<tr><td colspan="7" class="header-title">ESCUELA DE IDIOMAS DEL EJÉRCITO</td></tr>';
            echo '<tr><td colspan="7" class="header-title">REPORTES Y ESTADÍSTICAS GENERALES DE GESTIÓN ACADÉMICA</td></tr>';
            echo '<tr><td colspan="7" class="header-title">FILIAL COCHABAMBA - BOLIVIA (' . date('d/m/Y') . ')</td></tr>';
            echo '<tr><td colspan="7">&nbsp;</td></tr>';

            // TABLA 1: KPIs RESUMEN
            echo '<tr><th colspan="7" class="section-title">1. INDICADORES CLAVE DE RENDIMIENTO (KPIs)</th></tr>';
            echo '<tr>';
            echo '<th>Total Inscritos</th>';
            echo '<th>Estudiantes Habilitados</th>';
            echo '<th>Pendientes</th>';
            echo '<th>Retirados</th>';
            echo '<th>% Habilitados</th>';
            echo '<th>Idioma de Mayor Demanda</th>';
            echo '<th>Ocupación Promedio de Aulas</th>';
            echo '</tr>';

            echo '<tr>';
            echo '<td class="kpi-val">' . ($summary['total_inscritos'] ?? 0) . '</td>';
            echo '<td class="kpi-val">' . ($summary['habilitados'] ?? 0) . '</td>';
            echo '<td class="kpi-val">' . ($summary['pendientes'] ?? 0) . '</td>';
            echo '<td class="kpi-val">' . ($summary['retirados'] ?? 0) . '</td>';
            echo '<td class="kpi-val">' . ($summary['porcentaje_habilitados'] ?? 0) . '%</td>';
            echo '<td class="kpi-val">' . htmlspecialchars($summary['idioma_top'] ?? 'N/A') . '</td>';
            echo '<td class="kpi-val">' . ($summary['ocupacion_promedio'] ?? 0) . '%</td>';
            echo '</tr>';
            echo '</table><br>';

            // TABLA 2: ESTADÍSTICAS POR IDIOMA
            echo '<table>';
            echo '<tr><th colspan="3" class="section-title">2. DISTRIBUCIÓN DE MATRÍCULAS POR IDIOMA</th></tr>';
            echo '<tr>';
            echo '<th style="width: 40%;">Idioma</th>';
            echo '<th style="width: 30%;">Total Estudiantes</th>';
            echo '<th style="width: 30%;">Porcentaje (%)</th>';
            echo '</tr>';

            $langList = $langStats['estadisticas'] ?? [];
            if (count($langList) > 0) {
                foreach ($langList as $l) {
                    echo '<tr>';
                    echo '<td class="left"><strong>' . htmlspecialchars($l['idioma'] ?? 'N/A') . '</strong></td>';
                    echo '<td>' . ($l['total_estudiantes'] ?? 0) . '</td>';
                    echo '<td>' . ($l['porcentaje'] ?? 0) . '%</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="3">Sin registros estadísticos por idioma</td></tr>';
            }
            echo '</table><br>';

            // TABLA 3: OCUPACIÓN DE AULAS
            echo '<table>';
            echo '<tr><th colspan="6" class="section-title">3. PORCENTAJE DE OCUPACIÓN DE AULAS Y PARALELOS</th></tr>';
            echo '<tr>';
            echo '<th>Paralelo</th>';
            echo '<th>Curso / Nivel</th>';
            echo '<th>Aula Asignada</th>';
            echo '<th>Capacidad</th>';
            echo '<th>Estudiantes Inscritos</th>';
            echo '<th>% Ocupación</th>';
            echo '</tr>';

            $aulaList = $occStats['aulas'] ?? [];
            if (count($aulaList) > 0) {
                foreach ($aulaList as $a) {
                    echo '<tr>';
                    echo '<td class="left"><strong>' . htmlspecialchars($a['nombre_paralelo'] ?? 'N/A') . '</strong></td>';
                    echo '<td class="left">' . htmlspecialchars($a['curso'] ?? 'N/A') . '</td>';
                    echo '<td>' . htmlspecialchars($a['aula'] ?? 'Sin Aula') . '</td>';
                    echo '<td>' . ($a['capacidad'] ?? 0) . '</td>';
                    echo '<td>' . ($a['inscritos'] ?? 0) . '</td>';
                    echo '<td>' . ($a['porcentaje_ocupacion'] ?? 0) . '%</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="6">Sin datos de ocupación de aulas</td></tr>';
            }
            echo '</table><br>';

            // TABLA 4: DETALLE GENERAL DE MATRÍCULAS
            echo '<table>';
            echo '<tr><th colspan="7" class="section-title">4. DETALLE DE MATRÍCULAS FILTRADAS</th></tr>';
            echo '<tr>';
            echo '<th>Nro</th>';
            echo '<th>C.I.</th>';
            echo '<th>Apellidos y Nombres</th>';
            echo '<th>Idioma</th>';
            echo '<th>Nivel</th>';
            echo '<th>Paralelo</th>';
            echo '<th>Estado</th>';
            echo '</tr>';

            $idx = 1;
            foreach ($inscripciones as $insc) {
                $est = $insc->estudiante;
                $cur = $insc->curso;
                $idm = $cur ? $cur->idioma : null;
                $par = $insc->paralelo;

                echo '<tr>';
                echo '<td>' . $idx++ . '</td>';
                echo '<td>' . htmlspecialchars($est->ci ?? 'N/A') . '</td>';
                echo '<td class="left">' . htmlspecialchars(strtoupper(($est->apellidos ?? '') . ' ' . ($est->nombres ?? ''))) . '</td>';
                echo '<td>' . htmlspecialchars($idm->nombre_idioma ?? $idm->nombre ?? 'N/A') . '</td>';
                echo '<td>' . htmlspecialchars($cur->nivel ?? 'N/A') . '</td>';
                echo '<td>' . htmlspecialchars($par->nombre_paralelo ?? $par->nombre ?? 'N/A') . '</td>';
                echo '<td>' . htmlspecialchars(strtoupper($insc->estado ?? 'CONFIRMADO')) . '</td>';
                echo '</tr>';
            }
            echo '</table>';

            echo '</body></html>';
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * EXPORTACIÓN DE RELACIÓN NOMINAL CON ASISTENCIA REAL DE ALUMNOS (MÓDULO DOCENTES) (.xls)
     */
    public function exportNominalExcel(Request $request)
    {
        $filters = $request->only(['id_idioma', 'id_nivel', 'id_curso', 'id_paralelo', 'estado', 'fecha_desde', 'fecha_hasta']);

        $inscripciones = Inscripcion::with(['estudiante.user', 'curso.idioma', 'curso.nivelRel', 'paralelo', 'notas', 'asistencias'])
            ->filterMultiCriteria($filters)
            ->get();

        $fileName = 'Asistencias_Alumnos_EIE_' . date('Ymd_His') . '.xlsx';

        if (class_exists(\ZipArchive::class)) {
            $rows = [
                [['val' => 'ESCUELA DE IDIOMAS DEL EJÉRCITO', 'style' => 2]],
                [['val' => 'RELACIÓN NOMINAL DEL PERSONAL DE ALUMNOS (REGISTRO DE ASISTENCIA)', 'style' => 2]],
                [['val' => 'FECHA DE REPORTE: ' . date('d/m/Y H:i'), 'style' => 0]],
                [''],
                [
                    ['val' => 'Nro', 'style' => 1],
                    ['val' => 'C.I.', 'style' => 1],
                    ['val' => 'Grado', 'style' => 1],
                    ['val' => 'Apellidos', 'style' => 1],
                    ['val' => 'Nombres', 'style' => 1],
                    ['val' => 'Idioma', 'style' => 1],
                    ['val' => 'Nivel', 'style' => 1],
                    ['val' => 'Paralelo', 'style' => 1],
                    ['val' => 'Total Clases', 'style' => 1],
                    ['val' => 'Presentes', 'style' => 1],
                    ['val' => 'Faltas', 'style' => 1],
                    ['val' => 'Licencias', 'style' => 1],
                    ['val' => '% Asistencia', 'style' => 1],
                    ['val' => 'Estado', 'style' => 1],
                ]
            ];

            $idx = 1;
            foreach ($inscripciones as $insc) {
                $est = $insc->estudiante;
                $cur = $insc->curso;
                $idm = $cur ? $cur->idioma : null;
                $par = $insc->paralelo;

                $totalSesiones = $insc->asistencias ? $insc->asistencias->count() : 0;
                $presentes = $insc->asistencias ? $insc->asistencias->where('estado', 'presente')->count() : 0;
                $faltas = $insc->asistencias ? $insc->asistencias->where('estado', 'falta')->count() : 0;
                $licencias = $insc->asistencias ? $insc->asistencias->where('estado', 'licencia')->count() : 0;
                $pct = $totalSesiones > 0 ? round(($presentes / $totalSesiones) * 100, 1) . '%' : '100%';

                $rows[] = [
                    ['val' => $idx++, 'style' => 0],
                    ['val' => $est->ci ?? 'N/A', 'style' => 0],
                    ['val' => $est->grado ?? 'Civil', 'style' => 0],
                    ['val' => strtoupper($est->apellidos ?? ''), 'style' => 0],
                    ['val' => strtoupper($est->nombres ?? ''), 'style' => 0],
                    ['val' => $idm->nombre_idioma ?? $idm->nombre ?? 'N/A', 'style' => 0],
                    ['val' => $cur->nivel ?? 'N/A', 'style' => 0],
                    ['val' => $par->nombre_paralelo ?? $par->nombre ?? 'N/A', 'style' => 0],
                    ['val' => $totalSesiones, 'style' => 0],
                    ['val' => $presentes, 'style' => 0],
                    ['val' => $faltas, 'style' => 0],
                    ['val' => $licencias, 'style' => 0],
                    ['val' => $pct, 'style' => 0],
                    ['val' => strtoupper($insc->estado ?? 'ACTIVO'), 'style' => 0],
                ];
            }

            $xlsxContent = \App\Services\SimpleXlsxWriter::create('Asistencias Alumnos', $rows);
            return response($xlsxContent, 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => "attachment; filename=\"$fileName\"",
                'Cache-Control' => 'max-age=0'
            ]);
        }

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
            echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Asistencias Alumnos</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
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
            echo '<tr><td colspan="31" class="header-title">RELACION NOMINAL DEL PERSONAL DE ALUMNOS (REGISTRO DE ASISTENCIA)</td></tr>';
            echo '<tr><td colspan="31" class="header-title">DEL CURSO: CGEC1108A26I FILIAL: COCHABAMBA BOOK: NIVEL 1</td></tr>';
            echo '<tr><td colspan="31" class="header-title">&nbsp;</td></tr>';

            // Column Header 1
            echo '<tr>';
            echo '<th rowspan="2">Nro</th>';
            echo '<th rowspan="2">Grado</th>';
            echo '<th colspan="2">APELLIDOS</th>';
            echo '<th rowspan="2">Nombres</th>';
            echo '<th colspan="14">ASISTENCIA (REGISTRADA EN SISTEMA)</th>';
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

                // 14 columnas de asistencia real registrada en la base de datos
                $asistenciasList = $insc->asistencias ? $insc->asistencias->sortBy('fecha')->values() : collect();
                for ($a = 0; $a < 14; $a++) {
                    if (isset($asistenciasList[$a])) {
                        $st = strtolower(trim($asistenciasList[$a]->estado));
                        $char = match($st) {
                            'presente', 'p' => 'P',
                            'ausente', 'a' => '.',
                            'justificado', 'licencia', 'l', 'j' => 'L',
                            'tardanza', 't' => 'T',
                            'sin_camara', 's' => 'S',
                            default => 'P'
                        };
                        echo '<td>' . $char . '</td>';
                    } else {
                        echo '<td>P</td>'; // Si se generó la lista activa, valor por defecto P
                    }
                }

                // 4 HW
                for ($h = 1; $h <= 4; $h++) { echo '<td></td>'; }
                // 4 EE
                for ($e = 1; $e <= 4; $e++) { echo '<td></td>'; }
                // 4 LAB
                for ($l = 1; $l <= 4; $l++) { echo '<td></td>'; }

                echo '<td></td>'; // PROM
                echo '<td></td>'; // PART
                echo '<td></td>'; // OP
                echo '<td>' . htmlspecialchars(strtoupper($insc->estado ?? 'CONFIRMADO')) . '</td>';
                echo '</tr>';
            }

            echo '<tr><td colspan="31" style="border:none;">&nbsp;</td></tr>';
            echo '<tr><td colspan="15" class="left" style="border:none; font-weight:bold;">NOMBRE DEL DOCENTE: ___________________________</td><td colspan="16" class="left" style="border:none; font-weight:bold;">OBSERVACIONES:</td></tr>';
            echo '<tr><td colspan="15" class="left" style="border:none; font-weight:bold;">NOMBRE DE EC: ___________________________</td><td colspan="16" class="left" style="border:none;">________________________________________</td></tr>';
            echo '<tr><td colspan="31" style="border:none;">&nbsp;</td></tr>';
            
            // GLOSARIO
            echo '<tr><td colspan="10" class="left" style="font-weight:bold; background-color:#ffffff;">P - ASISTIO A CLASE</td><td colspan="21" style="border:none;"></td></tr>';
            echo '<tr><td colspan="10" class="left" style="font-weight:bold; background-color:#ffffff;">. - NO ASISTIO A CLASE</td><td colspan="21" style="border:none;"></td></tr>';
            echo '<tr><td colspan="10" class="left" style="font-weight:bold; background-color:#ffffff;">L - LICENCIA</td><td colspan="21" style="border:none;"></td></tr>';
            echo '<tr><td colspan="10" class="left" style="font-weight:bold; background-color:#ffffff;">S - ASISTIO SIN CAMARA</td><td colspan="21" style="border:none;"></td></tr>';

            echo '</table></body></html>';
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * EXPORTACIÓN DE CALIFICACIONES / NOTAS DE ALUMNOS (MÓDULO DOCENTES) (.xls)
     */
    public function exportNotasExcel(Request $request)
    {
        $filters = $request->only(['id_idioma', 'id_nivel', 'id_curso', 'id_paralelo', 'estado', 'fecha_desde', 'fecha_hasta']);

        $inscripciones = Inscripcion::with(['estudiante.user', 'curso.idioma', 'curso.nivelRel', 'paralelo', 'notas'])
            ->filterMultiCriteria($filters)
            ->get();

        $fileName = 'Notas_Calificaciones_EIE_' . date('Ymd_His') . '.xlsx';

        if (class_exists(\ZipArchive::class)) {
            $rows = [
                [['val' => 'ESCUELA DE IDIOMAS DEL EJÉRCITO - FILIAL COCHABAMBA', 'style' => 2]],
                [['val' => 'PLANILLA DE CALIFICACIONES DE ALUMNOS (REGISTRO OFICIAL)', 'style' => 2]],
                [['val' => 'FECHA DE EMISIÓN: ' . date('d/m/Y H:i'), 'style' => 0]],
                [''],
                [
                    ['val' => 'Nro', 'style' => 1],
                    ['val' => 'C.I.', 'style' => 1],
                    ['val' => 'Grado', 'style' => 1],
                    ['val' => 'Apellidos y Nombres', 'style' => 1],
                    ['val' => 'Idioma', 'style' => 1],
                    ['val' => 'Nivel', 'style' => 1],
                    ['val' => 'Paralelo', 'style' => 1],
                    ['val' => 'Book 1', 'style' => 1],
                    ['val' => 'Book 2', 'style' => 1],
                    ['val' => 'Book 3', 'style' => 1],
                    ['val' => 'Book 4', 'style' => 1],
                    ['val' => 'Examen Nivel', 'style' => 1],
                    ['val' => 'Promedio Final', 'style' => 1],
                    ['val' => 'Estado', 'style' => 1],
                ]
            ];

            $idx = 1;
            foreach ($inscripciones as $insc) {
                $est = $insc->estudiante;
                $cur = $insc->curso;
                $idm = $cur ? $cur->idioma : null;
                $par = $insc->paralelo;

                $b1 = '0'; $b2 = '0'; $b3 = '0'; $b4 = '0'; $ex = '0'; $pf = '0';
                if ($insc->notas && $insc->notas->count() > 0) {
                    $n = $insc->notas->first();
                    $b1 = (string)($n->nota_parcial_1 ?? '0');
                    $b2 = (string)($n->nota_parcial_2 ?? '0');
                    $b3 = (string)($n->nota_parcial_3 ?? '0');
                    $b4 = (string)($n->nota_parcial_4 ?? '0');
                    $ex = (string)($n->examen_final ?? '0');
                    $pf = (string)($n->promedio_final ?? '0');
                }

                $promNum = floatval($pf);
                $estadoAprob = $promNum >= 70 ? 'APROBADO' : ($promNum > 0 ? 'REPROBADO' : 'EN CURSO');

                $rows[] = [
                    ['val' => $idx++, 'style' => 0],
                    ['val' => $est->ci ?? 'N/A', 'style' => 0],
                    ['val' => $est->grado ?? 'Civil', 'style' => 0],
                    ['val' => strtoupper(($est->apellidos ?? '') . ' ' . ($est->nombres ?? '')), 'style' => 0],
                    ['val' => $idm->nombre_idioma ?? $idm->nombre ?? 'N/A', 'style' => 0],
                    ['val' => $cur->nivel ?? 'N/A', 'style' => 0],
                    ['val' => $par->nombre_paralelo ?? $par->nombre ?? 'N/A', 'style' => 0],
                    ['val' => $b1, 'style' => 0],
                    ['val' => $b2, 'style' => 0],
                    ['val' => $b3, 'style' => 0],
                    ['val' => $b4, 'style' => 0],
                    ['val' => $ex, 'style' => 0],
                    ['val' => $pf, 'style' => 3],
                    ['val' => $estadoAprob, 'style' => 0],
                ];
            }

            $xlsxContent = \App\Services\SimpleXlsxWriter::create('Notas Alumnos', $rows);
            return response($xlsxContent, 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => "attachment; filename=\"$fileName\"",
                'Cache-Control' => 'max-age=0'
            ]);
        }

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
            echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Notas Alumnos</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
            echo '<style>';
            echo 'table { border-collapse: collapse; font-family: Arial, sans-serif; font-size: 10px; }';
            echo 'th, td { border: 1px solid #000; padding: 4px 6px; text-align: center; vertical-align: middle; }';
            echo 'th { background-color: #003B71; color: #ffffff; font-weight: bold; font-size: 9.5px; }';
            echo '.header-title { font-weight: bold; font-size: 12px; color: #003B71; border: none; text-align: center; }';
            echo '.left { text-align: left; }';
            echo '</style>';
            echo '</head><body>';

            echo '<table>';
            echo '<tr><td colspan="13" class="header-title">ESCUELA DE IDIOMAS DEL EJÉRCITO - COCHABAMBA</td></tr>';
            echo '<tr><td colspan="13" class="header-title">PLANILLA DE CALIFICACIONES DE ALUMNOS REGISTRADAS EN SISTEMA</td></tr>';
            echo '<tr><td colspan="13">&nbsp;</td></tr>';

            echo '<tr>';
            echo '<th>Nro</th>';
            echo '<th>C.I.</th>';
            echo '<th>Grado</th>';
            echo '<th>Apellidos y Nombres</th>';
            echo '<th>Book 1</th>';
            echo '<th>Book 2</th>';
            echo '<th>Book 3</th>';
            echo '<th>Book 4</th>';
            echo '<th>Book 5</th>';
            echo '<th>Book 6</th>';
            echo '<th>Examen Nivel</th>';
            echo '<th>Promedio General</th>';
            echo '<th>Estado</th>';
            echo '</tr>';

            $i = 1;
            foreach ($inscripciones as $insc) {
                $est = $insc->estudiante;
                $notasCollection = $insc->notas ?: collect();
                
                $notasLibros = [1 => 0.00, 2 => 0.00, 3 => 0.00, 4 => 0.00, 5 => 0.00, 6 => 0.00];
                $examenNivel = 0.00;

                foreach ($notasCollection as $n) {
                    $val = (float)($n->nota ?? $n->puntaje ?? 0);
                    $periodo = strtolower(trim($n->periodo ?? $n->descripcion ?? ''));
                    if (str_contains($periodo, 'examen') || str_contains($periodo, 'nivel')) {
                        $examenNivel = $val;
                    } elseif (preg_match('/(\d+)/', $periodo, $m)) {
                        $num = (int)$m[1];
                        if ($num >= 1 && $num <= 6) $notasLibros[$num] = $val;
                    }
                }
                
                if (array_sum($notasLibros) == 0 && $notasCollection->count() > 0) {
                    $idx = 1;
                    foreach ($notasCollection as $n) {
                        if ($idx <= 6) {
                            $notasLibros[$idx] = (float)($n->nota ?? $n->puntaje ?? 0);
                            $idx++;
                        }
                    }
                }

                $validas = array_filter($notasLibros, fn($v) => $v > 0);
                $promLibros = count($validas) > 0 ? array_sum($validas) / count($validas) : 0;
                $promGral = round(($promLibros * 0.8) + ($examenNivel * 0.2), 2);
                if ($promGral == 0) $promGral = round($promLibros, 2);

                echo '<tr>';
                echo '<td>' . $i++ . '</td>';
                echo '<td>' . htmlspecialchars($est->ci ?? 'N/A') . '</td>';
                echo '<td>' . htmlspecialchars(strtoupper($est->grado_academico ?? 'SR')) . '</td>';
                echo '<td class="left">' . htmlspecialchars(strtoupper(($est->apellidos ?? '') . ' ' . ($est->nombres ?? ''))) . '</td>';
                
                for ($b = 1; $b <= 6; $b++) {
                    $nVal = $notasLibros[$b];
                    echo '<td>' . ($nVal > 0 ? number_format($nVal, 2, ',', '.') : '0.00') . '</td>';
                }

                echo '<td>' . ($examenNivel > 0 ? number_format($examenNivel, 2, ',', '.') : '0.00') . '</td>';
                echo '<td><strong>' . ($promGral > 0 ? number_format($promGral, 2, ',', '.') : '0.00') . '</strong></td>';
                echo '<td>' . htmlspecialchars(strtoupper($insc->estado ?? 'CONFIRMADO')) . '</td>';
                echo '</tr>';
            }

            echo '</table></body></html>';
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * RF 20 - HU 20: Exportación a PDF de reportes estadísticos
     */
    public function exportPdf(Request $request)
    {
        try {
            $filters = $request->only(['id_idioma', 'id_nivel', 'id_curso', 'id_paralelo', 'estado', 'fecha_desde', 'fecha_hasta']);

            $summary = $this->getDashboardSummary($request)->getData(true);
            $langStats = $this->getLanguageStatistics($request)->getData(true);
            $occStats = $this->getClassroomOccupancy($request)->getData(true);
            $inscripciones = Inscripcion::with(['estudiante.user', 'curso.idioma', 'curso.nivelRel', 'paralelo'])
                ->filterMultiCriteria($filters)
                ->get();

            $pdf = Pdf::loadView('pdf.reportes', [
                'summary' => $summary,
                'langStats' => $langStats,
                'occStats' => $occStats,
                'inscripciones' => $inscripciones,
                'fecha' => date('d/m/Y')
            ]);

            $pdf->setPaper('letter', 'portrait');

            return $pdf->download('Reporte_Estadistico_EIE_' . date('Ymd') . '.pdf');
        } catch (\Throwable $e) {
            \Log::error('Error exportando PDF de reportes: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Error al generar el reporte en PDF: ' . $e->getMessage()], 500);
        }
    }

    /**
     * EXPORTACIÓN DE NÓMINA EN PDF PARA MÓDULO DOCENTES CON FORMATO INSTITUCIONAL MILITAR
     */
    public function exportDocentePdf(Request $request)
    {
        $idParalelo = $request->input('id_paralelo');
        $tipo = $request->input('tipo', 'lista'); // 'lista', 'notas', 'asistencia'
        $filters = $request->only(['id_idioma', 'id_nivel', 'id_curso', 'id_paralelo', 'estado']);

        $paralelo = Paralelo::with(['curso.idioma', 'aula', 'docente.user'])->find($idParalelo);
        if (!$paralelo && !empty($filters['id_paralelo'])) {
            $paralelo = Paralelo::with(['curso.idioma', 'aula', 'docente.user'])->find($filters['id_paralelo']);
        }

        $inscripciones = Inscripcion::with(['estudiante.user', 'curso.idioma', 'paralelo', 'notas', 'asistencias'])
            ->when($idParalelo, function($q) use ($idParalelo) {
                $q->where('id_paralelo', $idParalelo);
            })
            ->filterMultiCriteria($filters)
            ->get();

        $curso = $paralelo ? $paralelo->curso : ($inscripciones->first() ? $inscripciones->first()->curso : null);
        $docente = $paralelo ? $paralelo->docente : null;
        $nombreParalelo = $paralelo ? ($paralelo->nombre_paralelo ?: $paralelo->nombre) : 'Paralelo';

        if ($tipo === 'notas') {
            $pdf = Pdf::loadView('pdf.notas_docente', [
                'paralelo' => $paralelo ?: (object)['nombre_paralelo' => 'A'],
                'curso' => $curso ?: (object)['nivel' => 'NIVEL I'],
                'docente' => $docente,
                'inscripciones' => $inscripciones,
                'fecha' => date('d/m/Y')
            ]);
            $pdf->setPaper('letter', 'portrait');
            return $pdf->download('Planilla_Calificaciones_' . $nombreParalelo . '_' . date('Ymd') . '.pdf');
        }

        if ($tipo === 'asistencia') {
            $pdf = Pdf::loadView('pdf.asistencias_docente', [
                'paralelo' => $paralelo ?: (object)['nombre_paralelo' => 'A'],
                'curso' => $curso ?: (object)['nivel' => 'NIVEL I'],
                'docente' => $docente,
                'inscripciones' => $inscripciones,
                'fecha' => date('d/m/Y')
            ]);
            $pdf->setPaper('letter', 'portrait');
            return $pdf->download('Planilla_Asistencias_' . $nombreParalelo . '_' . date('Ymd') . '.pdf');
        }

        $pdf = Pdf::loadView('pdf.lista_docente', [
            'paralelo' => $paralelo ?: (object)['nombre_paralelo' => 'A'],
            'curso' => $curso ?: (object)['nivel' => 'NIVEL I'],
            'docente' => $docente,
            'inscripciones' => $inscripciones,
            'fecha' => date('d/m/Y')
        ]);

        $pdf->setPaper('letter', 'portrait');
        return $pdf->download('Nomina_Alumnos_' . $nombreParalelo . '_' . date('Ymd') . '.pdf');
    }

    public function exportNotasPdf(Request $request)
    {
        $request->merge(['tipo' => 'notas']);
        return $this->exportDocentePdf($request);
    }

    public function exportAsistenciasPdf(Request $request)
    {
        $request->merge(['tipo' => 'asistencia']);
        return $this->exportDocentePdf($request);
    }
}
