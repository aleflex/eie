<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    echo "--- ESTRUCTURA DE CURSOS ---\n";
    $columns = DB::select('DESCRIBE cursos');
    foreach($columns as $column) {
        echo "{$column->Field}\n";
    }
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
