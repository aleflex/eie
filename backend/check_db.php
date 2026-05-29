<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$tables = ['estudiantes', 'inscripciones', 'cursos'];

foreach ($tables as $table) {
    echo "--- Table: $table ---\n";
    if (Schema::hasTable($table)) {
        $columns = Schema::getColumnListing($table);
        foreach ($columns as $column) {
            $type = Schema::getColumnType($table, $column);
            echo "- $column ($type)\n";
        }
    } else {
        echo "Table does not exist.\n";
    }
    echo "\n";
}
