<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    echo "--- ESTRUCTURA DE ESTUDIANTES ---\n";
    $columns = DB::select('DESCRIBE estudiantes');
    foreach($columns as $column) {
        echo "{$column->Field} | {$column->Type} | " . ($column->Null == 'NO' ? 'NOT NULL' : 'NULL') . "\n";
    }
    
    echo "\n--- ESTRUCTURA DE INSCRIPCIONES ---\n";
    $columnsInsc = DB::select('DESCRIBE inscripciones');
    foreach($columnsInsc as $column) {
        echo "{$column->Field} | {$column->Type} | " . ($column->Null == 'NO' ? 'NOT NULL' : 'NULL') . "\n";
    }
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
