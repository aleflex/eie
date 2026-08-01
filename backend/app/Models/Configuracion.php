<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Configuracion extends Model
{
    protected $table = 'configuraciones';
    protected $primaryKey = 'id_configuracion';

    protected $fillable = ['clave', 'valor', 'tipo', 'grupo'];

    /**
     * Obtiene una configuración cacheada por clave
     */
    public static function get(string $clave, $default = null)
    {
        return Cache::rememberForever("configuracion.{$clave}", function () use ($clave, $default) {
            $config = self::where('clave', $clave)->first();
            if (!$config) {
                return $default;
            }

            return match ($config->tipo) {
                'int', 'integer' => (int) $config->valor,
                'bool', 'boolean' => filter_var($config->valor, FILTER_VALIDATE_BOOLEAN),
                'json' => json_decode($config->valor, true),
                default => $config->valor,
            };
        });
    }

    /**
     * Guarda o actualiza una configuración, borrando la caché correspondiente
     */
    public static function set(string $clave, $valor, string $tipo = 'string', string $grupo = 'general')
    {
        $config = self::updateOrCreate(
            ['clave' => $clave],
            [
                'valor' => is_array($valor) ? json_encode($valor) : (string) $valor,
                'tipo' => $tipo,
                'grupo' => $grupo
            ]
        );

        Cache::forget("configuracion.{$clave}");
        return $config;
    }
}
