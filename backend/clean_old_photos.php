<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== LIMPIEZA DE REGISTROS ANTIGUOS EN AIVEN MYSQL DB ===\n\n";

// 1. Limpiar fotos de perfil corruptas (que sean rutas locales antiguas o data: URIs incompletos)
$affectedPhotos = DB::table('estudiantes')
    ->whereNotNull('foto_4x4_url')
    ->where('foto_4x4_url', '!=', '')
    ->where(function($q) {
        $q->where('foto_4x4_url', 'LIKE', '/storage/%')
          ->orWhere('foto_4x4_url', 'LIKE', 'data:%');
    })
    ->update(['foto_4x4_url' => null]);

echo "Fotos corruptas/base64 limpiadas en estudiantes (seteada a NULL): {$affectedPhotos}\n";

// 2. Limpiar documentos antiguos con rutas locales /storage/ o data: URIs corruptos
$affectedDocs = DB::table('documentos')
    ->where('ruta_archivo', 'LIKE', '/storage/%')
    ->orWhere('ruta_archivo', 'LIKE', 'data:%')
    ->delete();

echo "Documentos corruptos/locales eliminados: {$affectedDocs}\n\n";

echo "=== LIMPIEZA COMPLETADA CON ÉXITO ===\n";
