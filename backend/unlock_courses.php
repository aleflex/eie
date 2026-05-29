<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

try {
    echo "HACIENDO COLUMNAS ANTIGUAS OPCIONALES...\n";
    
    // Usamos SQL directo porque el dropColumn falló antes por las llaves foráneas
    DB::statement("ALTER TABLE cursos MODIFY docente_id INT NULL");
    DB::statement("ALTER TABLE cursos MODIFY idioma_id INT NULL");
    DB::statement("ALTER TABLE cursos MODIFY nivel_id INT NULL");

    echo "¡Base de datos desbloqueada con éxito!\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
