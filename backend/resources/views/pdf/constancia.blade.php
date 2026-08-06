<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte General de Notas</title>
    <style>
        @page {
            margin: 1.2cm 1.8cm;
        }
        body {
            font-family: 'Courier', 'Times New Roman', 'Arial', sans-serif;
            font-size: 10px;
            color: #000;
            line-height: 1.3;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .header-left {
            font-weight: bold;
            font-size: 9.5px;
            text-align: center;
            line-height: 1.2;
        }
        .header-right {
            text-align: right;
            font-weight: bold;
            font-size: 9.5px;
            vertical-align: top;
        }
        .title {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            margin: 12px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .student-box {
            border: 1.5px solid #000;
            border-radius: 10px;
            padding: 6px 10px;
            font-size: 9.5px;
            margin-bottom: 12px;
            font-weight: bold;
        }
        .table-notes {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0px;
        }
        .table-notes th, .table-notes td {
            border: 1px solid #000;
            padding: 4px 6px;
            text-align: center;
            font-size: 9.5px;
        }
        .table-notes th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .summary-box {
            border: 1.5px solid #000;
            border-top: none;
            border-radius: 0 0 10px 10px;
            padding: 6px 10px;
            font-size: 9.5px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .final-box {
            border: 1.5px solid #000;
            border-radius: 8px;
            text-align: center;
            padding: 6px;
            font-weight: bold;
            margin-bottom: 15px;
            font-size: 10.5px;
        }
        .note-text {
            font-size: 8.5px;
            font-weight: bold;
            float: left;
            width: 60%;
        }
        .date-text {
            font-size: 9.5px;
            font-weight: bold;
            float: right;
            text-align: right;
            width: 38%;
        }
        .clear {
            clear: both;
        }
        .signatures-table {
            width: 100%;
            margin-top: 40px;
            border-collapse: collapse;
        }
        .signatures-table td {
            text-align: center;
            vertical-align: bottom;
            font-size: 8.5px;
            font-weight: bold;
            padding: 0 5px;
        }
        .qr-section {
            margin-top: 15px;
            float: left;
            border: 1.5px solid #000;
            padding: 4px 8px;
            text-align: center;
            font-size: 8px;
            font-weight: bold;
            border-radius: 6px;
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

    <div class="title">REPORTE GENERAL DE NOTAS {{ strtoupper($curso->nivel ?? 'NIVEL I') }}</div>

    <div class="student-box">
        Matrícula : {{ $estudiante->ci }} &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Apellidos y Nombres : {{ mb_strtoupper(trim($estudiante->apellidos . ' ' . $estudiante->nombres), 'UTF-8') }} &nbsp;&nbsp;&nbsp;&nbsp; Curso de : {{ strtoupper($curso->idioma ?? 'INGLÉS') }}
    </div>

    <table class="table-notes">
        <thead>
            <tr>
                <th colspan="3">{{ strtoupper($curso->nivel ?? 'NIVEL I (BOOK 1-6)') }}</th>
            </tr>
            <tr>
                <th style="width: 33%;">Gestión</th>
                <th style="width: 33%;">Libros-</th>
                <th style="width: 34%;">Notas</th>
            </tr>
        </thead>
        <tbody>
            @for ($i = 1; $i <= 6; $i++)
                @php
                    $val = isset($notasLibros[$i]) ? (float)$notasLibros[$i] : 0;
                    $notaFormatted = number_format($val, 2, ',', '.');
                @endphp
                <tr>
                    <td>{{ date('Y') }}</td>
                    <td>{{ $i }}</td>
                    <td style="{{ $val == 0 ? 'color: red;' : '' }}">{{ $notaFormatted }}</td>
                </tr>
            @endfor
        </tbody>
    </table>

    <div class="summary-box">
        <table style="width: 100%; font-size: 9.5px; border-collapse: collapse;">
            <tr>
                <td>Promedio Libros</td>
                <td style="text-align: right;">{{ number_format($promedioLibros ?? 0.00, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td>80%</td>
                <td style="text-align: right;">{{ number_format($promedio80 ?? 0.00, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Examen de Nivel</td>
                <td style="text-align: right;">{{ number_format($examenNivel ?? 0.00, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td>20%</td>
                <td style="text-align: right;">{{ number_format($examen20 ?? 0.00, 2, ',', '.') }}</td>
            </tr>
            <tr style="border-top: 1.5px solid #000;">
                <td>Promedio Nivel</td>
                <td style="text-align: right;">{{ number_format($promedioNivel ?? 0.00, 2, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <div class="final-box">
        Promedio Gral de Fin de Gestión<br>
        <span style="font-size: 11.5px;">{{ number_format($promedioGral ?? 0.00, 2, ',', '.') }}</span>
    </div>

    <div class="note-text">
        NOTA: El presente certificado de notas, no refleja la conclusión de sus estudios.
    </div>
    <div class="date-text">
        {{ date('d') }} de {{ $mesNombre }} de {{ date('Y') }}.
    </div>
    <div class="clear"></div>

    <table class="signatures-table">
        <tr>
            <td style="width: 35%;">
                Tte. Inf. Nerling Delgadillo Arce<br>
                <strong>JEFE DE LA SUB SECCION ACADEMICA</strong>
            </td>
            <td style="width: 30%;">
                Vo.Bo.<br><br>
                My. DIM. Vaneza Mercedes Barrientos Fernández<br>
                <strong>JEFE DE ESTUDIOS DE LA EIE. FILIAL COCHABAMBA</strong>
            </td>
            <td style="width: 35%;">
                Cap. Inf. Luis Gerardo Thellaeche Borda<br>
                <strong>JEFE DE LA SECCION ACADEMICA</strong>
            </td>
        </tr>
    </table>

    <div class="qr-section">
        <div style="font-size: 10px; font-weight: bold; border-bottom: 1px solid #000; margin-bottom: 3px;">CÓDIGO QR</div>
        EIE-BOLIVIA<br>
        DOCUMENTO OFICIAL<br>
        CI: {{ $estudiante->ci }}
    </div>

</body>
</html>
