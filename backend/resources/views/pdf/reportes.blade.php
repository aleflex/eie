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
        .footer-note {
            margin-top: 25px;
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
                <td>{{ mb_convert_case($summary['idioma_top'] ?? 'Inglés', MB_CASE_TITLE, 'UTF-8') }}</td>
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
                    <td class="left"><strong>{{ mb_convert_case($item['idioma'] ?? 'N/A', MB_CASE_TITLE, 'UTF-8') }}</strong></td>
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
                <th style="width: 6%;">Estado</th>
            </tr>
        </thead>
        <tbody>
            @php
                $aulasList = $occStats['aulas'] ?? [];
            @endphp
            @forelse($aulasList as $aula)
                <tr>
                    <td><strong>{{ mb_convert_case($aula['nombre_paralelo'] ?? 'A', MB_CASE_TITLE, 'UTF-8') }}</strong></td>
                    <td class="left">{{ mb_convert_case($aula['curso'] ?? 'N/A', MB_CASE_TITLE, 'UTF-8') }}</td>
                    <td>{{ mb_convert_case($aula['aula'] ?? 'Sin Aula', MB_CASE_TITLE, 'UTF-8') }}</td>
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

    <!-- 4. DETALLE DE MATRÍCULAS (KARDEX GENERAL) -->
    <div class="section-title">4. Detalle de Matrículas (Kardex General - {{ count($inscripciones) }} Registros)</div>
    <table class="table-list">
        <thead>
            <tr>
                <th style="width: 4%;">Nro</th>
                <th style="width: 14%;">C.I.</th>
                <th class="left" style="width: 32%;">Apellidos y Nombres</th>
                <th style="width: 14%;">Idioma</th>
                <th style="width: 14%;">Nivel</th>
                <th style="width: 11%;">Paralelo</th>
                <th style="width: 11%;">Estado</th>
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
                    $estado = $insc->estado ?? 'Activo';
                    $isBaja = in_array(strtoupper($estado), ['BAJA', 'RETIRADO']);
                @endphp
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ $ci }}</td>
                    <td class="left"><strong>{{ mb_convert_case($nombre, MB_CASE_TITLE, 'UTF-8') }}</strong></td>
                    <td>{{ mb_convert_case($idioma, MB_CASE_TITLE, 'UTF-8') }}</td>
                    <td>{{ mb_convert_case($nivel, MB_CASE_TITLE, 'UTF-8') }}</td>
                    <td>{{ mb_convert_case($paralelo, MB_CASE_TITLE, 'UTF-8') }}</td>
                    <td style="color: {{ $isBaja ? '#dc2626' : '#16a34a' }}; font-weight: bold;">{{ mb_convert_case($estado, MB_CASE_TITLE, 'UTF-8') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">No se encontraron matrículas con los filtros aplicados.</td>
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

    <div class="footer-note">
        Documento Oficial emitido por el Sistema de Gestión Académica EIE. Filial Cochabamba, Bolivia.
    </div>

</body>
</html>
