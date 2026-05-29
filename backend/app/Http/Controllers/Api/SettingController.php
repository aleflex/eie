<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Obtiene todas las configuraciones en un objeto clave-valor simple
     */
    public function index()
    {
        $settings = Setting::all();
        $response = [];

        foreach ($settings as $setting) {
            $value = $setting->value;
            
            // Castear el valor según su tipo
            $castedValue = match ($setting->type) {
                'int', 'integer' => (int) $value,
                'bool', 'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
                'json' => json_decode($value, true),
                default => $value,
            };

            $response[$setting->key] = $castedValue;
        }

        // Si la tabla está vacía, retornar un objeto vacío
        return response()->json($response);
    }

    /**
     * Actualiza o crea múltiples configuraciones recibidas en el body
     */
    public function update(Request $request)
    {
        $data = $request->all();

        foreach ($data as $key => $value) {
            // Buscar la configuración existente para mantener el tipo y grupo original
            $existing = Setting::where('key', $key)->first();
            
            $type = $existing ? $existing->type : 'string';
            $group = $existing ? $existing->group : 'general';

            // Si es un booleano o número detectado, y no tiene configuración previa
            if (!$existing) {
                if (is_bool($value)) {
                    $type = 'bool';
                } elseif (is_numeric($value)) {
                    $type = 'int';
                } elseif (is_array($value)) {
                    $type = 'json';
                }
            }

            Setting::set($key, $value, $type, $group);
        }

        return response()->json([
            'message' => 'Configuraciones actualizadas con éxito',
            'settings' => $this->index()->original
        ]);
    }
}
