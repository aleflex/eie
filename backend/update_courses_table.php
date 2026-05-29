<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

try {
    echo "Añadiendo columnas IDIOMA y NIVEL a la tabla CURSOS...\n";
    
    Schema::table('cursos', function ($table) {
        if (!Schema::hasColumn('cursos', 'idioma')) {
            $table->string('idioma')->default('Inglés')->after('id');
        }
        if (!Schema::hasColumn('cursos', 'nivel')) {
            $table->string('nivel')->default('Básico')->after('idioma');
        }
    });

    echo "¡Éxito! Columnas añadidas correctamente.\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
