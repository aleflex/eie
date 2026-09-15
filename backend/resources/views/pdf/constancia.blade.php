<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Contrato y Constancia de Inscripción</title>
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
            letter-spacing: 0.5px;
        }
        .section-title {
            font-size: 10px;
            font-weight: bold;
            color: #000000;
            margin-top: 12px;
            margin-bottom: 4px;
            border-bottom: 1px solid #000000;
            padding-bottom: 2px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .info-table td {
            padding: 3px 4px;
            font-size: 9.5px;
            vertical-align: top;
            border-bottom: 1px solid #f0f0f0;
        }
        .label {
            font-weight: bold;
            color: #000000;
            width: 35%;
        }
        .value {
            color: #000000;
            width: 65%;
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

    <div class="title">Contrato y Constancia de Inscripción</div>

    <div class="section-title">1. Datos Personales del Estudiante</div>
    <table class="info-table">
        <tr>
            <td class="label">Cédula de Identidad (C.I.):</td>
            <td class="value"><strong>{{ $estudiante->ci }}</strong></td>
        </tr>
        <tr>
            <td class="label">Apellidos y Nombres:</td>
            <td class="value"><strong>{{ mb_convert_case(trim($estudiante->apellidos . ' ' . $estudiante->nombres), MB_CASE_TITLE, 'UTF-8') }}</strong></td>
        </tr>
        <tr>
            <td class="label">Grado / Ocupación:</td>
            <td class="value">{{ mb_convert_case($estudiante->grado_academico ?: 'Estudiante', MB_CASE_TITLE, 'UTF-8') }}</td>
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

    <div class="section-title">2. Datos de los Padres / Tutor / Emergencia</div>
    <table class="info-table">
        <tr>
            <td class="label">Nombre de los Padres / Tutor:</td>
            <td class="value"><strong>{{ mb_convert_case($estudiante->nombre_padres ?: 'N/A', MB_CASE_TITLE, 'UTF-8') }}</strong></td>
        </tr>
        <tr>
            <td class="label">Contacto de Emergencia:</td>
            <td class="value">{{ $estudiante->contacto_emergencia ?: $estudiante->celular ?: 'N/A' }}</td>
        </tr>
    </table>

    <div class="section-title">3. Datos del Curso y Horario</div>
    <table class="info-table">
        <tr>
            <td class="label">Idioma Registrado:</td>
            <td class="value"><strong>{{ mb_convert_case($curso->idioma ?? 'Inglés', MB_CASE_TITLE, 'UTF-8') }}</strong></td>
        </tr>
        <tr>
            <td class="label">Nivel de Estudio:</td>
            <td class="value"><strong>{{ mb_convert_case($curso->nivel ?? 'Nivel 1', MB_CASE_TITLE, 'UTF-8') }}</strong></td>
        </tr>
        <tr>
            <td class="label">Paralelo Asignado:</td>
            <td class="value">{{ mb_convert_case($paralelo->nombre ?? 'A', MB_CASE_TITLE, 'UTF-8') }}</td>
        </tr>
        <tr>
            <td class="label">Horario de Clases:</td>
            <td class="value"><strong>{{ $curso->horario ?: ($paralelo->horario ?: '08:00 - 12:00 / Turno Regular') }}</strong></td>
        </tr>
        <tr>
            <td class="label">Modalidad:</td>
            <td class="value">{{ mb_convert_case($curso->modalidad ?? 'Presencial', MB_CASE_TITLE, 'UTF-8') }}</td>
        </tr>
        <tr>
            <td class="label">Fecha de Inscripción:</td>
            <td class="value">{{ $inscripcion->fecha_registro ?: date('Y-m-d') }}</td>
        </tr>
        <tr>
            <td class="label">Estado de Inscripción:</td>
            <td class="value"><strong>{{ mb_convert_case($inscripcion->estado ?: 'Confirmado', MB_CASE_TITLE, 'UTF-8') }}</strong></td>
        </tr>
    </table>

    <table class="signatures-table">
        <tr>
            <td style="width: 45%;">
                <div class="sig-line">
                    Firma del Estudiante / Tutor<br>
                    C.I. {{ $estudiante->ci }}
                </div>
            </td>
            <td style="width: 10%;"></td>
            <td style="width: 45%;">
                <div class="sig-line">
                    Sello y Firma de Recepción<br>
                    Escuela de Idiomas del Ejército
                </div>
            </td>
        </tr>
    </table>

    <div class="footer-note">
        Documento Oficial de Registro de Inscripción emitido por el Sistema de Gestión Académica EIE. Cochabamba, Bolivia.
    </div>

</body>
</html>
