<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
try {
    foreach (['estudiantes', 'usuarios', 'responsables', 'contactos_emergencia', 'inscripciones'] as $table) {
        echo "=== TABLE: $table ===\n";
        $columns = DB::select("DESCRIBE $table");
        foreach ($columns as $col) {
            echo "  {$col->Field} - {$col->Type} - Null: {$col->Null} - Key: {$col->Key} - Default: {$col->Default}\n";
        }
    }
} catch (\Exception $e) {
    echo $e->getMessage() . "\n";
}
