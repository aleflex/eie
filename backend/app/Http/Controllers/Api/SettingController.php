<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Configuracion;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Obtiene todas las configuraciones en un objeto clave-valor simple
     */
    public function index()
    {
        $settings = Configuracion::all();
        $response = [];

        foreach ($settings as $setting) {
            $value = $setting->valor;
            
            // Castear el valor según su tipo
            $castedValue = match ($setting->tipo) {
                'int', 'integer' => (int) $value,
                'bool', 'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
                'json' => json_decode($value, true),
                default => $value,
            };

            $response[$setting->clave] = $castedValue;
        }

        return response()->json($response);
    }

    /**
     * Actualiza o crea múltiples configuraciones recibidas en el body
     */
    public function update(Request $request)
    {
        $data = $request->all();

        foreach ($data as $clave => $value) {
            // Buscar la configuración existente para mantener el tipo y grupo original
            $existing = Configuracion::where('clave', $clave)->first();
            
            $tipo = $existing ? $existing->tipo : 'string';
            $grupo = $existing ? $existing->grupo : 'general';

            // Si es un booleano o número detectado, y no tiene configuración previa
            if (!$existing) {
                if (is_bool($value)) {
                    $tipo = 'bool';
                } elseif (is_numeric($value)) {
                    $tipo = 'int';
                } elseif (is_array($value)) {
                    $tipo = 'json';
                }
            }

            Configuracion::set($clave, $value, $tipo, $grupo);
        }

        return response()->json([
            'message' => 'Configuraciones actualizadas con éxito',
            'settings' => $this->index()->original
        ]);
    }

    /**
     * Retorna el estado público del periodo de inscripciones (si está abierto o cerrado, y sus fechas límites).
     */
    public function getPeriodoInscripcion()
    {
        $fechaInicio = Configuracion::where('clave', 'fecha_inicio_inscripcion')->value('valor');
        $fechaFin = Configuracion::where('clave', 'fecha_fin_inscripcion')->value('valor');

        $ahora = now();
        $abierto = true;
        $motivo = '';

        if (!empty($fechaInicio)) {
            try {
                $inicio = \Carbon\Carbon::parse($fechaInicio);
                if ($ahora->lessThan($inicio)) {
                    $abierto = false;
                    $motivo = 'El periodo de inscripciones aún no ha comenzado. Apertura programada: ' . $inicio->format('d/m/Y H:i');
                }
            } catch (\Throwable $e) {}
        }

        if ($abierto && !empty($fechaFin)) {
            try {
                $fin = \Carbon\Carbon::parse($fechaFin);
                if ($ahora->greaterThan($fin)) {
                    $abierto = false;
                    $motivo = 'El periodo de inscripciones ha finalizado. La fecha y hora límite de cierre fue: ' . $fin->format('d/m/Y H:i');
                }
            } catch (\Throwable $e) {}
        }

        return response()->json([
            'abierto' => $abierto,
            'motivo' => $motivo,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'hora_actual' => $ahora->toIso8601String()
        ]);
    }
}
