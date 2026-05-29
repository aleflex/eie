<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;

try {
    echo "Añadiendo CUPO MÍNIMO a la tabla CURSOS...\n";
    
    Schema::table('cursos', function ($table) {
        if (!Schema::hasColumn('cursos', 'cupo_minimo')) {
            $table->integer('cupo_minimo')->default(10)->after('horario');
        }
    });

    echo "¡Éxito! Columna cupo_minimo añadida.\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
