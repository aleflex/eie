<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

try {
    echo "Añadiendo columna TIPO_USUARIO...\n";
    
    if (!Schema::hasColumn('estudiantes', 'tipo_usuario')) {
        DB::statement('ALTER TABLE estudiantes ADD COLUMN tipo_usuario VARCHAR(50) DEFAULT "normal" AFTER id');
        echo " - Columna tipo_usuario añadida.\n";
    } else {
        echo " - La columna ya existe.\n";
    }

    echo "\n¡Éxito!\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
