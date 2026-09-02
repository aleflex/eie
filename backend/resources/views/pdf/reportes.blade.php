<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe Estadístico y Resumen Ejecutivo EIE</title>
    <style>
        @page {
            margin: 1.2cm 1.5cm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 9px;
            color: #1a1f26;
            background-color: #ffffff;
            line-height: 1.3;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #003B71;
            padding-bottom: 6px;
            margin-bottom: 12px;
        }
        .header-left {
            font-weight: bold;
            font-size: 8.5px;
            text-align: left;
            line-height: 1.2;
            color: #003B71;
        }
        .header-right {
            text-align: right;
            font-size: 8.5px;
            vertical-align: top;
            color: #4a5568;
            line-height: 1.2;
        }
        .title {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            color: #003B71;
            margin: 8px 0 3px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .subtitle {
            text-align: center;
            font-size: 9.5px;
            color: #4a5568;
            margin-bottom: 12px;
        }
        .section-title {
            font-size: 9.5px;
            font-weight: bold;
            color: #003B71;
            background-color: #e8f0fe;
            border-left: 3px solid #003B71;
            padding: 4px 8px;
            margin: 10px 0 6px 0;
            text-transform: uppercase;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .data-table th {
            background-color: #003B71;
            color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8px;
            padding: 4px 5px;
            border: 1px solid #002d57;
            text-align: center;
        }
        .data-table td {
            padding: 4px 5px;
            border: 1px solid #cbd5e0;
            font-size: 8px;
            text-align: center;
        }
        .data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .left {
            text-align: left !important;
        }
        .bold {
            font-weight: bold;
        }
        .kpi-number {
            font-size: 10.5px;
            font-weight: bold;
            color: #003B71;
        }
        .badge {
            display: inline-block;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-activo, .badge-confirmado { background-color: #def7ec; color: #03543f; }
        .badge-pendiente { background-color: #fdf6b2; color: #723b7a; }
        .badge-retirado { background-color: #fde8e8; color: #9b1c1c; }
        .badge-finalizado { background-color: #e1effe; color: #1e429f; }
        .signatures {
            margin-top: 25px;
            width: 100%;
            page-break-inside: avoid;
        }
        .signature-box {
            width: 45%;
            float: left;
            text-align: center;
            border-top: 1px solid #718096;
            padding-top: 4px;
            font-size: 8px;
            font-weight: bold;
            color: #2d3748;
        }
        .signature-box-right {
            float: right;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 7.5px;
            color: #a0aec0;
            border-top: 1px solid #e2e8f0;
            padding-top: 3px;
        }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td class="header-left" style="width: 60%;">
                FACULTAD DE CIENCIAS Y ARTES MILITARES TERRESTRES<br>
                "GRAL. DIV. JOSÉ MIGUEL LANZA"<br>
                ESCUELA DE IDIOMAS DEL EJÉRCITO<br>
                <u>COCHABAMBA - BOLIVIA</u>
            </td>
            <td class="header-right" style="width: 40%;">
                <strong>SECCIÓN ACADÉMICA</strong><br>
                SubSección Evaluación y Estadística<br>
                <strong>Fecha de Emisión:</strong> {{ $fecha }}
            </td>
        </tr>
    </table>

    <div class="title">INFORME ESTADÍSTICO Y RESUMEN EJECUTIVO</div>
    <div class="subtitle">SISTEMA INTEGRAL DE GESTIÓN Y CONTROL ACADÉMICO</div>

    <!-- 1. RESUMEN DE KPIs -->
    <div class="section-title">1. Indicadores Clave de Gestión Académica (KPIs)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Total Inscritos</th>
                <th>Estudiantes Habilitados</th>
                <th>Pendientes</th>
                <th>Retirados</th>
                <th>% Habilitados</th>
                <th>Idioma Top</th>
                <th>Ocupación Prom. Aulas</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="kpi-number">{{ $summary['total_inscritos'] ?? 0 }}</td>
                <td class="kpi-number">{{ $summary['habilitados'] ?? 0 }}</td>
                <td class="kpi-number">{{ $summary['pendientes'] ?? 0 }}</td>
                <td class="kpi-number" style="color: #9b1c1c;">{{ $summary['retirados'] ?? 0 }}</td>
                <td class="kpi-number" style="color: #03543f;">{{ $summary['porcentaje_habilitados'] ?? 0 }}%</td>
                <td class="bold">{{ $summary['idioma_top'] ?? 'N/A' }}</td>
                <td class="bold">{{ $summary['ocupacion_promedio'] ?? 0 }}%</td>
            </tr>
        </tbody>
    </table>

    <!-- 2. DISTRIBUCIÓN POR IDIOMA -->
    <div class="section-title">2. Distribución de Matrículas por Idioma</div>
    <table class="data-table">
        <thead>
            <tr>
                <th class="left" style="width: 40%;">Idioma</th>
                <th style="width: 30%;">Total Estudiantes</th>
                <th style="width: 30%;">Porcentaje de Participación</th>
            </tr>
        </thead>
        <tbody>
            @php
                $estadisticasIdioma = $langStats['estadisticas'] ?? [];
            @endphp
            @forelse($estadisticasIdioma as $item)
                <tr>
                    <td class="left bold">{{ $item['idioma'] ?? 'N/A' }}</td>
                    <td>{{ $item['total_estudiantes'] ?? 0 }}</td>
                    <td>{{ $item['porcentaje'] ?? 0 }}%</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="color: #718096; padding: 6px;">No se registraron datos de idiomas para los filtros actuales.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- 3. OCUPACIÓN DE AULAS Y PARALELOS -->
    <div class="section-title">3. Porcentaje de Ocupación por Aula y Paralelo</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 15%;">Paralelo</th>
                <th class="left" style="width: 35%;">Curso / Nivel</th>
                <th style="width: 20%;">Aula Asignada</th>
                <th style="width: 10%;">Capacidad</th>
                <th style="width: 10%;">Inscritos</th>
                <th style="width: 10%;">% Ocupación</th>
            </tr>
        </thead>
        <tbody>
            @php
                $aulasList = $occStats['aulas'] ?? [];
            @endphp
            @forelse($aulasList as $aula)
                <tr>
                    <td class="bold">{{ $aula['nombre_paralelo'] ?? 'N/A' }}</td>
                    <td class="left">{{ $aula['curso'] ?? 'N/A' }}</td>
                    <td>{{ $aula['aula'] ?? 'Sin Aula' }}</td>
                    <td>{{ $aula['capacidad'] ?? 0 }}</td>
                    <td class="bold">{{ $aula['inscritos'] ?? 0 }}</td>
                    <td class="bold" style="color: {{ ($aula['porcentaje_ocupacion'] ?? 0) >= 90 ? '#9b1c1c' : '#03543f' }};">
                        {{ $aula['porcentaje_ocupacion'] ?? 0 }}%
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="color: #718096; padding: 6px;">No se registraron datos de ocupación de aulas.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- 4. DETALLE DE MATRÍCULAS -->
    <div class="section-title">4. Detalle de Estudiantes Matriculados ({{ count($inscripciones) }} Registros)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">Nº</th>
                <th style="width: 12%;">C.I.</th>
                <th class="left" style="width: 30%;">Estudiante</th>
                <th style="width: 18%;">Idioma</th>
                <th style="width: 14%;">Nivel</th>
                <th style="width: 10%;">Paralelo</th>
                <th style="width: 12%;">Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($inscripciones as $idx => $insc)
                @php
                    $est = $insc->estudiante;
                    $nombre = $est ? trim(($est->nombres ?? '') . ' ' . ($est->apellidos ?? '')) : ($est->user->name ?? 'N/A');
                    $ci = $est->ci ?? 'N/A';
                    $idioma = $insc->curso && $insc->curso->idioma ? ($insc->curso->idioma->nombre_idioma ?? $insc->curso->idioma->nombre ?? 'N/A') : 'N/A';
                    $nivel = $insc->curso->nivel ?? 'N/A';
                    $paralelo = $insc->paralelo ? ($insc->paralelo->nombre_paralelo ?? $insc->paralelo->nombre ?? 'N/A') : 'N/A';
                    $estado = strtolower($insc->estado ?? 'activo');
                @endphp
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ $ci }}</td>
                    <td class="left bold">{{ mb_strtoupper($nombre, 'UTF-8') }}</td>
                    <td>{{ $idioma }}</td>
                    <td>{{ $nivel }}</td>
                    <td>{{ $paralelo }}</td>
                    <td>
                        <span class="badge badge-{{ $estado }}">{{ strtoupper($estado) }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="color: #718096; padding: 8px;">No se encontraron matrículas con los filtros aplicados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- 5. FIRMAS OFICIALES -->
    <div class="signatures">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 45%; text-align: center; vertical-align: top;">
                    <div style="border-top: 1px solid #718096; padding-top: 4px; font-weight: bold; font-size: 8px;">
                        JEFE DE ESTUDIOS / DIRECCIÓN ACADÉMICA<br>
                        ESCUELA DE IDIOMAS DEL EJÉRCITO
                    </div>
                </td>
                <td style="width: 10%;"></td>
                <td style="width: 45%; text-align: center; vertical-align: top;">
                    <div style="border-top: 1px solid #718096; padding-top: 4px; font-weight: bold; font-size: 8px;">
                        SUBSECCIÓN EVALUACIÓN Y ESTADÍSTICA<br>
                        FIRMA Y SELLO OFICIAL
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Documento oficial generado por el Sistema de Gestión Académica EIE - Escuela de Idiomas del Ejército, Cochabamba - Bolivia
    </div>

</body>
</html>
