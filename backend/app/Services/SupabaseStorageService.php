<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SupabaseStorageService
{
    protected static $supabaseUrl = 'https://xrtemuwuseageaeeeeuq.supabase.co';
    protected static $anonKey = 'sb_publishable_te0VbMYKOOy_6rzDX8Nu9g_YrvMQzIO';
    protected static $bucket = 'eie-storage';

    /**
     * Sube un archivo binario o Base64 a Supabase Storage y retorna la URL pública.
     * Si falla o el bucket no existe aún, retorna NULL para activar el fallback.
     */
    public static function uploadFile($fileBinary, $remotePath, $mimeType = 'application/octet-stream')
    {
        try {
            $uploadUrl = rtrim(self::$supabaseUrl, '/') . '/storage/v1/object/' . self::$bucket . '/' . ltrim($remotePath, '/');

            $response = Http::withHeaders([
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
