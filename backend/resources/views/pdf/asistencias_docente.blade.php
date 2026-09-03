<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Planilla Oficial de Asistencias</title>
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
        .pct-val {
            font-weight: bold;
            font-size: 9px;
            color: #003B71;
        }
        .badge-regular {
            color: #166534;
            font-weight: bold;
        }
        .badge-riesgo {
            color: #854d0e;
            font-weight: bold;
        }
        .badge-inhabilitado {
            color: #991b1b;
            font-weight: bold;
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
                SUBSECCIÓN DE ASISTENCIA Y DISCIPLINA<br>
                GESTIÓN: {{ date('Y') }}
            </td>
        </tr>
    </table>

    <div class="title">PLANILLA OFICIAL DE ASISTENCIAS DEL PERSONAL DE ALUMNOS</div>
    <div class="subtitle">CONTROL Y REGISTRO DE ASISTENCIAS POR PARALELO</div>

    <div class="meta-box">
        <table class="meta-table">
            <tr>
                <td style="width: 35%;"><strong>PARALELO:</strong> {{ strtoupper($paralelo->nombre_paralelo ?? $paralelo->nombre ?? 'A') }}</td>
                <td style="width: 35%;"><strong>IDIOMA / NIVEL:</strong> {{ strtoupper(($curso->idioma->nombre_idioma ?? $curso->idioma ?? 'INGLÉS') . ' - ' . ($curso->nivel ?? 'NIVEL I')) }}</td>
                <td style="width: 30%;"><strong>AULA:</strong> {{ $paralelo->aula ? ($paralelo->aula->nombre_aula ?? $paralelo->aula->nombre) : 'Sin Aula Asignada' }}</td>
            </tr>
            <tr>
                <td><strong>DOCENTE TITULAR:</strong> {{ strtoupper($docente ? ($docente->nombres . ' ' . $docente->apellidos) : 'DOCENTE ASIGNADO') }}</td>
                <td><strong>HORARIO:</strong> {{ strtoupper($curso->horario ?? 'REGULAR') }}</td>
                <td><strong>FECHA DE EMISIÓN:</strong> {{ date('d/m/Y H:i') }}</td>
            </tr>
        </table>
    </div>

    <table class="table-list">
        <thead>
            <tr>
                <th style="width: 5%;">Nro</th>
                <th style="width: 13%;">C.I.</th>
                <th style="width: 10%;">Grado</th>
                <th style="width: 34%;" class="left">Apellidos y Nombres</th>
                <th style="width: 8%;">Total Clases</th>
                <th style="width: 8%;">Presentes</th>
                <th style="width: 7%;">Faltas</th>
                <th style="width: 7%;">Licencias</th>
                <th style="width: 8%;">% Asist.</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $idx = 1; 
                $totalAlumnos = count($inscripciones);
            @endphp
            @foreach ($inscripciones as $insc)
                @php 
                    $est = $insc->estudiante; 
                    $totalSesiones = $insc->asistencias ? $insc->asistencias->count() : 0;
                    $presentes = $insc->asistencias ? $insc->asistencias->where('estado', 'presente')->count() : 0;
                    $faltas = $insc->asistencias ? $insc->asistencias->where('estado', 'falta')->count() : 0;
                    $licencias = $insc->asistencias ? $insc->asistencias->where('estado', 'licencia')->count() : 0;
                    $pct = $totalSesiones > 0 ? round(($presentes / $totalSesiones) * 100, 1) : 100;
                @endphp
                <tr>
                    <td>{{ $idx++ }}</td>
                    <td>{{ $est->ci ?? 'N/A' }}</td>
                    <td>{{ $est->grado ?? 'Civil' }}</td>
                    <td class="left bold">{{ strtoupper(($est->apellidos ?? '') . ' ' . ($est->nombres ?? '')) }}</td>
                    <td>{{ $totalSesiones }}</td>
                    <td style="color: #166534; font-weight: bold;">{{ $presentes }}</td>
                    <td style="color: #991b1b; font-weight: bold;">{{ $faltas }}</td>
                    <td style="color: #854d0e; font-weight: bold;">{{ $licencias }}</td>
                    <td class="pct-val">{{ $pct }}%</td>
                </tr>
            @endforeach
            @if ($totalAlumnos === 0)
                <tr>
                    <td colspan="9" style="padding: 12px; color: #64748b;">No existen alumnos registrados en este paralelo.</td>
                </tr>
            @endif
        </tbody>
    </table>

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
                    CONTROL DE ASISTENCIA Y DISCIPLINA<br>
                    <strong>ESCUELA DE IDIOMAS DEL EJÉRCITO</strong>
                </div>
            </td>
            <td style="width: 33%;">
                <div class="sig-line">
                    JEFE DE SECCIÓN ACADÉMICA<br>
                    <strong>EIE FILIAL COCHABAMBA</strong>
                </div>
            </td>
        </tr>
    </table>

    <div class="footer-note">
        Documento Oficial emitido por el Sistema de Gestión Académica EIE • Validez Institucional • Fecha de emisión: {{ date('d/m/Y H:i:s') }}
    </div>

</body>
</html>
