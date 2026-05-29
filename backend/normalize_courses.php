<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

try {
    echo "NORMALIZANDO TABLA CURSOS...\n";
    
    Schema::table('cursos', function ($table) {
        // Eliminar columnas viejas que pueden causar conflictos
        if (Schema::hasColumn('cursos', 'idioma_id')) $table->dropColumn('idioma_id');
        if (Schema::hasColumn('cursos', 'nivel_id')) $table->dropColumn('nivel_id');
        if (Schema::hasColumn('cursos', 'docente_id')) $table->dropColumn('docente_id');
        
        // Asegurar que las nuevas no sean NULL
        // Nota: Cambiamos a nullable por seguridad durante la transición
    });

    echo "¡Tabla normalizada con éxito!\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
