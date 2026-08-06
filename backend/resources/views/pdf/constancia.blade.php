<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Contrato y Constancia de Inscripción</title>
    <style>
        @page {
            margin: 1.5cm 2cm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #1a202c;
            line-height: 1.4;
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
            font-size: 15px;
            color: #003B71;
            margin: 0 0 3px 0;
            text-transform: uppercase;
        }
        .header-title h2 {
            font-size: 11px;
            color: #4a5568;
            margin: 0;
            font-weight: normal;
        }
        .doc-title {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            color: #003B71;
            margin: 12px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-top: 1px dashed #cbd5e0;
            border-bottom: 1px dashed #cbd5e0;
            padding: 5px 0;
        }
        .section-box {
            border: 1px solid #cbd5e0;
            border-radius: 6px;
            padding: 8px 12px;
            margin-bottom: 12px;
            background-color: #ffffff;
        }
        .section-header {
            font-size: 10.5px;
            font-weight: bold;
            color: #003B71;
            text-transform: uppercase;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 3px;
            margin-bottom: 6px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 3px 4px;
            font-size: 9.5px;
            vertical-align: top;
        }
        .label {
            font-weight: bold;
            color: #4a5568;
            width: 32%;
        }
        .value {
            color: #1a202c;
            width: 68%;
        }
        .signatures-table {
            width: 100%;
            margin-top: 40px;
            border-collapse: collapse;
        }
        .signatures-table td {
            text-align: center;
            vertical-align: bottom;
            font-size: 9px;
            font-weight: bold;
            padding: 0 15px;
        }
        .sig-line {
            border-top: 1px solid #4a5568;
            padding-top: 4px;
        }
        .qr-container {
            float: left;
            margin-top: 10px;
            border: 1px solid #003B71;
            padding: 5px 8px;
            border-radius: 5px;
            text-align: center;
            font-size: 7.5px;
            font-weight: bold;
            color: #003B71;
        }
        .footer {
            margin-top: 25px;
            text-align: center;
            font-size: 8px;
            color: #718096;
        }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td style="width: 20%; text-align: left; vertical-align: middle;">
                <strong style="color: #003B71; font-size: 11px;">EIE BOLIVIA</strong>
            </td>
            <td class="header-title" style="width: 60%;">
                <h1>ESCUELA DE IDIOMAS DEL EJÉRCITO</h1>
                <h2>FILIAL COCHABAMBA - BOLIVIA</h2>
            </td>
            <td style="width: 20%; text-align: right; font-size: 8.5px; color: #4a5568; vertical-align: middle;">
                <strong>Fecha:</strong> {{ date('d/m/Y') }}
            </td>
        </tr>
    </table>

    <div class="doc-title">CONTRATO Y CONSTANCIA DE INSCRIPCIÓN</div>

    <!-- DATOS PERSONALES DEL ESTUDIANTE -->
    <div class="section-box">
        <div class="section-header">1. DATOS PERSONALES DEL ESTUDIANTE</div>
        <table class="info-table">
            <tr>
                <td class="label">Cédula de Identidad / C.I.:</td>
                <td class="value"><strong>{{ $estudiante->ci }}</strong></td>
            </tr>
            <tr>
                <td class="label">Apellidos y Nombres:</td>
                <td class="value"><strong>{{ mb_strtoupper(trim($estudiante->apellidos . ' ' . $estudiante->nombres), 'UTF-8') }}</strong></td>
            </tr>
            <tr>
                <td class="label">Grado / Ocupación:</td>
                <td class="value">{{ $estudiante->grado_academico ?: 'ESTUDIANTE' }}</td>
            </tr>
            <tr>
                <td class="label">Teléfono / Celular:</td>
                <td class="value">{{ $estudiante->celular ?: 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Domicilio:</td>
                <td class="value">{{ $estudiante->domicilio ?: 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <!-- DATOS DE LOS PADRES / TUTOR -->
    <div class="section-box">
        <div class="section-header">2. DATOS DE LOS PADRES / TUTOR / EMERGENCIA</div>
        <table class="info-table">
            <tr>
                <td class="label">Nombre de los Padres / Tutor:</td>
                <td class="value"><strong>{{ $estudiante->nombre_padres ?: 'N/A' }}</strong></td>
            </tr>
            <tr>
                <td class="label">Contacto de Emergencia:</td>
                <td class="value">{{ $estudiante->contacto_emergencia ?: $estudiante->celular ?: 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <!-- DATOS DEL CURSO E INSCRIPCIÓN -->
    <div class="section-box">
        <div class="section-header">3. DATOS DEL CURSO Y HORARIO</div>
        <table class="info-table">
            <tr>
                <td class="label">Idioma Registrado:</td>
                <td class="value"><strong>{{ strtoupper($curso->idioma ?? 'INGLÉS') }}</strong></td>
            </tr>
            <tr>
                <td class="label">Nivel de Estudio:</td>
                <td class="value"><strong>{{ strtoupper($curso->nivel ?? 'NIVEL 1') }}</strong></td>
            </tr>
            <tr>
                <td class="label">Paralelo Asignado:</td>
                <td class="value">{{ strtoupper($paralelo->nombre ?? 'A') }}</td>
            </tr>
            <tr>
                <td class="label">Horario de Clases:</td>
                <td class="value"><strong>{{ $curso->horario ?: ($paralelo->horario ?: '08:00 - 12:00 / Turno Regular') }}</strong></td>
            </tr>
            <tr>
                <td class="label">Modalidad:</td>
                <td class="value">{{ strtoupper($curso->modalidad ?? 'PRESENCIAL') }}</td>
            </tr>
            <tr>
                <td class="label">Fecha de Inscripción:</td>
                <td class="value">{{ $inscripcion->fecha_registro ?: date('Y-m-d') }}</td>
            </tr>
            <tr>
                <td class="label">Estado de Inscripción:</td>
                <td class="value"><span style="color: #03543f; font-weight: bold;">{{ strtoupper($inscripcion->estado ?: 'CONFIRMADO') }}</span></td>
            </tr>
        </table>
    </div>

    <table class="signatures-table">
        <tr>
            <td style="width: 45%;">
                <div class="sig-line">
                    FIRMA DEL ESTUDIANTE / TUTOR<br>
                    C.I. {{ $estudiante->ci }}
                </div>
            </td>
            <td style="width: 10%;"></td>
            <td style="width: 45%;">
                <div class="sig-line">
                    SELLO Y FIRMA DE RECEPCIÓN<br>
                    ESCUELA DE IDIOMAS DEL EJÉRCITO
                </div>
            </td>
        </tr>
    </table>

    <div class="qr-container">
        CÓDIGO DE VERIFICACIÓN<br>
        EIE-INSCRIPCIÓN-{{ $inscripcion->id_inscripcion ?: '1' }}<br>
        CI: {{ $estudiante->ci }}
    </div>

    <div class="footer" style="clear: both; padding-top: 15px;">
        Documento Oficial de Registro de Inscripción emitido por el Sistema de Gestión Académica EIE. Cochabamba, Bolivia.
    </div>

</body>
</html>
