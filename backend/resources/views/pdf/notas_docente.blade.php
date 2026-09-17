<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Planilla Oficial de Calificaciones</title>
    <style>
        @page {
            margin: 1.2cm 1.8cm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
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
            padding: 4px 5px;
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

    <div class="title">PLANILLA OFICIAL DE CALIFICACIONES DE ALUMNOS</div>

    <div class="meta-box">
        <strong>PARALELO:</strong> {{ mb_strtoupper($paralelo->nombre_paralelo ?? $paralelo->nombre ?? 'A', 'UTF-8') }} &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <strong>IDIOMA Y NIVEL:</strong> {{ mb_strtoupper((is_object($curso->idioma ?? null) ? ($curso->idioma->nombre_idioma ?? $curso->idioma->nombre ?? 'INGLÉS') : ($curso->idioma ?? 'INGLÉS')) . ' - ' . ($curso->nivel ?? 'NIVEL I'), 'UTF-8') }}<br>
        <strong>AULA:</strong> {{ !empty($paralelo->aula) ? ($paralelo->aula->nombre_aula ?? $paralelo->aula->nombre ?? 'Sin Aula') : 'Sin Aula' }} &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <strong>FECHA:</strong> {{ date('d/m/Y') }}
    </div>

    <table class="table-list">
        <thead>
            <tr>
                <th style="width: 4%;">Nro</th>
                <th style="width: 12%;">C.I.</th>
                <th style="width: 10%;">Grado</th>
                <th style="width: {{ !empty($isGeneral) ? '24%' : '32%' }};" class="left">Nombres y Apellidos</th>
                @if(!empty($isGeneral))
                    <th style="width: 15%;">Idioma / Nivel</th>
                    <th style="width: 9%;">Paralelo</th>
                @endif
                <th style="width: {{ !empty($isGeneral) ? '5%' : '6%' }};">B1</th>
                <th style="width: {{ !empty($isGeneral) ? '5%' : '6%' }};">B2</th>
                <th style="width: {{ !empty($isGeneral) ? '5%' : '6%' }};">B3</th>
                <th style="width: {{ !empty($isGeneral) ? '5%' : '6%' }};">B4</th>
                <th style="width: {{ !empty($isGeneral) ? '5%' : '7%' }};">Ex. Fin</th>
                <th style="width: {{ !empty($isGeneral) ? '6%' : '7%' }};">Prom.</th>
            </tr>
        </thead>
        <tbody>
            @php $idx = 1; @endphp
            @foreach ($inscripciones as $insc)
                @php 
                    $est = $insc->estudiante ?? null; 
                    $b1 = 0; $b2 = 0; $b3 = 0; $b4 = 0; $ex = 0; $prom = 0;
                    // Solo estudiantes con paralelo asignado computan calificaciones de aula
                    if (!empty($insc->id_paralelo) && !empty($insc->notas) && $insc->notas->count() > 0) {
                        foreach ($insc->notas as $n) {
                            $per = strtolower(trim((string)($n->periodo ?? '')));
                            $val = floatval($n->nota ?? 0);
                            if (str_contains($per, '1') || str_contains($per, 'book 1') || str_contains($per, 'parcial 1')) $b1 = $val;
                            elseif (str_contains($per, '2') || str_contains($per, 'book 2') || str_contains($per, 'parcial 2')) $b2 = $val;
                            elseif (str_contains($per, '3') || str_contains($per, 'book 3') || str_contains($per, 'parcial 3')) $b3 = $val;
                            elseif (str_contains($per, '4') || str_contains($per, 'book 4') || str_contains($per, 'parcial 4')) $b4 = $val;
                            elseif (str_contains($per, 'final') || str_contains($per, 'nivel') || str_contains($per, 'examen')) $ex = $val;
                        }
                        $registradas = array_filter([$b1, $b2, $b3, $b4, $ex], fn($v) => $v > 0);
                        $prom = count($registradas) > 0 ? round(array_sum($registradas) / count($registradas), 1) : 0;
                    }
                    $idmRow = $insc->curso && $insc->curso->idioma ? ($insc->curso->idioma->nombre_idioma ?? $insc->curso->idioma->nombre ?? 'N/A') : 'N/A';
                    $nvlRow = $insc->curso ? ($insc->curso->nivelRel->nombre_nivel ?? $insc->curso->nivel ?? '') : '';
                    $parRow = $insc->paralelo ? ($insc->paralelo->nombre_paralelo ?? $insc->paralelo->nombre ?? 'Sin Paralelo') : 'Sin Paralelo';
                @endphp
                <tr>
                    <td>{{ $idx++ }}</td>
                    <td>{{ $est?->ci ?? 'N/A' }}</td>
                    <td>{{ mb_convert_case($est?->grado_academico ?? $est?->grado ?? 'Civil', MB_CASE_TITLE, 'UTF-8') }}</td>
                    <td class="left"><strong>{{ mb_convert_case(trim(($est?->nombres ?? '') . ' ' . ($est?->apellidos ?? '')), MB_CASE_TITLE, 'UTF-8') }}</strong></td>
                    @if(!empty($isGeneral))
                        <td>{{ mb_convert_case($idmRow . ($nvlRow ? ' - ' . $nvlRow : ''), MB_CASE_TITLE, 'UTF-8') }}</td>
                        <td>{{ mb_convert_case($parRow, MB_CASE_TITLE, 'UTF-8') }}</td>
                    @endif
                    <td>{{ $b1 > 0 ? $b1 : '—' }}</td>
                    <td>{{ $b2 > 0 ? $b2 : '—' }}</td>
                    <td>{{ $b3 > 0 ? $b3 : '—' }}</td>
                    <td>{{ $b4 > 0 ? $b4 : '—' }}</td>
                    <td>{{ $ex > 0 ? $ex : '—' }}</td>
                    <td><strong>{{ $prom > 0 ? $prom : '—' }}</strong></td>
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

    <div class="footer-note">
        Documento Oficial emitido por el Sistema de Gestión Académica EIE. Filial Cochabamba, Bolivia.
    </div>

</body>
</html>
