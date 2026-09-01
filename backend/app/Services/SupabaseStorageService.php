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

    /**
     * Intenta asegurar que el bucket público exista en Supabase.
     */
    protected static function ensureBucketExists()
    {
        if (self::$bucketChecked) {
            return;
        }
        try {
            $bucketUrl = rtrim(self::$supabaseUrl, '/') . '/storage/v1/bucket';
            Http::timeout(3)->withHeaders([
                'apikey' => self::$anonKey,
                'Authorization' => 'Bearer ' . self::$anonKey,
                'Content-Type' => 'application/json'
            ])->post($bucketUrl, [
                'id' => self::$bucket,
                'name' => self::$bucket,
                'public' => true
            ]);
            self::$bucketChecked = true;
        } catch (\Exception $e) {
            // Silencioso si falla
        }
    }

    /**
     * Sube un archivo binario a Supabase Storage y retorna la URL pública.
     * Si falla, retorna NULL para activar el fallback permanente en BD.
     */
    public static function uploadFile($fileBinary, $remotePath, $mimeType = 'application/octet-stream')
    {
        try {
            self::ensureBucketExists();

            $uploadUrl = rtrim(self::$supabaseUrl, '/') . '/storage/v1/object/' . self::$bucket . '/' . ltrim($remotePath, '/');

            $response = Http::timeout(4)->withHeaders([
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
            }
        } catch (\Exception $e) {
            Log::error('Supabase Storage exception: ' . $e->getMessage());
        }

        return null;
    }
}
