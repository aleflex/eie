<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Planilla Oficial de Calificaciones</title>
    <style>
        @page {
            margin: 1.0cm 1.5cm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 9px;
            color: #111827;
            background-color: #ffffff;
            line-height: 1.3;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .header-left {
            font-weight: bold;
            font-size: 9px;
            text-align: center;
            line-height: 1.25;
            color: #000000;
        }
        .header-right {
            text-align: right;
            font-weight: bold;
            font-size: 9px;
            vertical-align: top;
            color: #000000;
            line-height: 1.25;
        }
        .title {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            color: #003B71;
            margin: 10px 0 6px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .subtitle {
            text-align: center;
            font-size: 10px;
            font-weight: bold;
            color: #4b5563;
            margin-bottom: 12px;
        }
        .meta-box {
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
            padding: 6px 10px;
            margin-bottom: 14px;
            font-size: 9px;
            border-radius: 4px;
            line-height: 1.5;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
        }
        .meta-table td {
            padding: 2px 4px;
            font-size: 9px;
        }
        .table-list {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .table-list th, .table-list td {
            border: 1px solid #94a3b8;
            padding: 4px 5px;
            text-align: center;
            font-size: 8.5px;
            vertical-align: middle;
        }
        .table-list th {
            background-color: #003B71;
            color: #ffffff;
            font-weight: bold;
            font-size: 8.5px;
        }
        .table-list tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .left {
            text-align: left !important;
        }
        .bold {
            font-weight: bold;
        }
        .prom-val {
            font-weight: bold;
            font-size: 9.5px;
            color: #003B71;
            background-color: #e0f2fe;
        }
        .badge-aprobado {
            color: #166534;
            font-weight: bold;
        }
        .badge-reprobado {
            color: #991b1b;
            font-weight: bold;
        }
        .badge-proceso {
            color: #854d0e;
            font-weight: bold;
        }
        .stats-summary {
            margin-top: 10px;
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
            padding: 6px 10px;
            font-size: 8.5px;
            margin-bottom: 25px;
        }
        .signatures-table {
            width: 100%;
            margin-top: 30px;
            border-collapse: collapse;
            page-break-inside: avoid;
        }
        .signatures-table td {
            text-align: center;
            vertical-align: bottom;
            font-size: 8.5px;
            font-weight: bold;
            color: #000000;
            padding: 0 15px;
        }
        .sig-line {
            border-top: 1px solid #000000;
            padding-top: 4px;
        }
        .footer-note {
            margin-top: 15px;
            font-size: 7.5px;
            color: #64748b;
            text-align: center;
            clear: both;
        }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td class="header-left" style="width: 50%;">
                FACULTAD DE CIENCIAS Y ARTES MILITARES TERRESTRES<br>
                "GRAL. DIV. JOSÉ MIGUEL LANZA"<br>
                ESCUELA DE IDIOMAS DEL EJÉRCITO<br>
                <u>FILIAL COCHABAMBA - BOLIVIA</u>
            </td>
            <td class="header-right" style="width: 50%;">
                DEPARTAMENTO VI - EDUCACIÓN<br>
                SUBSECCIÓN DE EVALUACIÓN Y ESTADÍSTICA ACADÉMICA<br>
                GESTIÓN: {{ date('Y') }}
            </td>
        </tr>
    </table>

    <div class="title">PLANILLA OFICIAL DE CALIFICACIONES DE ALUMNOS</div>
    <div class="subtitle">REGISTRO CENTRALIZADOR DE EVALUACIONES POR PERÍODO</div>

    <div class="meta-box">
        <table class="meta-table">
            <tr>
                <td style="width: 35%;"><strong>PARALELO:</strong> {{ strtoupper($paralelo->nombre_paralelo ?? $paralelo->nombre ?? 'A') }}</td>
                <td style="width: 35%;"><strong>IDIOMA / NIVEL:</strong> {{ strtoupper(($curso->idioma->nombre_idioma ?? $curso->idioma ?? 'INGLÉS') . ' - ' . ($curso->nivel ?? 'NIVEL I')) }}</td>
                <td style="width: 30%;"><strong>AULA:</strong> {{ $paralelo->aula ? ($paralelo->aula->nombre_aula ?? $paralelo->aula->nombre) : 'Sin Aula Asignada' }}</td>
            </tr>
            <tr>
                <td><strong>DOCENTE TITULAR:</strong> {{ strtoupper($docente ? ($docente->nombres . ' ' . $docente->apellidos) : 'DOCENTE ASIGNADO') }}</td>
                <td><strong>MODALIDAD:</strong> {{ strtoupper($curso->modalidad ?? 'PRESENCIAL') }}</td>
                <td><strong>FECHA DE EMISIÓN:</strong> {{ date('d/m/Y H:i') }}</td>
            </tr>
        </table>
    </div>

    <table class="table-list">
        <thead>
            <tr>
                <th style="width: 4%;">Nro</th>
                <th style="width: 11%;">C.I.</th>
                <th style="width: 9%;">Grado</th>
                <th style="width: 30%;" class="left">Apellidos y Nombres</th>
                <th style="width: 7%;">Book 1</th>
                <th style="width: 7%;">Book 2</th>
                <th style="width: 7%;">Book 3</th>
                <th style="width: 7%;">Book 4</th>
                <th style="width: 7%;">Ex. Final</th>
                <th style="width: 8%;">Promedio</th>
                <th style="width: 10%;">Estado</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $idx = 1; 
                $totalAprobados = 0;
                $totalReprobados = 0;
                $sumaPromedios = 0;
                $totalAlumnos = count($inscripciones);
            @endphp
            @foreach ($inscripciones as $insc)
                @php 
                    $est = $insc->estudiante; 
                    $b1 = 0; $b2 = 0; $b3 = 0; $b4 = 0; $ex = 0; $prom = 0;
                    if ($insc->notas && $insc->notas->count() > 0) {
                        foreach ($insc->notas as $notaItem) {
                            $per = strtolower(trim($notaItem->periodo ?? ''));
                            $val = floatval($notaItem->nota ?? 0);
                            if (str_contains($per, '1') || str_contains($per, 'parcial 1') || str_contains($per, 'book 1')) $b1 = $val;
                            elseif (str_contains($per, '2') || str_contains($per, 'parcial 2') || str_contains($per, 'book 2')) $b2 = $val;
                            elseif (str_contains($per, '3') || str_contains($per, 'parcial 3') || str_contains($per, 'book 3')) $b3 = $val;
                            elseif (str_contains($per, '4') || str_contains($per, 'parcial 4') || str_contains($per, 'book 4')) $b4 = $val;
                            elseif (str_contains($per, 'final') || str_contains($per, 'nivel') || str_contains($per, 'examen')) $ex = $val;
                        }
                        // Promedio ponderado o aritmético de las notas registradas
                        $notasRegistradas = array_filter([$b1, $b2, $b3, $b4, $ex], fn($v) => $v > 0);
                        $prom = count($notasRegistradas) > 0 ? round(array_sum($notasRegistradas) / count($notasRegistradas), 1) : 0;
                    }
                    $sumaPromedios += $prom;
                    if ($prom >= 70) $totalAprobados++;
                    elseif ($prom > 0) $totalReprobados++;
                @endphp
                <tr>
                    <td>{{ $idx++ }}</td>
                    <td>{{ $est->ci ?? 'N/A' }}</td>
                    <td>{{ $est->grado ?? 'Civil' }}</td>
                    <td class="left bold">{{ strtoupper(($est->apellidos ?? '') . ' ' . ($est->nombres ?? '')) }}</td>
                    <td>{{ $b1 > 0 ? $b1 : '-' }}</td>
                    <td>{{ $b2 > 0 ? $b2 : '-' }}</td>
                    <td>{{ $b3 > 0 ? $b3 : '-' }}</td>
                    <td>{{ $b4 > 0 ? $b4 : '-' }}</td>
                    <td>{{ $ex > 0 ? $ex : '-' }}</td>
                    <td class="prom-val">{{ $prom > 0 ? $prom : '-' }}</td>
                    <td>
                        @if ($prom >= 70)
                            <span class="badge-aprobado">APROBADO</span>
                        @elseif ($prom > 0)
                            <span class="badge-reprobado">REPROBADO</span>
                        @else
                            <span class="badge-proceso">EN CURSO</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            @if ($totalAlumnos === 0)
                <tr>
                    <td colspan="11" style="padding: 12px; color: #64748b;">No existen alumnos registrados en este paralelo.</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="stats-summary">
        <strong>RESUMEN ESTADÍSTICO DE EVALUACIÓN:</strong> &nbsp;&nbsp;|&nbsp;&nbsp;
        <strong>Total Alumnos:</strong> {{ $totalAlumnos }} &nbsp;&nbsp;|&nbsp;&nbsp;
        <strong>Aprobados:</strong> {{ $totalAprobados }} &nbsp;&nbsp;|&nbsp;&nbsp;
        <strong>Reprobados:</strong> {{ $totalReprobados }} &nbsp;&nbsp;|&nbsp;&nbsp;
        <strong>Promedio General del Curso:</strong> {{ $totalAlumnos > 0 ? round($sumaPromedios / $totalAlumnos, 1) : '0' }} / 100
    </div>

    <table class="signatures-table">
        <tr>
            <td style="width: 33%;">
                <div class="sig-line">
                    {{ strtoupper($docente ? ($docente->nombres . ' ' . $docente->apellidos) : 'DOCENTE DEL CURSO') }}<br>
                    <strong>DOCENTE TITULAR EIE</strong>
                </div>
            </td>
            <td style="width: 34%;">
                <div class="sig-line">
                    JEFE DE SECCIÓN ACADÉMICA<br>
                    <strong>ESCUELA DE IDIOMAS DEL EJÉRCITO</strong>
                </div>
            </td>
            <td style="width: 33%;">
                <div class="sig-line">
                    COMANDANTE / DIRECTOR FILIAL<br>
                    <strong>EIE COCHABAMBA</strong>
                </div>
            </td>
        </tr>
    </table>

    <div class="footer-note">
        Documento Oficial emitido por el Sistema de Gestión Académica EIE • Validez Legal y Académica Institucional • Fecha de impresión: {{ date('d/m/Y H:i:s') }}
    </div>

</body>
</html>
