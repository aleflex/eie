<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== LIMPIEZA DE REGISTROS ANTIGUOS EN AIVEN MYSQL DB ===\n\n";

// 1. Limpiar fotos de perfil en estudiantes que no sean URLs de Supabase Storage
$affectedPhotos = DB::table('estudiantes')
    ->whereNotNull('foto_4x4_url')
    ->where('foto_4x4_url', '!=', '')
    ->where('foto_4x4_url', 'NOT LIKE', 'https://xrtemuwuseageaeeeeuq.supabase.co%')
    ->update(['foto_4x4_url' => null]);

echo "Fotos antiguas limpiadas en estudiantes (seteada a NULL): {$affectedPhotos}\n";

// 2. Limpiar documentos antiguos con rutas locales /storage/ que daban 404
$affectedDocs = DB::table('documentos')
    ->where('ruta_archivo', 'LIKE', '/storage/%')
    ->orWhere('ruta_archivo', 'LIKE', 'data:image%')
    ->delete();

echo "Documentos antiguos corruptos/locales eliminados: {$affectedDocs}\n\n";

echo "=== LIMPIEZA COMPLETADA CON ÉXITO ===\n";
