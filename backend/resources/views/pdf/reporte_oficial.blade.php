<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $titulo }}</title>
    <style>
        @page {
            margin: 15mm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10px;
            color: #1a1f26;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #003B71;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }
        .header-title {
            text-align: center;
        }
        .header-title h1 {
            font-size: 16px;
            color: #003B71;
            margin: 0 0 4px 0;
            text-transform: uppercase;
        }
        .header-title h2 {
            font-size: 12px;
            color: #4a5568;
            margin: 0;
            font-weight: normal;
        }
        .meta-info {
            margin-bottom: 12px;
            font-size: 9px;
            color: #718096;
        }
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .report-table th {
            background-color: #003B71;
            color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8.5px;
            padding: 6px 4px;
            border: 1px solid #002d57;
            text-align: left;
        }
        .report-table td {
            padding: 5px 4px;
            border: 1px solid #cbd5e0;
            font-size: 8.5px;
        }
        .report-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-activo { background-color: #def7ec; color: #03543f; }
        .badge-pendiente { background-color: #fdf6b2; color: #723b7a; }
        .badge-retirado { background-color: #fde8e8; color: #9b1c1c; }
        .badge-finalizado { background-color: #e1effe; color: #1e429f; }

        .signatures {
            margin-top: 30px;
            width: 100%;
        }
        .signature-box {
            width: 45%;
            float: left;
            text-align: center;
            border-top: 1px solid #718096;
            padding-top: 4px;
            font-size: 9px;
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
            font-size: 8px;
            color: #a0aec0;
            border-top: 1px solid #e2e8f0;
            padding-top: 4px;
        }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td style="width: 20%; vertical-align: middle;">
                <strong style="color: #003B71; font-size: 11px;">EIE BOLIVIA</strong>
            </td>
            <td class="header-title" style="width: 60%;">
                <h1>{{ $institucion }}</h1>
                <h2>{{ $titulo }}</h2>
                <div style="font-size: 9px; color: #718096;">{{ $departamento }}</div>
            </td>
            <td style="width: 20%; text-align: right; font-size: 8.5px; color: #4a5568;">
                <strong>Fecha:</strong> {{ $fecha }}<br>
                <strong>Registros:</strong> {{ $total_registros }}
            </td>
        </tr>
    </table>

    <table class="report-table">
        <thead>
            <tr>
                <th style="width: 3%; text-align: center;">Nº</th>
                <th style="width: 20%;">Estudiante</th>
                <th style="width: 8%;">C.I.</th>
                <th style="width: 12%;">Idioma</th>
                <th style="width: 10%;">Nivel</th>
                <th style="width: 8%;">Paralelo</th>
                <th style="width: 16%;">Tutor / Contacto</th>
                <th style="width: 8%; text-align: center;">F. Registro</th>
                <th style="width: 6%; text-align: center;">Prom.</th>
                <th style="width: 5%; text-align: center;">Asist.</th>
                <th style="width: 4%; text-align: center;">Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($inscripciones as $index => $insc)
                @php
                    $est = $insc->estudiante;
                    $nombre = $est ? trim(($est->nombres ?? '') . ' ' . ($est->apellidos ?? '')) : 'N/A';
                    $idioma = $insc->curso ? ($insc->curso->idioma ? ($insc->curso->idioma->nombre_idioma ?? $insc->curso->idioma->nombre) : 'N/A') : 'N/A';
                    $nivel = $insc->curso ? ($insc->curso->nivel ?? 'N/A') : 'N/A';
                    $paralelo = $insc->paralelo ? ($insc->paralelo->nombre_paralelo ?? 'N/A') : 'N/A';
                    $tutor = $est ? ($est->nombre_padres ?: $est->contacto_emergencia) : 'N/A';
                    $notasAvg = $insc->notas->count() > 0 ? round($insc->notas->avg('puntaje'), 1) : 0;
                    $totalAsist = $insc->asistencias->count();
                    $presentes = $insc->asistencias->where('estado', 'presente')->count();
                    $asistPct = $totalAsist > 0 ? round(($presentes / $totalAsist) * 100) : 100;
                    $st = strtolower($insc->estado);
                @endphp
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td><strong>{{ mb_strtoupper($nombre, 'UTF-8') }}</strong></td>
                    <td>{{ $est->ci ?? 'N/A' }}</td>
                    <td>{{ $idioma }}</td>
                    <td>{{ $nivel }}</td>
                    <td>{{ $paralelo }}</td>
                    <td>{{ $tutor }}</td>
                    <td style="text-align: center;">{{ $insc->fecha_registro }}</td>
                    <td style="text-align: center; font-weight: bold; color: {{ $notasAvg >= 51 ? '#03543f' : '#9b1c1c' }};">
                        {{ $notasAvg > 0 ? $notasAvg : '-' }}
                    </td>
                    <td style="text-align: center;">{{ $asistPct }}%</td>
                    <td style="text-align: center;">
                        <span class="badge badge-{{ $st }}">
                            {{ strtoupper(substr($st, 0, 3)) }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" style="text-align: center; padding: 15px; color: #718096;">
                        No se encontraron registros con los criterios seleccionados.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="signatures">
        <div class="signature-box">
            JEFE DE ESTUDIOS / DIRECCIÓN ACADÉMICA<br>
            ESCUELA DE IDIOMAS DEL EJÉRCITO
        </div>
        <div class="signature-box signature-box-right">
            SISTEMA DE GESTIÓN Y CONTROL ACADÉMICO<br>
            FIRMA Y SELLO OFICIAL
        </div>
    </div>

    <div class="footer">
        Documento oficial generado automáticamente por el Sistema de Gestión Académica EIE - Cochabamba, Bolivia
    </div>

</body>
</html>
