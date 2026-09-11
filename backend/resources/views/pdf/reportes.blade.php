<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe Estadístico y Resumen Ejecutivo EIE</title>
    <style>
        @page {
            margin: 0.8cm 1.2cm;
        }
        body {
            font-family: 'Courier', 'Times New Roman', 'Arial', sans-serif;
            font-size: 9.5px;
            color: #000000;
            background-color: #ffffff;
            line-height: 1.3;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .header-left {
            font-weight: bold;
            font-size: 9.5px;
            text-align: center;
            line-height: 1.2;
            color: #000000;
        }
        .header-right {
            text-align: right;
            font-weight: bold;
            font-size: 9.5px;
            vertical-align: top;
            color: #000000;
            line-height: 1.2;
        }
        .title {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            color: #000000;
            margin: 14px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .meta-box {
            border: 1px solid #000000;
            padding: 6px 10px;
            margin-bottom: 14px;
            font-size: 9.5px;
            line-height: 1.4;
        }
        .section-title {
            font-size: 10px;
            font-weight: bold;
            color: #000000;
            text-transform: uppercase;
            margin-top: 14px;
            margin-bottom: 5px;
            border-bottom: 1px solid #000000;
            padding-bottom: 2px;
        }
        .table-list {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .table-list th, .table-list td {
            border: 1px solid #000000;
            padding: 4px 6px;
            text-align: center;
            font-size: 8.5px;
        }
        .table-list th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .left {
            text-align: left !important;
        }
        .signatures-table {
            width: 100%;
            margin-top: 35px;
            border-collapse: collapse;
            page-break-inside: avoid;
        }
        .signatures-table td {
            text-align: center;
            vertical-align: bottom;
            font-size: 8.5px;
            font-weight: bold;
            color: #000000;
            padding: 0 10px;
        }
        .sig-line {
            border-top: 1px solid #000000;
            padding-top: 4px;
        }
        .qr-section {
            margin-top: 15px;
            float: left;
            border: 1px solid #000000;
            padding: 4px 8px;
            text-align: center;
            font-size: 7.5px;
            font-weight: bold;
            background-color: #ffffff;
            color: #000000;
        }
        .footer-note {
            margin-top: 20px;
            font-size: 8px;
            color: #000000;
            text-align: center;
            clear: both;
        }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td class="header-left" style="width: 55%;">
                FACULTAD DE CIENCIAS Y ARTES MILITARES TERRESTRES<br>
                "GRAL. DIV. JOSÉ MIGUEL LANZA"<br>
                ESCUELA DE IDIOMAS DEL EJÉRCITO<br>
                <u>BOLIVIA</u>
            </td>
            <td class="header-right" style="width: 45%;">
                SECCIÓN ACADÉMICA<br>
                SubSecc. Evaluación y Estadística
            </td>
        </tr>
    </table>

    <div class="title">INFORME ESTADÍSTICO Y RESUMEN GENERAL EJECUTIVO</div>

    <div class="meta-box">
        <strong>TIPO DE INFORME:</strong> CONSOLIDADO GENERAL ESTADÍSTICO &nbsp;&nbsp;&nbsp;&nbsp;
        <strong>GESTIÓN:</strong> {{ date('Y') }}<br>
        <strong>TOTAL MATRÍCULAS:</strong> {{ $summary['total_inscritos'] ?? count($inscripciones) }} &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <strong>FECHA:</strong> {{ $fecha ?? date('d/m/Y') }}
    </div>

    <!-- 1. INDICADORES CLAVE -->
    <div class="section-title">1. Indicadores Clave de Gestión Académica (KPIs)</div>
    <table class="table-list">
        <thead>
            <tr>
                <th>Total Inscritos</th>
                <th>Habilitados</th>
                <th>Pendientes</th>
                <th>Retirados</th>
                <th>% Habilitados</th>
                <th>Idioma Top</th>
                <th>Ocupación Aulas</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>{{ $summary['total_inscritos'] ?? 0 }}</strong></td>
                <td>{{ $summary['habilitados'] ?? 0 }}</td>
                <td>{{ $summary['pendientes'] ?? 0 }}</td>
                <td>{{ $summary['retirados'] ?? 0 }}</td>
                <td><strong>{{ $summary['porcentaje_habilitados'] ?? 0 }}%</strong></td>
                <td>{{ strtoupper($summary['idioma_top'] ?? 'INGLÉS') }}</td>
                <td><strong>{{ $summary['ocupacion_promedio'] ?? 0 }}%</strong></td>
            </tr>
        </tbody>
    </table>

    <!-- 2. DISTRIBUCIÓN POR IDIOMA -->
    <div class="section-title">2. Distribución de Matrículas por Idioma</div>
    <table class="table-list">
        <thead>
            <tr>
                <th class="left" style="width: 45%;">Idioma</th>
                <th style="width: 25%;">Total Estudiantes</th>
                <th style="width: 30%;">Porcentaje de Participación</th>
            </tr>
        </thead>
        <tbody>
            @php
                $estadisticasIdioma = $langStats['estadisticas'] ?? [];
            @endphp
            @forelse($estadisticasIdioma as $item)
                <tr>
                    <td class="left"><strong>{{ strtoupper($item['idioma'] ?? 'N/A') }}</strong></td>
                    <td>{{ $item['total_estudiantes'] ?? 0 }}</td>
                    <td>{{ $item['porcentaje'] ?? 0 }}%</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">No se registraron datos de idiomas para los filtros actuales.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- 3. OCUPACIÓN DE AULAS -->
    <div class="section-title">3. Porcentaje de Ocupación por Aula, Paralelo y Estado de Estudiantes</div>
    <table class="table-list">
        <thead>
            <tr>
                <th style="width: 12%;">Paralelo</th>
                <th class="left" style="width: 28%;">Curso / Nivel</th>
                <th style="width: 16%;">Aula Asignada</th>
                <th style="width: 8%;">Capacidad</th>
                <th style="width: 7%;">Inscritos</th>
                <th style="width: 17%;">Estudiantes (Act/Pend/Baja)</th>
                <th style="width: 6%;">% Ocup.</th>
                <th style="width: 12%;">Nivel Ocupación</th>
            </tr>
        </thead>
        <tbody>
            @php
                $aulasList = $occStats['aulas'] ?? [];
            @endphp
            @forelse($aulasList as $aula)
                <tr>
                    <td><strong>{{ strtoupper($aula['nombre_paralelo'] ?? 'A') }}</strong></td>
                    <td class="left">{{ strtoupper($aula['curso'] ?? 'N/A') }}</td>
                    <td>{{ strtoupper($aula['aula'] ?? 'Sin Aula') }}</td>
                    <td>{{ $aula['capacidad'] ?? 0 }}</td>
                    <td><strong>{{ $aula['inscritos'] ?? 0 }}</strong></td>
                    <td style="font-size: 7.5px;">
                        <span style="color: #15803d; font-weight: bold;">{{ $aula['activos_count'] ?? 0 }} Act</span> / 
                        <span style="color: #b45309; font-weight: bold;">{{ $aula['pendientes_count'] ?? 0 }} Pend</span> / 
                        <span style="color: #b91c1c; font-weight: bold;">{{ $aula['bajas_count'] ?? 0 }} Baja</span>
                    </td>
                    <td><strong>{{ $aula['porcentaje_ocupacion'] ?? 0 }}%</strong></td>
                    <td>{{ $aula['estado_ocupacion'] ?? 'Ocupación Baja' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">No se registraron datos de ocupación de aulas.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- 4. DETALLE DE MATRÍCULAS Y CALIFICACIONES (KARDEX ACADÉMICO) -->
    <div class="section-title">4. Detalle de Matrículas y Calificaciones Académicas (Kardex General - {{ count($inscripciones) }} Registros)</div>
    <table class="table-list">
        <thead>
            <tr>
                <th style="width: 3%;">Nro</th>
                <th style="width: 8%;">C.I.</th>
                <th class="left" style="width: 22%;">Apellidos y Nombres</th>
                <th style="width: 8%;">Idioma</th>
                <th style="width: 12%;">Nivel</th>
                <th style="width: 8%;">Paralelo</th>
                <th style="width: 5%;">Book 1</th>
                <th style="width: 5%;">Book 2</th>
                <th style="width: 5%;">Book 3</th>
                <th style="width: 5%;">Book 4</th>
                <th style="width: 6%;">Ex. Final</th>
                <th style="width: 6%;">Promedio</th>
                <th style="width: 7%;">Rendimiento</th>
            </tr>
        </thead>
        <tbody>
            @forelse($inscripciones as $idx => $insc)
                @php
                    $est = $insc->estudiante;
                    $user = $est ? ($est->user ?? null) : null;
                    $nombre = $user ? trim(($user->apellidos ?? '') . ' ' . ($user->nombres ?? '')) : ($est ? trim(($est->apellidos ?? '') . ' ' . ($est->nombres ?? '')) : 'N/A');
                    $ci = $user->ci ?? ($est->ci ?? 'N/A');
                    $idioma = $insc->curso && $insc->curso->idioma ? ($insc->curso->idioma->nombre_idioma ?? $insc->curso->idioma->nombre ?? 'N/A') : 'N/A';
                    $nivel = $insc->curso ? ($insc->curso->nivelRel->nombre_nivel ?? $insc->curso->nivel ?? 'N/A') : 'N/A';
                    $paralelo = $insc->paralelo ? ($insc->paralelo->nombre_paralelo ?? $insc->paralelo->nombre ?? 'Sin Paralelo') : 'Sin Paralelo';
                    $estado = strtoupper($insc->estado ?? 'ACTIVO');

                    $b1 = '-'; $b2 = '-'; $b3 = '-'; $b4 = '-'; $ex = '-';
                    $notasCol = $insc->notas ?: collect();
                    foreach ($notasCol as $nt) {
                        $p = strtolower(trim($nt->periodo ?? $nt->descripcion ?? ''));
                        $val = (string)$nt->nota;
                        if (str_contains($p, 'examen') || str_contains($p, 'final') || str_contains($p, 'nivel')) {
                            $ex = $val;
                        } elseif (preg_match('/(?:book|parcial|libro|unidad)\s*1\b/i', $p) || $p === 'b1') {
                            $b1 = $val;
                        } elseif (preg_match('/(?:book|parcial|libro|unidad)\s*2\b/i', $p) || $p === 'b2') {
                            $b2 = $val;
                        } elseif (preg_match('/(?:book|parcial|libro|unidad)\s*3\b/i', $p) || $p === 'b3') {
                            $b3 = $val;
                        } elseif (preg_match('/(?:book|parcial|libro|unidad)\s*4\b/i', $p) || $p === 'b4') {
                            $b4 = $val;
                        }
                    }
                    $avg = $notasCol->count() > 0 ? round($notasCol->avg('nota'), 1) : null;
                    $promStr = $avg !== null ? (string)$avg : '-';

                    if (in_array($estado, ['BAJA', 'RETIRADO'])) {
                        $rendimiento = 'DE BAJA';
                        $rendColor = '#dc2626';
                    } elseif ($avg !== null) {
                        if ($avg >= 90) {
                            $rendimiento = 'EXCELENTE';
                            $rendColor = '#2563eb';
                        } elseif ($avg >= 71) {
                            if ($avg < 80) {
                                $rendimiento = 'EN RIESGO';
                                $rendColor = '#d97706';
                            } else {
                                $rendimiento = 'APROBADO';
                                $rendColor = '#16a34a';
                            }
                        } else {
                            $rendimiento = 'REPROBADO';
                            $rendColor = '#dc2626';
                        }
                    } else {
                        $rendimiento = ($estado === 'PENDIENTE') ? 'PENDIENTE' : 'SIN NOTAS';
                        $rendColor = '#6b7280';
                    }
                @endphp
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ $ci }}</td>
                    <td class="left"><strong>{{ mb_strtoupper($nombre, 'UTF-8') }}</strong></td>
                    <td>{{ strtoupper($idioma) }}</td>
                    <td>{{ strtoupper($nivel) }}</td>
                    <td>{{ strtoupper($paralelo) }}</td>
                    <td>{{ $b1 }}</td>
                    <td>{{ $b2 }}</td>
                    <td>{{ $b3 }}</td>
                    <td>{{ $b4 }}</td>
                    <td>{{ $ex }}</td>
                    <td><strong>{{ $promStr }}</strong></td>
                    <td style="color: {{ $rendColor }}; font-weight: bold;">{{ $rendimiento }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="13">No se encontraron matrículas con los filtros aplicados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- 5. FIRMAS -->
    <table class="signatures-table">
        <tr>
            <td style="width: 45%;">
                <div class="sig-line">
                    JEFE DE ESTUDIOS / SECCIÓN ACADÉMICA<br>
                    ESCUELA DE IDIOMAS DEL EJÉRCITO
                </div>
            </td>
            <td style="width: 10%;"></td>
            <td style="width: 45%;">
                <div class="sig-line">
                    SUBSECCIÓN EVALUACIÓN Y ESTADÍSTICA<br>
                    ESCUELA DE IDIOMAS DEL EJÉRCITO
                </div>
            </td>
        </tr>
    </table>

    <div class="qr-section">
        CÓDIGO DE VERIFICACIÓN<br>
        EIE-ESTADISTICA-GRAL-{{ date('Y') }}<br>
        FECHA: {{ $fecha ?? date('d/m/Y') }}
    </div>

    <div class="footer-note">
        Documento Oficial emitido por el Sistema de Gestión Académica EIE. Filial Cochabamba, Bolivia.
    </div>

</body>
</html>
