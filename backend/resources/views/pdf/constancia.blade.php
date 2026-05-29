<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Constancia de Estudios - EIE</title>
    <style>
        @page {
            margin: 2cm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #1a237e;
            padding-bottom: 10px;
        }
        .logo {
            width: 80px;
            height: auto;
            margin-bottom: 10px;
        }
        .institution-name {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            color: #1a237e;
            margin: 0;
        }
        .document-title {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            margin-top: 40px;
            margin-bottom: 40px;
            text-decoration: underline;
        }
        .content {
            font-size: 14px;
            text-align: justify;
            margin-bottom: 60px;
        }
        .data-table {
            width: 100%;
            margin-bottom: 30px;
            border-collapse: collapse;
        }
        .data-table td {
            padding: 8px;
            border: 1px solid #ddd;
        }
        .label {
            font-weight: bold;
            background-color: #f5f5f5;
            width: 30%;
        }
        .footer {
            margin-top: 100px;
        }
        .signature-container {
            width: 100%;
        }
        .signature-box {
            width: 45%;
            display: inline-block;
            text-align: center;
            border-top: 1px solid #333;
            margin: 0 2%;
            padding-top: 10px;
            font-size: 12px;
        }
        .date-section {
            text-align: right;
            margin-top: 20px;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="header">
        <!-- En entorno real usaríamos asset() o base64 -->
        <h1 class="institution-name">ESCUELA DE IDIOMAS DEL EJÉRCITO</h1>
        <p style="margin: 5px 0; font-size: 12px;">Departamento de Coordinación Académica</p>
    </div>

    <div class="document-title">CONSTANCIA DE ESTUDIOS</div>

    <div class="content">
        <p>Por la presente, la <strong>Escuela de Idiomas del Ejército</strong> hace constar que el/la estudiante cuyos datos se detallan a continuación, se encuentra debidamente registrado/a y activo/a en el programa académico institucional:</p>

        <table class="data-table">
            <tr>
                <td class="label">Nombres y Apellidos:</td>
                <td>{{ $estudiante->nombres }} {{ $estudiante->apellidos }}</td>
            </tr>
            <tr>
                <td class="label">Documento de Identidad:</td>
                <td>{{ $estudiante->ci }}</td>
            </tr>
            <tr>
                <td class="label">Curso / Idioma:</td>
                <td>{{ $curso->idioma }}</td>
            </tr>
            <tr>
                <td class="label">Nivel / Modalidad:</td>
                <td>{{ $curso->nivel }} - {{ $curso->modalidad }}</td>
            </tr>
            <tr>
                <td class="label">Paralelo:</td>
                <td>{{ $paralelo->nombre }}</td>
            </tr>
            <tr>
                <td class="label">Estado Académico:</td>
                <td>{{ $inscripcion->estado }}</td>
            </tr>
        </table>

        <p>Se extiende la presente constancia para los fines que el/la interesado/a convenga, en la ciudad de La Paz, a los {{ date('d') }} días del mes de {{ date('F') }} de {{ date('Y') }}.</p>
    </div>

    <div class="footer">
        <div class="signature-container">
            <div class="signature-box">
                Sello y Firma<br>
                <strong>Jefe de Estudios EIE</strong>
            </div>
            <div class="signature-box">
                Firma del Estudiante<br>
                <strong>{{ $estudiante->nombres }} {{ $estudiante->apellidos }}</strong>
            </div>
        </div>
    </div>
    
    <div class="date-section">
        Generado el: {{ date('d/m/Y H:i:s') }}
    </div>
</body>
</html>
