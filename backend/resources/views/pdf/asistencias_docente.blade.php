<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Planilla Oficial de Asistencias</title>
    <style>
        @page {
            margin: 1.2cm 1.8cm;
        }
        body {
            font-family: 'Courier', 'Times New Roman', 'Arial', sans-serif;
            font-size: 10px;
            color: #000000;
            background-color: #ffffff;
            line-height: 1.35;
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
        .table-list {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table-list th, .table-list td {
            border: 1px solid #000000;
            padding: 4px 6px;
            text-align: center;
            font-size: 9px;
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
            margin-top: 45px;
            border-collapse: collapse;
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

    <div class="title">PLANILLA OFICIAL DE ASISTENCIA DE ALUMNOS</div>

    <div class="meta-box">
        <strong>PARALELO:</strong> {{ strtoupper($paralelo->nombre_paralelo ?? $paralelo->nombre ?? 'A') }} &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <strong>IDIOMA Y NIVEL:</strong> {{ strtoupper(($curso->idioma->nombre_idioma ?? $curso->idioma ?? 'INGLÉS') . ' - ' . ($curso->nivel ?? 'NIVEL I')) }}<br>
        <strong>AULA:</strong> {{ $paralelo->aula ? ($paralelo->aula->nombre_aula ?? $paralelo->aula->nombre) : 'Sin Aula' }} &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <strong>FECHA:</strong> {{ date('d/m/Y') }}
    </div>

    <table class="table-list">
        <thead>
            <tr>
                <th style="width: 5%;">Nro</th>
                <th style="width: 15%;">C.I.</th>
                <th style="width: 14%;">Grado</th>
                <th style="width: 38%;" class="left">Apellidos y Nombres</th>
                <th style="width: 9%;">Clases</th>
                <th style="width: 9%;">Presente</th>
                <th style="width: 10%;">% Asist.</th>
            </tr>
        </thead>
        <tbody>
            @php $idx = 1; @endphp
            @foreach ($inscripciones as $insc)
                @php 
                    $est = $insc->estudiante; 
                    $totalSesiones = $insc->asistencias ? $insc->asistencias->count() : 0;
                    $presentes = $insc->asistencias ? $insc->asistencias->where('estado', 'presente')->count() : 0;
                    $pct = $totalSesiones > 0 ? round(($presentes / $totalSesiones) * 100, 1) : 100;
                @endphp
                <tr>
                    <td>{{ $idx++ }}</td>
                    <td>{{ $est->ci ?? 'N/A' }}</td>
                    <td>{{ strtoupper($est->grado_academico ?? $est->grado ?? 'SR') }}</td>
                    <td class="left"><strong>{{ mb_strtoupper(trim(($est->apellidos ?? '') . ' ' . ($est->nombres ?? '')), 'UTF-8') }}</strong></td>
                    <td>{{ $totalSesiones }}</td>
                    <td>{{ $presentes }}</td>
                    <td><strong>{{ $pct }}%</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="signatures-table">
        <tr>
            <td style="width: 45%;">
                <div class="sig-line">
                    FIRMA DEL INSTRUCTOR<br>
                    DOCENTE RESPONSABLE
                </div>
            </td>
            <td style="width: 10%;"></td>
            <td style="width: 45%;">
                <div class="sig-line">
                    VO.BO. SECCIÓN ACADÉMICA<br>
                    ESCUELA DE IDIOMAS DEL EJÉRCITO
                </div>
            </td>
        </tr>
    </table>

    <div class="qr-section">
        CÓDIGO DE VERIFICACIÓN<br>
        EIE-DOCENTE-PARALELO-{{ $paralelo->id_paralelo ?? $paralelo->id ?? '1' }}<br>
        FECHA: {{ date('d/m/Y') }}
    </div>

    <div class="footer-note">
        Documento Oficial emitido por el Sistema de Gestión Académica EIE. Filial Cochabamba, Bolivia.
    </div>

</body>
</html>
