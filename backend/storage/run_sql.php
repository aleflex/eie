<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $sql = file_get_contents(__DIR__ . '/alter_inscripciones.sql');
    DB::unprepared($sql);
    echo "SQL script executed successfully. Inscripciones altered!\n";
} catch (\Exception $e) {
    echo "Error executing SQL script: " . $e->getMessage() . "\n";
}
