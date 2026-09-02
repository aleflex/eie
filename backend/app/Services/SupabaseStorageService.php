<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SupabaseStorageService
{
    protected static $supabaseUrl = 'https://xrtemuwuseageaeeeeuq.supabase.co';
    protected static $anonKey = 'sb_publishable_te0VbMYKOOy_6rzDX8Nu9g_YrvMQzIO';
    protected static $bucket = 'eie-storage';

    protected static $bucketChecked = false;
    protected static $isAvailable = null;

    /**
     * Comprueba si el host de Supabase está activo y responde en DNS de forma ultra rápida.
     */
    public static function isAvailable()
    {
        if (self::$isAvailable !== null) {
            return self::$isAvailable;
        }

        $host = parse_url(self::$supabaseUrl, PHP_URL_HOST);
        if (empty($host)) {
            self::$isAvailable = false;
            return false;
        }

        // Validación DNS instantánea (no bloquea el servidor si el dominio no existe o está pausado)
        $ip = @gethostbyname($host);
        if ($ip === $host) {
            // El dominio no resuelve en DNS
            self::$isAvailable = false;
            return false;
        }

        self::$isAvailable = true;
        return true;
    }

    /**
     * Intenta asegurar que el bucket público exista en Supabase.
     */
    protected static function ensureBucketExists()
    {
        if (self::$bucketChecked || !self::isAvailable()) {
            return;
        }
        try {
            $bucketUrl = rtrim(self::$supabaseUrl, '/') . '/storage/v1/bucket';
            $response = Http::timeout(1.5)->connectTimeout(1.0)->withHeaders([
                'apikey' => self::$anonKey,
                'Authorization' => 'Bearer ' . self::$anonKey,
                'Content-Type' => 'application/json'
            ])->post($bucketUrl, [
                'id' => self::$bucket,
                'name' => self::$bucket,
                'public' => true
            ]);

            if ($response->successful()) {
                self::$bucketChecked = true;
            } else {
                self::$isAvailable = false;
            }
        } catch (\Exception $e) {
            self::$isAvailable = false;
        }
    }

    /**
     * Sube un archivo binario a Supabase Storage y retorna la URL pública.
     * Si falla o la conexión es lenta, retorna NULL de inmediato (< 1ms)
     * para que el backend guarde en disco local al instante.
     */
    public static function uploadFile($fileBinary, $remotePath, $mimeType = 'application/octet-stream')
    {
        if (!self::isAvailable()) {
            return null;
        }

        try {
            self::ensureBucketExists();
            if (!self::isAvailable()) {
                return null;
            }

            $uploadUrl = rtrim(self::$supabaseUrl, '/') . '/storage/v1/object/' . self::$bucket . '/' . ltrim($remotePath, '/');

            $response = Http::timeout(2.0)->connectTimeout(1.0)->withHeaders([
                'apikey' => self::$anonKey,
                'Authorization' => 'Bearer ' . self::$anonKey,
                'Content-Type' => $mimeType,
                'x-upsert' => 'true'
            ])->withBody($fileBinary, $mimeType)->post($uploadUrl);

            if ($response->successful()) {
                $publicUrl = rtrim(self::$supabaseUrl, '/') . '/storage/v1/object/public/' . self::$bucket . '/' . ltrim($remotePath, '/');
                return $publicUrl;
            } else {
                Log::warning('Supabase Storage upload warning: ' . $response->body());
                self::$isAvailable = false;
            }
        } catch (\Exception $e) {
            self::$isAvailable = false;
            Log::warning('Supabase Storage timeout/error: ' . $e->getMessage());
        }

        return null;
    }
}

